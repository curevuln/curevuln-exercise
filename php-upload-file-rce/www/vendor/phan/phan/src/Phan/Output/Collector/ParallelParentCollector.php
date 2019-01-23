<?php declare(strict_types=1);

namespace Phan\Output\Collector;

use AssertionError;
use Phan\IssueInstance;
use Phan\Output\IssueCollectorInterface;

/**
 * A ParallelParentCollector collects issues as normal proxying
 * them on to a given base collector, but will also listen to
 * a message queue for issues emitted by other processes (via
 * a ParallelChildCollector).
 */
class ParallelParentCollector implements IssueCollectorInterface
{
    const MESSAGE_TYPE_ISSUE = 1;

    /**
     * @var IssueCollectorInterface
     * All issues will be proxied to the base collector for
     * filtering and output.
     */
    private $base_collector;

    /**
     * @var Resource
     * A message queue that will be listened to for incoming
     * messages
     */
    private $message_queue_resource;

    /**
     * Create a ParallelParentCollector that will collect
     * issues via a message queue. You'll want to do the
     * real collection via
     * \Phan\Output\Collector\ParallelChildCollector.
     *
     * @param IssueCollectorInterface $base_collector
     * A collector must be given to which collected issues
     * will be passed
     */
    public function __construct(
        IssueCollectorInterface $base_collector
    ) {
        ParallelChildCollector::assertSharedMemoryCommunicationEnabled();

        $this->base_collector = $base_collector;

        // Create a message queue for this process group
        $message_queue_key = posix_getpgid(posix_getpid());
        $this->message_queue_resource =
            msg_get_queue($message_queue_key);

        // Listen for ALARMS that indicate we should flush
        // the queue
        pcntl_sigprocmask(SIG_UNBLOCK, [SIGUSR1], $old);
        pcntl_signal(SIGUSR1, function () {
            $this->readQueuedIssues();
        });
    }

    public function __destruct()
    {
        // Shut down and remove the queue
        $success = msg_remove_queue(
            $this->message_queue_resource
        );
        if (!$success) {
            // @phan-suppress-next-line PhanTypeSuspiciousStringExpression we're deliberately converting the resource to a string
            throw new AssertionError("Failed to remove queue with ID {$this->message_queue_resource}");
        }
    }

    /**
     * Collect issue
     * @param IssueInstance $issue
     * @return void
     */
    public function collectIssue(IssueInstance $issue)
    {
        $this->base_collector->collectIssue($issue);
    }

    /**
     * Read the entire queue and write all issues to the
     * base collector
     * @return void
     */
    public function readQueuedIssues()
    {
        // Get the status of the queue
        $status = msg_stat_queue(
            $this->message_queue_resource
        );

        // Read messages while there are still messages on
        // the queue
        while ($status['msg_qnum'] > 0) {
            $message = null;
            $message_type = 0;

            // Receive the message, populating $message by
            // reference
            if (false !== msg_receive(
                $this->message_queue_resource,
                self::MESSAGE_TYPE_ISSUE,
                $message_type,
                2048,
                $message,
                true
            )) {
                if (!($message instanceof IssueInstance)) {
                    throw new AssertionError("Messages must be of type IssueInstance.");
                }

                // Cast the message to an IssueInstance
                $this->collectIssue($message);
            } else {
                break;
            }

            $status = msg_stat_queue(
                $this->message_queue_resource
            );
        }
    }

    /**
     * Remove all collected issues (from the parse phase) for the given file paths.
     * Called from daemon mode.
     *
     * @param string[] $files - the relative paths to those files (@phan-unused-param)
     * @return void
     */
    public function removeIssuesForFiles(array $files)
    {
        return;  // Never going to be called - daemon mode isn't combined with parallel execution.
    }

    /**
     * @return array<int,IssueInstance>
     */
    public function getCollectedIssues() : array
    {
        // Read any available issues waiting on the
        // message queue
        $this->readQueuedIssues();

        // Return everything on the base collector
        return $this->base_collector->getCollectedIssues();
    }

    /**
     * This method has no effect on a ParallelParentCollector.
     */
    public function reset()
    {
    }
}
