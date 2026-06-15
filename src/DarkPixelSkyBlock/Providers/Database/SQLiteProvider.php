<?php

declare(strict_types=1);

namespace DarkPixelSkyBlock\Providers\Database;

use DarkPixelSkyBlock\Main;
use SQLite3;
use SQLite3Result;
use Throwable;

/**
 * SQLiteProvider
 *
 * Stores all player profiles in a single SQLite database file at:
 *   plugin_data/DarkPixelSkyBlock/data/players.db
 *
 * All operations are wrapped in try-catch blocks.
 * $db is nullable — if the database fails to open, every method returns
 * a safe no-op / empty value and logs the error instead of crashing.
 */
final class SQLiteProvider {

    private ?SQLite3 $db = null;

    public function __construct(private readonly Main $plugin) {
        // Validate that the SQLite3 extension is available before proceeding
        if (!class_exists(SQLite3::class)) {
            $plugin->getLogger()->critical(
                "SQLiteProvider: PHP SQLite3 extension is not available! " .
                "Switch to the 'json' or 'yaml' database provider in config.yml."
            );
            return;
        }

        try {
            $dataDir = $plugin->getDataFolder() . "data" . DIRECTORY_SEPARATOR;
            if (!is_dir($dataDir)) {
                if (!mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
                    throw new \RuntimeException("Failed to create data directory: {$dataDir}");
                }
            }

            $dbPath    = $dataDir . "players.db";
            $this->db  = new SQLite3($dbPath);
            $this->db->enableExceptions(true);
            $this->initSchema();

            $plugin->getConfigManager()->debugLog("SQLiteProvider opened: {$dbPath}");
        } catch (Throwable $e) {
            $plugin->getLogger()->critical(
                "SQLiteProvider: Failed to open database — " . $e->getMessage()
            );
            $plugin->getLogger()->critical(
                "Player data will NOT be persisted this session. " .
                "Check file permissions or switch to the 'json' provider."
            );
            $this->db = null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SCHEMA
    // ─────────────────────────────────────────────────────────────────────────

    private function initSchema(): void {
        if ($this->db === null) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS players (
                name    TEXT PRIMARY KEY,
                data    TEXT NOT NULL DEFAULT '{}',
                updated INTEGER NOT NULL DEFAULT 0
            )"
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROVIDER METADATA
    // ─────────────────────────────────────────────────────────────────────────

    public function getName(): string { return "SQLite"; }

    /** True when the database is open and usable. */
    public function isAvailable(): bool { return $this->db !== null; }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function load(string $playerName): array {
        if ($this->db === null) return [];

        try {
            $stmt = $this->db->prepare("SELECT data FROM players WHERE name = :name");
            if ($stmt === false) return [];

            $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
            $result = $stmt->execute();

            if (!$result instanceof SQLite3Result) return [];

            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row === false) return [];

            $decoded = json_decode((string) $row["data"], true);
            return is_array($decoded) ? $decoded : [];
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "SQLiteProvider::load({$playerName}) failed — " . $e->getMessage()
            );
            return [];
        }
    }

    /** @param array<string, mixed> $data */
    public function save(string $playerName, array $data): void {
        if ($this->db === null) return;

        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $stmt = $this->db->prepare(
                "INSERT INTO players (name, data, updated) VALUES (:name, :data, :ts)
                 ON CONFLICT(name) DO UPDATE SET data = :data, updated = :ts"
            );
            if ($stmt === false) return;

            $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
            $stmt->bindValue(":data", $json,        SQLITE3_TEXT);
            $stmt->bindValue(":ts",   time(),        SQLITE3_INTEGER);
            $stmt->execute();
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "SQLiteProvider::save({$playerName}) failed — " . $e->getMessage()
            );
        }
    }

    public function delete(string $playerName): void {
        if ($this->db === null) return;

        try {
            $stmt = $this->db->prepare("DELETE FROM players WHERE name = :name");
            if ($stmt === false) return;

            $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
            $stmt->execute();
        } catch (Throwable $e) {
            $this->plugin->getLogger()->error(
                "SQLiteProvider::delete({$playerName}) failed — " . $e->getMessage()
            );
        }
    }

    public function exists(string $playerName): bool {
        if ($this->db === null) return false;

        try {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM players WHERE name = :name LIMIT 1"
            );
            if ($stmt === false) return false;

            $stmt->bindValue(":name", $playerName, SQLITE3_TEXT);
            $result = $stmt->execute();

            return $result instanceof SQLite3Result &&
                   $result->fetchArray(SQLITE3_NUM) !== false;
        } catch (Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIFECYCLE
    // ─────────────────────────────────────────────────────────────────────────

    public function __destruct() {
        if ($this->db !== null) {
            try {
                $this->db->close();
            } catch (Throwable) {
                // Ignore close errors on shutdown
            }
        }
    }
}
