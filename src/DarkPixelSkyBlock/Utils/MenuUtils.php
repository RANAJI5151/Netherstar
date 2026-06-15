<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Utils;

use pocketmine\utils\TextFormat;

/**
 * MenuUtils
 *
 * Stateless utility helpers for GUI menus.
 */
final class MenuUtils {

    /** Prevent instantiation — static utility class only. */
    private function __construct() {}

    /**
     * Translate & colour-code a string using both § and & prefixes.
     */
    public static function colorize(string $text): string {
        return TextFormat::colorize(str_replace("§", "§", $text));
    }

    /**
     * Build a progress bar string for display in item lore.
     *
     * @param  float  $current   Current value
     * @param  float  $max       Maximum value
     * @param  int    $length    Number of bar segments
     * @param  string $filled    Character/colour for filled sections
     * @param  string $empty     Character/colour for empty sections
     */
    public static function progressBar(
        float  $current,
        float  $max,
        int    $length  = 20,
        string $filled  = "§a|",
        string $empty   = "§7|"
    ): string {
        if ($max <= 0) return str_repeat($empty, $length);

        $ratio    = min(1.0, $current / $max);
        $filledN  = (int) round($ratio * $length);
        $emptyN   = $length - $filledN;

        return str_repeat($filled, $filledN) . str_repeat($empty, $emptyN);
    }

    /**
     * Format a large number with K/M/B suffix for compact display.
     */
    public static function compactNumber(float $number): string {
        return match (true) {
            $number >= 1_000_000_000 => round($number / 1_000_000_000, 1) . "B",
            $number >= 1_000_000     => round($number / 1_000_000,     1) . "M",
            $number >= 1_000         => round($number / 1_000,         1) . "K",
            default                  => (string) (int) $number,
        };
    }

    /**
     * Clamp a slot index to the valid range for a given inventory size.
     */
    public static function clampSlot(int $slot, int $size = 54): int {
        return max(0, min($size - 1, $slot));
    }

    /**
     * Convert a level integer to a Roman numeral string (up to 50).
     */
    public static function toRoman(int $level): string {
        $map = [
            50 => "L", 40 => "XL", 10 => "X", 9 => "IX",
            5  => "V", 4  => "IV", 1  => "I",
        ];
        $result = "";
        foreach ($map as $value => $numeral) {
            while ($level >= $value) {
                $result .= $numeral;
                $level  -= $value;
            }
        }
        return $result ?: "0";
    }
}
