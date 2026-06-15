# DarkPixelSkyBlock

Hypixel SkyBlock Menu system for PocketMine-MP Bedrock Edition.

## Version

**2.0.0** — PMMP API 5.41.0+ / 5.43.2 compatible

## Requirements

### Required
- **PocketMine-MP 5.43.2+ (API 5.41.0+ required for the InvMenu virion)**
- **PHP 8.2**
- **DevTools** (for virion loading)
- **InvMenu virion** (library, not plugin)

### Optional
- **EconomyAPI** (soft dependency for external economy)

## Installation

1. Install **DevTools** plugin if you haven't already:
   - Download from [poggit.io/p/DevTools](https://poggit.io/p/DevTools)
   - Place `DevTools.phar` in your `plugins/` folder

2. Install **InvMenu virion**:
   - Download the latest `InvMenu.phar` virion from [poggit.io/p/InvMenu](https://poggit.io/p/InvMenu)
   - Place `InvMenu.phar` in your server root **next to your `plugins/` folder** (NOT inside `plugins/`)
   - DevTools will automatically inject the virion into any plugin that needs it

3. Install **DarkPixelSkyBlock**:
   - Place `DarkPixelSkyBlock.phar` in your `plugins/` folder
   - Restart the server

## What NOT to do

- **Do NOT** install InvMenu as a standalone plugin (`.phar` inside `plugins/`). This plugin uses InvMenu as a **virion/library**, not a plugin dependency.
- **Do NOT** add `depend: [InvMenu]` or `softdepend: [InvMenu]` — the virion is injected at build time by DevTools.

## Features

- **16-button SkyBlock Menu** with Profile, Skills, Collections, Recipe Book, Trades, Quest Log, Calendar, Storage, Bank, Pets, Crafting, Wardrobe, Equipment, Fast Travel, Settings
- **Hotbar menu item** with right-click detection
- **Inventory protection** — menu item cannot be dropped, moved, or stored
- **Configurable** via `config.yml`, `menus.yml`, `items.yml`, `messages.yml`
- **Data providers** — JSON, YAML, or SQLite
- **Economy bridge** — EconomyAPI or internal economy
- **Sound system** with 8 built-in UI sounds
- **Settings toggles** — sound effects, tutorial hints, menu animations

## Permissions

| Permission | Default | Description |
|------------|---------|-------------|
| `darkpixelskyblock.command.sbmenu` | `true` | Use /sbmenu |
| `darkpixelskyblock.command.givemenuitem` | `op` | Use /givemenuitem |
| `darkpixelskyblock.bypass.restrictions` | `op` | Bypass item restrictions |
| `darkpixelskyblock.admin` | `op` | Full admin access |

## Commands

| Command | Alias | Description |
|---------|-------|-------------|
| `/sbmenu` | `/skyblock`, `/sb` | Open the SkyBlock Menu |
| `/givemenuitem [player]` | `/givesb` | Give the menu item to a player |

## Support

- GitHub: https://github.com/DarkPixel/DarkPixelSkyBlock
- PMMP Forums: [Search DarkPixelSkyBlock](https://forums.pmmp.io)
