<?php declare(strict_types=1);

namespace Phan\Daemon;

use Closure;
use Phan\Analysis;
use Phan\CodeBase;
use Phan\Config;
use Phan\Daemon;
use Phan\Daemon\Transport\Responder;
use Phan\Language\FileRef;
use Phan\Language\Type;
use Phan\LanguageServer\CompletionRequest;
use Phan\LanguageServer\FileMapping;
use Phan\LanguageServer\GoToDefinitionRequest;
use Phan\LanguageServer\NodeInfoRequest;
use Phan\Library\FileCache;
use Phan\Library\StringUtil;
use Phan\Output\IssuePrinterInterface;
use Phan\Output\PrinterFactory;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Represents the state of a client request to a daemon, and contains methods for sending formatted responses.
 * @phan-file-suppress PhanPluginDescriptionlessCommentOnPublicMethod
 */
class Request
{
    const METHOD_ANALYZE_FILES = 'analyze_files';  // has shorthand analyze_file with param 'file'

    const PARAM_METHOD = 'method';
    const PARAM_FILES  = 'files';
    const PARAM_FORMAT = 'format';
    const PARAM_COLOR  = 'color';
    const PARAM_TEMPORARY_FILE_MAPPING_CONTENTS = 'temporary_file_mapping_contents';

    // success codes
    const STATUS_OK = 'ok';  // unrecognized output format
    const STATUS_NO_FILES = 'no_files';  // none of the requested files were in this project's config directories

    // failure codes
    const STATUS_INVALID_FORMAT = 'invalid_format';  // unrecognized requested output "format"
    const STATUS_ERROR_UNKNOWN = 'error_unknown';
    const STATUS_INVALID_FILES = 'invalid_files';  // expected a valid string for 'files'/'file'
    const STATUS_INVALID_METHOD = 'invalid_method';  // expected 'method' to be analyze_files or
    const STATUS_INVALID_REQUEST = 'invalid_request';  // expected a valid string for 'files'/'file'

    /** @var Responder|null - Null after the response is sent. */
    private $responder;

    /**
     * @var array{method:string,files:array<int,string>,format:string,temporary_file_mapping_contents:array<string,string>}
     *
     * The configuration passed in with the request to the daemon.
     */
    private $request_config;

    /** @var BufferedOutput this collects the serialized issues emitted by this worker to be sent back to the master process */
    private $buffered_output;

    /** @var string the method of the daemon being invoked */
    private $method;

    /** @var array<int,string>|null the list of files the client has requested to be analyzed */
    private $files = null;

    /**
     * A set of process ids of child processes
     * @var array<int,true>
     */
    private static $child_pids = [];

    /**
     * A set of process ids of child processes
     * @var array<int,int>
     */
    private static $exited_pid_status = [];


    /**
     * The most recent Language Server Protocol request to look up what an element is
     * (e.g. "go to definition", "go to type definition", "hover")
     *
     * @var ?NodeInfoRequest
     */
    private $most_recent_node_info_request;

    /**
     * If true, this process will exit() after finishing.
     * If false, this class will instead throw ExitException to be caught by the caller
     * (E.g. if pcntl is unavailable)
     *
     * @var bool
     */
    private $should_exit;

    /**
     * @param array{method:string,files:array<int,string>,format:string,temporary_file_mapping_contents:array<string,string>} $config
     * @param ?NodeInfoRequest $most_recent_node_info_request
     */
    private function __construct(Responder $responder, array $config, $most_recent_node_info_request, bool $should_exit)
    {
        $this->responder = $responder;
        $this->request_config = $config;
        $this->buffered_output = new BufferedOutput();
        $this->method = $config[self::PARAM_METHOD];
        if ($this->method === self::METHOD_ANALYZE_FILES) {
            $this->files = $config[self::PARAM_FILES];
        }
        $this->most_recent_node_info_request = $most_recent_node_info_request;
        $this->should_exit = $should_exit;
    }

