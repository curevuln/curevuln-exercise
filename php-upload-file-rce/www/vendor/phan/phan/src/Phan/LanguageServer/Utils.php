<?php declare(strict_types=1);

namespace Phan\LanguageServer;

use InvalidArgumentException;
use Sabre\Event\Loop;
use Throwable;

/**
 * Utils that are useful for implementing a language server.
 *
 * Taken from code by Felix Frederick Becker
 *
 * Source: https://github.com/felixfbecker/php-language-server
 *
 * - Mostly from that project's src/utils.php
 */
class Utils
{
    /**
     * Causes the sabre event loop to crash, for debugging.
     *
     * E.g. this is called if there is an unrecoverable error elsewhere.
     *
     * @return void
     * @suppress PhanUnreferencedPublicMethod
     */
    public static function crash(Throwable $err)
    {
        Loop\nextTick(function () use ($err) {
            // @phan-suppress-next-line PhanThrowTypeAbsent this is meant to crash the loop for debugging.
            throw $err;
        });
    }

    /**
     * Transforms an absolute file path into a URI as used by the language server protocol.
     *
     * @param string $filepath
     * @return string
     */
    public static function pathToUri(string $filepath) : string
    {
        // TODO: Make the return value of str_replace depend on the param value
        $filepath = \trim(\str_replace('\\', '/', $filepath), '/');
        $parts = \explode('/', $filepath);
        // Don't %-encode the colon after a Windows drive letter
        $first = (string)\array_shift($parts);
        if (substr($first, -1) !== ':') {
            $first = \rawurlencode($first);
        }
        $parts = \array_map('rawurlencode', $parts);
        array_unshift($parts, $first);
        $filepath = \implode('/', $parts);
        return 'file:///' . $filepath;
    }

    /**
     * Transforms URI into an absolute file path
     *
     * @param string $uri
     * @return string
     * @throws InvalidArgumentException
     */
    public static function uriToPath(string $uri) : string
    {
        $fragments = \parse_url($uri);
        if ($fragments === null || !isset($fragments['scheme']) || $fragments['scheme'] !== 'file') {
            throw new InvalidArgumentException("Not a valid file URI: $uri");
        }
        $filepath = \urldecode($fragments['path']);
        if (strpos($filepath, ':') !== false) {
            if ($filepath[0] === '/') {
                $filepath = (string)\substr($filepath, 1);
            }
            $filepath = \str_replace('/', '\\', $filepath);
        }
        return $filepath;
    }
}
