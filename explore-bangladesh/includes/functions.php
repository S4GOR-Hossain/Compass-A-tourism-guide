<?php
/**
 * Small shared helpers used across pages.
 */

if (!function_exists('truncateText')) {
    /**
     * UTF-8 safe text truncation. Uses mb_strimwidth when the mbstring
     * extension is available, otherwise falls back to a plain substr
     * (still safe for our mostly-ASCII sample content).
     */
    function truncateText(string $text, int $length = 100, string $suffix = '…'): string
    {
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $length, $suffix);
        }
        return strlen($text) > $length ? substr($text, 0, $length) . $suffix : $text;
    }
}