    /**
     * @param string $file_path an absolute or relative path to be analyzed
     */
    public function shouldUseMappingPolyfill(string $file_path) : bool
    {
        $most_recent_node_info_request = $this->most_recent_node_info_request;
        if ($most_recent_node_info_request) {
            return $most_recent_node_info_request->getPath() === Config::projectPath($file_path);
        }
        return false;
    }

    /**
     * @param string $file_path an absolute or relative path to be analyzed
     */
    public function shouldAddPlaceholdersForPath(string $file_path) : bool
    {
        $most_recent_node_info_request = $this->most_recent_node_info_request;
        if ($most_recent_node_info_request) {
            return $most_recent_node_info_request->getPath() === Config::projectPath($file_path) &&
                $most_recent_node_info_request instanceof CompletionRequest;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getTargetByteOffset(string $file_contents) : int
    {
        $most_recent_node_info_request = $this->most_recent_node_info_request;
        if ($most_recent_node_info_request) {
            $position = $most_recent_node_info_request->getPosition();
            return $position->toOffset($file_contents);
        }
        return -1;
    }

    /**
     * @return void (unreachable)
     * @throws ExitException to imitate an exit without actually exiting
     */
    public function exit(int $exit_code)
    {
        if ($this->should_exit) {
            Daemon::debugf("Exiting");
            exit($exit_code);
        }
        throw new ExitException("done", $exit_code);
    }

    /**
     * @param Responder $responder (e.g. a socket to write a response on)
     * @param array<int,string> $file_names absolute path of file(s) to analyze
     * @param CodeBase $code_base (for refreshing parse state)
     * @param Closure $file_path_lister (for refreshing parse state)
     * @param FileMapping $file_mapping object tracking the overrides made by a client.
     * @param ?NodeInfoRequest $most_recent_node_info_request contains a promise that we want the resolution of
     * @param bool $should_exit - If this is true, calling $this->exit() will terminate the program. If false, ExitException will be thrown.
     */
    public static function makeLanguageServerAnalysisRequest(
        Responder $responder,
        array $file_names,
        CodeBase $code_base,
        Closure $file_path_lister,
        FileMapping $file_mapping,
        $most_recent_node_info_request,
        bool $should_exit
    ) : Request {
        FileCache::clear();
        $file_mapping_contents = self::normalizeFileMappingContents($file_mapping->getOverrides(), $error_message);
        // Use the temporary contents if they're available
        Request::reloadFilePathListForDaemon($code_base, $file_path_lister, $file_mapping_contents, $file_names);
        if ($error_message !== null) {
            Daemon::debugf($error_message);
        }
        $result = new self(
            $responder,
            [
                self::PARAM_FORMAT => 'json',
                self::PARAM_METHOD => self::METHOD_ANALYZE_FILES,
                self::PARAM_FILES => $file_names,
                self::PARAM_TEMPORARY_FILE_MAPPING_CONTENTS => $file_mapping_contents,
            ],
            $most_recent_node_info_request,
            $should_exit
        );
        return $result;
    }

    /**
     * Returns a printer that will be used to send JSON serialized data to the daemon client (i.e. `phan_client`).
     */
    public function getPrinter() : IssuePrinterInterface
    {
        $this->handleClientColorOutput();

        $factory = new PrinterFactory();
        $format = $this->request_config[self::PARAM_FORMAT] ?? 'json';
        if (!in_array($format, $factory->getTypes())) {
            $this->sendJSONResponse([
                "status" => self::STATUS_INVALID_FORMAT,
            ]);
            exit(0);
        }
        return $factory->getPrinter($format, $this->buffered_output);
    }

    /**
     * Handle a request created by the client with `phan_client --color`
     */
    private function handleClientColorOutput()
    {
        // Back up the original state: If pcntl isn't used, we don't want subsequent requests to be accidentally colorized.
        static $original_color = null;
        if ($original_color === null) {
            $original_color = (bool)Config::getValue('color_issue_messages');
        }
        $new_color = $this->request_config[self::PARAM_COLOR] ?? $original_color;
        Config::setValue('color_issue_messages', $new_color);
    }

    /**
     * Respond with issues in the requested format
     * @return void
     * @see LanguageServer::handleJSONResponseFromWorker() for one possible usage of this
     */
    public function respondWithIssues(int $issue_count)
    {
        $raw_issues = $this->buffered_output->fetch();
        if (($this->request_config[self::PARAM_FORMAT] ?? null) === 'json') {
            $issues = json_decode($raw_issues, true);
            if (!\is_array($issues)) {
                $issues = "(Failed to decode) " . json_last_error_msg() . ': ' . $raw_issues;
            }
        } else {
            $issues = $raw_issues;
        }
        $response = [
            "status" => self::STATUS_OK,
            "issue_count" => $issue_count,
            "issues" => $issues,
        ];
        $most_recent_node_info_request = $this->most_recent_node_info_request;
        if ($most_recent_node_info_request instanceof GoToDefinitionRequest) {
            $response['definitions'] = $most_recent_node_info_request->getDefinitionLocations();
            $response['hover_response'] = $most_recent_node_info_request->getHoverResponse();
        } elseif ($most_recent_node_info_request instanceof CompletionRequest) {
            $response['completions'] = $most_recent_node_info_request->getCompletions();
        }
        $this->sendJSONResponse($response);
    }

    /**
     * @return void
     */
    public function respondWithNoFilesToAnalyze()
    {
        // The mentioned file wasn't in .phan/config.php's list of files to analyze.
        $this->sendJSONResponse([
            "status" => self::STATUS_NO_FILES,
        ]);
    }

    /**
     * @param array<int,string> $analyze_file_path_list
     * @return array<int,string>
     */
    public function filterFilesToAnalyze(array $analyze_file_path_list) : array
    {
        if (\is_null($this->files)) {
            Daemon::debugf("No files to filter in filterFilesToAnalyze");
            return $analyze_file_path_list;
        }

        $analyze_file_path_set = array_flip($analyze_file_path_list);
        $filtered_files = [];
        foreach ($this->files as $file) {
            // Must be relative to project, allow absolute paths to be passed in.
            $file = FileRef::getProjectRelativePathForPath($file);

            if (\array_key_exists($file, $analyze_file_path_set)) {
                $filtered_files[] = $file;
            } else {
                // TODO: Reload file list once before processing request?
                // TODO: Change this to also support analyzing files that would normally be parsed but not analyzed?
                Daemon::debugf("Failed to find requested file '%s' in parsed file list", $file, StringUtil::jsonEncode($analyze_file_path_list));
            }
        }
        Daemon::debugf("Returning file set: %s", StringUtil::jsonEncode($filtered_files));
        return $filtered_files;
    }

    /**
     * TODO: convert absolute path to file contents
     * @return array<string,string> - Maps original relative file paths to contents.
     */
    public function getTemporaryFileMapping() : array
    {
        $mapping = $this->request_config[self::PARAM_TEMPORARY_FILE_MAPPING_CONTENTS] ?? [];
        if (!is_array($mapping)) {
            $mapping = [];
        }
        Daemon::debugf("Have the following files in mapping: %s", StringUtil::jsonEncode(array_keys($mapping)));
        return $mapping;
    }

    /**
     * @return ?NodeInfoRequest
     */
    public function getMostRecentNodeInfoRequest()
    {
        return $this->most_recent_node_info_request;
    }

    /**
     * @return void
     */
    public function rejectLanguageServerRequestsRequiringAnalysis()
    {
        $most_recent_node_info_request = $this->most_recent_node_info_request;
        if ($most_recent_node_info_request) {
            $most_recent_node_info_request->finalize();
            $this->most_recent_node_info_request = null;
        }
    }

    /**
     * Send a response and close the connection, for the given socket's protocol.
     * Currently supports only JSON.
     * TODO: HTTP protocol.
     *
     * @param array<string,mixed> $response
     * @return void
     */
    public function sendJSONResponse(array $response)
    {
        $responder = $this->responder;
        if (!$responder) {
            Daemon::debugf("Already sent response");
            return;
        }
        $responder->sendResponseAndClose($response);
        $this->responder = null;
    }

    public function __destruct()
    {
        $responder = $this->responder;
        if ($responder) {
            $responder->sendResponseAndClose([
                'status' => self::STATUS_ERROR_UNKNOWN,
                'message' => 'failed to send a response - Possibly encountered an exception. See daemon output: ' . StringUtil::jsonEncode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)),
            ]);
            $this->responder = null;
        }
    }

