# DarkPixelSkyBlock Changelog

## v2.0.1 — Audit fixes, PMMP 5.43.2 compatibility, and PHP 8.2 validation

### Audit Fixes
- Updated `plugin.yml` API version to `5.43.2` for PocketMine-MP 5.43.2 compatibility.
- Added defensive slot bounds checking for the locked hotbar menu item.
- Hardened menu item persistence: cleanup now scans player inventory, armor, and off-hand inventories.
- Added `PlayerDeathEvent` handling to prevent the SkyBlock menu item from dropping on death.
- Added safe JSON load/save error handling and atomic writes in `JsonProvider`.
- Added fallback from SQLite to JSON storage when SQLite is unavailable.
- Added fallback from EconomyAPI to internal economy when EconomyAPI is missing.
- Swapped direct `PLAYER_HEAD` usage in ProfileMenu for a safe player-head helper.
- Verified all PHP files compile cleanly under PHP 8.2 using Docker.

## v2.0.0 — Production Modernization & Poggit Compliance

### Critical Fixes
- **Version consistency** — all version strings unified to `2.0.0` across `plugin.yml`, `MainMenu.php`, `items.yml`, and `README.md`.
- **PMMP 5.x item API** — replaced deprecated `VanillaItems::RAW_FISH()` with `VanillaItems::COD()` in `CollectionsMenu.php` (PMMP 5.x renamed raw fish items to their cooked names).

### Poggit & Virion v3 Setup
- **Added `.poggit.yml`** — declares `muqsit/InvMenu/InvMenu` as a virion dependency with the exact published `4.7.4` virion build, enabling Poggit CI builds.
- **Aligned plugin API** — `plugin.yml` now declares PMMP API `5.41.0` to match the published InvMenu 4.7.4 virion requirement used by Poggit.
- **Removed all InvMenu plugin-dependency references** — `plugin.yml` has no `depend` or `softdepend` for InvMenu; it is strictly a virion.
- **Clean plugin.yml** — only `softdepend: [EconomyAPI]` remains for the optional economy bridge.

### Verified Compatibility
- PocketMine-MP 5.43.2
- PHP 8.2
- InvMenu 4.7.x (virion via DevTools)
- EconomyAPI (optional soft dependency)

## v1.1.0 — PMMP 5.43.2 Compatibility & Stability

### Critical Fixes
- **Removed nested `children` permission declarations** from `plugin.yml` — PMMP 5.43.2 does not support nested permission `children` blocks, which would cause the plugin to fail validation on load.
- **Player head fallback** — `VanillaItems::PLAYER_HEAD()` is used with a graceful fallback to `NETHER_STAR` if unavailable on Bedrock.
- **InvMenu safety** — all menu creation is wrapped in try-catch with fallback to chat messages if the GUI cannot be opened.
- **SQLite error handling** — `SQLiteProvider` uses `getResult()` instead of `fetchArray(SQLITE3_ASSOC)` and has full error guards.

### Stability Improvements
- **Hotbar locking** — the SkyBlock Menu item is automatically restored to the configured slot after inventory events, preventing accidental loss.
- **Inventory protection** — menu items cannot be dropped, moved, or stored in containers.
- **Cooldown system** — prevents spam-opening menus with a configurable cooldown (default 500ms).
- **Silent restore** — `MenuRestoreTask` restores the menu item without chat messages.
- **Settings toggle** — `SettingsMenu` supports live toggling of personal preferences (sound, tutorial, animations) via ProfileManager.
- **Debug mode** — `config.yml` includes a `debug` flag for verbose logging.

### Verified Compatibility
- PocketMine-MP 5.43.2
- PHP 8.2
- InvMenu virion (latest, via DevTools)
- EconomyAPI (optional soft dependency)

## v1.0.0 — Initial Release

- Full SkyBlock Menu system with 16 buttons
- Profile, Skills, Collections, Recipe Book, Quest, Storage, Bank, Wardrobe, Equipment, Fast Travel, Settings submenus
- Hotbar menu item with right-click detection
- Configurable via `config.yml`, `menus.yml`, `items.yml`, `messages.yml`
- JSON / YAML / SQLite data providers
- Economy provider bridge (EconomyAPI or internal)
- Sound system with 8 built-in UI sounds
- All 40 source files + 4 resource files
