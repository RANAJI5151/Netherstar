<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Utils;

use pocketmine\utils\TextFormat;

/**
 * TextUtils
 *
 * Stateless helpers for text/string formatting used throughout the plugin.
 */
final class TextUtils {

    private function __construct() {}

    /**
     * Strip all Minecraft formatting codes from a string.
     */
    public static function strip(string $text): string {
        return TextFormat::clean($text);
    }

    /**
     * Translate & colour codes (both § and &) in a string.
     */
    public static function colorize(string $text): string {
        return TextFormat::colorize($text);
    }

    /**
     * Truncate a string to at most $maxLength characters, appending
     * $suffix (e.g. "…") if truncation occurred.
     */
    public static function truncate(string $text, int $maxLength, string $suffix = "…"): string {
        $clean = self::strip($text);
        if (strlen($clean) <= $maxLength) {
            return $text;
        }
        return substr($clean, 0, $maxLength - strlen($suffix)) . $suffix;
    }

    /**
     * Pad a string on the right with spaces to a given display width
     * (ignoring colour codes).
     */
    public static function padRight(string $text, int $width, string $pad = " "): string {
        $len  = strlen(self::strip($text));
        $diff = $width - $len;
        return $diff > 0 ? $text . str_repeat($pad, $diff) : $text;
    }

    /**
     * Format a Unix timestamp as a human-readable "X ago" string.
     */
    public static function timeAgo(int $timestamp): string {
        $diff    = time() - $timestamp;
        $seconds = max(0, $diff);

        return match (true) {
            $seconds < 60     => $seconds . "s ago",
            $seconds < 3600   => (int) ($seconds / 60)   . "m ago",
            $seconds < 86400  => (int) ($seconds / 3600)  . "h ago",
            $seconds < 604800 => (int) ($seconds / 86400) . "d ago",
            default           => date("Y-m-d", $timestamp),
        };
    }

    /**
     * Convert a snake_case string to Title Case.
     * e.g. "skyblock_level" → "Skyblock Level"
     */
    public static function snakeToTitle(string $snake): string {
        return ucwords(str_replace("_", " ", $snake));
    }

    /**
     * Build a coloured coin display string.
     * e.g. 1234567 → "§61,234,567 Coins"
     */
    public static function formatCoins(float $amount, string $symbol = "§6", string $unit = "Coins"): string {
        return $symbol . number_format($amount) . " §7" . $unit;
    }
}