    /**
     * @param int $signo
     * @param int|null $status
     * @param int|null $pid
     * @return void
     */
    public static function childSignalHandler($signo, $status = null, $pid = null)
    {
        // test
        if ($signo !== SIGCHLD) {
            return;
        }
        if (!$pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        Daemon::debugf("Got signal pid=%s", StringUtil::jsonEncode($pid));

        while ($pid > 0) {
            if (\array_key_exists($pid, self::$child_pids)) {
                $exit_code = pcntl_wexitstatus($status);
                if ($exit_code != 0) {
                    error_log(sprintf("child process %d exited with status %d\n", $pid, $exit_code));
                } else {
                    Daemon::debugf("child process %d completed successfully", $pid);
                }
                unset(self::$child_pids[$pid]);
            } elseif ($pid > 0) {
                self::$exited_pid_status[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    /**
     * @param array<string,string> $file_mapping_contents
     * @param ?string &$error_message @phan-output-reference
     */
    public static function normalizeFileMappingContents($file_mapping_contents, &$error_message) : array
    {
        $error_message = null;
        if (!\is_array($file_mapping_contents)) {
            $error_message = 'Invalid value of temporary_file_mapping_contents';
        }
        $new_file_mapping_contents = [];
        foreach ($file_mapping_contents ?? [] as $file => $contents) {
            if (!\is_string($file)) {
                $error_message = 'Passed non-string in list of files to map';
                return [];
            } elseif (!\is_string($contents)) {
                $error_message = 'Passed non-string in as new file contents';
                return [];
            }
            $new_file_mapping_contents[FileRef::getProjectRelativePathForPath($file)] = $contents;
        }
        return $new_file_mapping_contents;
    }
    /**
     * @param CodeBase $code_base
     * @param \Closure $file_path_lister lists all files that will be parsed by Phan
     * @param Responder $responder
     * @return ?Request - non-null if this is a worker process with work to do. null if request failed or this is the master.
     */
    public static function accept(CodeBase $code_base, \Closure $file_path_lister, Responder $responder, bool $fork)
    {
        FileCache::clear();

        $request = $responder->getRequestData();

        if (!\is_array($request)) {
            $responder->sendResponseAndClose([
                'status'  => self::STATUS_INVALID_REQUEST,
                'message' => 'malformed JSON',
            ]);
            return null;
        }
        $new_file_mapping_contents = [];
        $method = $request['method'] ?? '';
        $files = null;
        switch ($method) {
            case 'analyze_all':
                // Analyze the default list of files. No expected params.
                break;
            case 'analyze_file':
                $method = 'analyze_files';
                $request = [
                    self::PARAM_METHOD => $method,
                    self::PARAM_FILES => [$request['file']],
                    self::PARAM_FORMAT => $request[self::PARAM_FORMAT] ?? 'json',
                ];
                // Fall through, this is an alias of analyze_files
            case 'analyze_files':
                // Analyze the list of strings provided in "files"
                $files = $request[self::PARAM_FILES] ?? null;
                $request[self::PARAM_FORMAT] = $request[self::PARAM_FORMAT] ?? 'json';
                $error_message = null;
                if (\is_array($files) && count($files)) {
                    foreach ($files as $file) {
                        if (!\is_string($file)) {
                            $error_message = 'Passed non-string in list of files';
                            break;
                        }
                    }
                } else {
                    $error_message = 'Must pass a non-empty array of file paths for field files';
                }
                if (\is_null($error_message)) {
                    $file_mapping_contents = $request[self::PARAM_TEMPORARY_FILE_MAPPING_CONTENTS] ?? [];
                    if (is_array($file_mapping_contents)) {
                        $new_file_mapping_contents = self::normalizeFileMappingContents($file_mapping_contents, $error_message);
                        $request[self::PARAM_TEMPORARY_FILE_MAPPING_CONTENTS] = $new_file_mapping_contents;
                    } else {
                        $error_message = 'Must pass an optional array or null for temporary_file_mapping_contents';
                    }
                }
                if ($error_message !== null) {
                    Daemon::debugf($error_message);
                    $responder->sendResponseAndClose([
                        'status'  => self::STATUS_INVALID_FILES,
                        'message' => $error_message,
                    ]);
                    return null;
                }
                break;
                // TODO(optional): add APIs to resolve types of variables/properties/etc (e.g. accept byte offset or line/column offset)
            default:
                $message = sprintf("expected method to be analyze_all or analyze_files, got %s", StringUtil::jsonEncode($method));
                Daemon::debugf($message);
                $responder->sendResponseAndClose([
                    'status'  => self::STATUS_INVALID_METHOD,
                    'message' => $message,
                ]);
                return null;
        }

        // Re-parse the file list
        self::reloadFilePathListForDaemon($code_base, $file_path_lister, $new_file_mapping_contents, $files);

        // Analyze the files that are open in the IDE (If pcntl is available, the analysis is done in a forked process)

        if (!$fork) {
            Daemon::debugf("This is the main process pretending to be the fork");
            self::$child_pids = [];
            // This is running on the only thread, so configure $request_obj to throw ExitException instead of calling exit()
            // @phan-suppress-next-line PhanPartialTypeMismatchArgument pre-existing
            $request_obj = new self($responder, $request, null, false);
            $temporary_file_mapping = $request_obj->getTemporaryFileMapping();
            if (count($temporary_file_mapping) > 0) {
                self::applyTemporaryFileMappingForParsePhase($code_base, $temporary_file_mapping);
            }
            return $request_obj;
        }

        $fork_result = pcntl_fork();
        if ($fork_result < 0) {
            error_log("The daemon failed to fork. Going to terminate");
        } elseif ($fork_result == 0) {
            Daemon::debugf("This is the fork");
            self::handleBecomingChildAnalysisProcess();
            // @phan-suppress-next-line PhanPartialTypeMismatchArgument pre-existing
            $request_obj = new self($responder, $request, null, true);
            $temporary_file_mapping = $request_obj->getTemporaryFileMapping();
            if (count($temporary_file_mapping) > 0) {
                self::applyTemporaryFileMappingForParsePhase($code_base, $temporary_file_mapping);
            }
            return $request_obj;
        } else {
            $pid = $fork_result;
            self::handleBecomingParentOfChildAnalysisProcess($pid);
        }
        return null;
    }

    /**
     * @param int $pid the child PID of this process that is performing analysis
     * @return void
     */
    public static function handleBecomingParentOfChildAnalysisProcess(int $pid)
    {
        $status = self::$exited_pid_status[$pid] ?? null;
        if (isset($status)) {
            Daemon::debugf("child process %d already exited", $pid);
            self::childSignalHandler(SIGCHLD, $status, $pid);
            unset(self::$exited_pid_status[$pid]);
        } else {
            self::$child_pids[$pid] = true;
        }

        // TODO: Use http://php.net/manual/en/book.inotify.php if available, watch all directories if available.
        // Daemon continues to execute.
        Daemon::debugf("Created a child pid %d", $pid);
    }

    /**
     * @return void
     */
    public static function handleBecomingChildAnalysisProcess()
    {
        self::$child_pids = [];
    }

    /**
     * Reloads the file path list.
     * @param ?array $file_names
     * @return void
     */
    public static function reloadFilePathListForDaemon(CodeBase $code_base, \Closure $file_path_lister, array $file_mapping_contents, array $file_names = null)
    {
        $old_count = $code_base->getParsedFilePathCount();

        $file_list = $file_path_lister(true);

        if (Config::getValue('consistent_hashing_file_order')) {
            // Parse the files in lexicographic order.
            // If there are duplicate class/function definitions,
            // this ensures they are added to the maps in the same order.
            sort($file_list, SORT_STRING);
        }

        $changed_or_added_files = $code_base->updateFileList($file_list, $file_mapping_contents, $file_names);
        // Daemon::debugf("Parsing modified files: New files = %s", StringUtil::jsonEncode($changed_or_added_files));
        if (count($changed_or_added_files) > 0 || $code_base->getParsedFilePathCount() !== $old_count) {
            // Only clear memoizations if it is determined at least one file to parse was added/removed/modified.
            // - file path count changes if files were deleted or added
            // - changed_or_added_files has an entry for every added/modified file.
            // (would be 0 if a client analyzes one file, then analyzes a different file)
            Type::clearAllMemoizations();
        }
        // A progress bar doesn't make sense in a daemon which can theoretically process multiple requests at once.
        foreach ($changed_or_added_files as $file_path) {
            // Kick out anything we read from the former version
            // of this file
            $code_base->flushDependenciesForFile($file_path);

            // If the file is gone, no need to continue
            $real = realpath($file_path);
            if ($real === false || !file_exists($real)) {
                Daemon::debugf("file $file_path does not exist");
                continue;
            }
            Daemon::debugf("Parsing %s yet again", $file_path);
            try {
                // Parse the file
                Analysis::parseFile($code_base, $file_path, false, $file_mapping_contents[$file_path] ?? null);
            } catch (\Throwable $throwable) {
                error_log(sprintf("Analysis::parseFile threw %s for %s: %s\n%s", get_class($throwable), $file_path, $throwable->getMessage(), $throwable->getTraceAsString()));
            }
        }
        Daemon::debugf("Done parsing modified files");
    }

    /**
     * Substitutes files. We assume that the original file path exists already, and reject it if it doesn't.
     * (i.e. it was returned by $file_path_lister in the past)
     *
     * @param array<string,string> $temporary_file_mapping_contents
     * @return void
     */
    private static function applyTemporaryFileMappingForParsePhase(CodeBase $code_base, array $temporary_file_mapping_contents)
    {
        if (count($temporary_file_mapping_contents) === 0) {
            return;
        }

        // too verbose
        Daemon::debugf("Parsing temporary file mapping contents: New contents = %s", StringUtil::jsonEncode($temporary_file_mapping_contents));

        $changes_to_add = [];
        foreach ($temporary_file_mapping_contents as $file_name => $contents) {
            if ($code_base->beforeReplaceFileContents($file_name)) {
                $changes_to_add[$file_name] = $contents;
            }
        }
        Daemon::debugf("Done setting temporary file contents: Will replace contents of the following files: %s", StringUtil::jsonEncode(array_keys($changes_to_add)));
        if (count($changes_to_add) === 0) {
            return;
        }
        Type::clearAllMemoizations();

        foreach ($changes_to_add as $file_path => $new_contents) {
            // Kick out anything we read from the former version
            // of this file
            $code_base->flushDependenciesForFile($file_path);

            // If the file is gone, no need to continue
            $real = realpath($file_path);
            if ($real === false || !file_exists($real)) {
                Daemon::debugf("file $file_path no longer exists on disk, but we tried to replace it?");
                continue;
            }
            Daemon::debugf("Parsing temporary file instead of %s", $file_path);
            try {
                // Parse the file
                Analysis::parseFile($code_base, $file_path, false, $new_contents);
            } catch (\Throwable $throwable) {
                error_log(sprintf("Analysis::parseFile threw %s for %s: %s\n%s", get_class($throwable), $file_path, $throwable->getMessage(), $throwable->getTraceAsString()));
            }
        }
    }
}
