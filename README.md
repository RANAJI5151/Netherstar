# DarkPixelSkyBlock

A comprehensive Hypixel SkyBlock menu system for PocketMine-MP Bedrock Edition. Provides an interactive GUI system with multiple menu screens for profile management, skills, collections, equipment, and more.

## Version

**2.0.0** — PocketMine-MP API 5.0.0+

## Requirements

- **PocketMine-MP 5.0.0+** (API 5.0.0 or higher)
- **PHP 8.1+**

### Optional Dependencies
- **EconomyAPI** — For external economy integration (soft dependency)

## Installation

1. Download the latest release of `DarkPixelSkyBlock.phar`
2. Place it in your `plugins/` folder
3. Restart your server
4. Configuration files will be generated automatically in `plugins/DarkPixelSkyBlock/`

## Features

- **Multiple Menu Screens** — Main Menu, Profile, Skills, Collections, Recipe Book, Equipment, Fast Travel, Settings, Wardrobe, Storage, and Quest Menu
- **Menu Item System** — Hotbar menu item with right-click detection and inventory protection
- **Configurable UI** — Customize menus via YAML configuration files
- **Data Storage** — Multiple storage providers: JSON, YAML, and SQLite
- **Economy Integration** — Support for both EconomyAPI and internal economy systems
- **Sound System** — Built-in UI sound effects for menu interactions
- **Profile System** — Player profile management and data persistence
- **Auto-Save** — Automatic data saving with configurable intervals

## Configuration

Configuration files are located in `plugins/DarkPixelSkyBlock/resources/`:

- `config.yml` — Main plugin configuration
- `menus.yml` — Menu layout and button definitions
- `items.yml` — Item configuration and definitions
- `messages.yml` — Plugin messages and locale strings

## Commands

| Command | Aliases | Permission | Description |
|---------|---------|-----------|-------------|
| `/sbmenu` | `/skyblock`, `/sb` | `darkpixelskyblock.command.sbmenu` | Open the SkyBlock Menu |
| `/givemenuitem [player]` | `/givesb` | `darkpixelskyblock.command.givemenuitem` | Give the SkyBlock menu item to a player |

## Permissions

| Permission | Default | Description |
|------------|---------|-------------|
| `darkpixelskyblock.command.sbmenu` | `true` | Allow using the /sbmenu command |
| `darkpixelskyblock.command.givemenuitem` | `op` | Allow using the /givemenuitem command |
| `darkpixelskyblock.bypass.restrictions` | `op` | Bypass SkyBlock item restrictions |
| `darkpixelskyblock.admin` | `op` | Full admin access to DarkPixelSkyBlock |

## Supported Menu Screens

- **Main Menu** — Primary hub for all menu functions
- **Profile Menu** — Player profile information
- **Skills Menu** — Skill tracking and management
- **Collections Menu** — Collection tracking
- **Equipment Menu** — Equipment management
- **Wardrobe Menu** — Cosmetic outfit management
- **Storage Menu** — Storage access
- **Fast Travel Menu** — Quick travel locations
- **Quest Menu** — Quest tracking and logs
- **Recipe Book Menu** — Recipe browsing
- **Settings Menu** — User preferences and toggles

## Data Providers

The plugin supports multiple storage backends:
- **JSON** — File-based JSON storage
- **YAML** — File-based YAML storage
- **SQLite** — Embedded database storage

## Author

- **DarkPixel**

## Links

- **GitHub** — https://github.com/DarkPixel/DarkPixelSkyBlock
- **Website** — https://github.com/DarkPixel/DarkPixelSkyBlock
