# Ninja Saga Backend (Laravel 12+)

This project is a modern backend replacement for the classic Ninja Saga game, rebuilt using **PHP 8.2** and **Laravel 12.x**. It utilizes the **sabre/amf** library to securely serve AMF3 (Action Message Format) endpoints to the ActionScript 3 (Flash) game client.

---

## Features & Systems Implemented

The architecture consists of highly modularized proxy services ensuring rapid development, clear separation of concerns, and stable gameplay.

### Core Systems
- **Character Authentication:** Complete signup, login, and secure session management. Uses multi-login detection.
- **AMF Gateway:** Full `sabre/amf` integration mapping client calls accurately to application logic.
- **Inventory Management:** Centralized asset injection for Items, Currencies, Pets, EXP, and custom reward bags.
- **Game Data Parsing:** Smart caching and loading of legacy unstructured JSON datasets to preserve 512MB shared hosting server constraints.

### Combat & Abilities
- **PvP Service:** Advanced matchmaking, live Battle Activity reports, Trophy rankings, global leaderboards, and detailed Battle Details logging.
- **Talents & Senjutsu:** Implements Bloodline Limit (Talent) discovery, Sage Mode (Senjutsu) unlocks, point upgrades, and skill allocations.
- **Pets Service:** Purchase, equip/unequip, rename, and pet skill learning logic.
- **Scroll of Wisdom:** Specific mechanics to discover elemental skill chains automatically sorting by the most advanced tiers available.

### Special Events & Shops
- **Mysterious Market:** Randomly generated daily rotating discount item stores.
- **Event Services:** Eudemon Garden, Dragon Hunt, Justice Event 2024, Valentine Event 2026, Monster Hunter 2023, and global Giveaways.
- **Special Deals:** Timed global active deals, automated VIP/Token currency purchases.
- **Daily Giveaways/Draws:** Daily Roulette, Daily Scratch systems configured with varying rarities.

---

## Tech Stack 

- **Language:** PHP 8.2+ 
- **Framework:** Laravel 12.0+
- **Database:** MySQL / SQLite
- **Communication Protocol:** AMF0 / AMF3 (`sabre/amf`)
- **Client End:** ActionScript 3 / Flash / AIR (`/actionscript` is untracked to keep repository light)

---

## Local Development & Setup

### Requirements
- PHP >= 8.2
- Composer
- Node.js & npm (For asset compilation if admin panels exist)
- A MySQL/MariaDB daemon running.

### Quick Start Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/klouat/ns.git
   cd ns
   ```

2. **Run Composer Setup:**
   Run the setup script defined in `composer.json` which handles `.env` creation, key generation, migrations, and package installation:
   ```bash
   composer run setup
   ```

3. **Configure your Database:**
   Ensure your `.env` is configured properly (e.g. `DB_CONNECTION=mysql`, `DB_DATABASE=sagessd`).

4. **Serve the Application:**
   Run the local development server:
   ```bash
   php artisan serve
   ```
   > By default, the AMF gateway is likely accessible at `http://localhost:8000/Gateway.php` or `http://localhost:8000/api/amf` (depending on the router configuration).

---

## Project Structure High-level

```text
├── app/
│   ├── Helpers/          # Centralized mechanics (GameDataHelper, ItemHelper, ElementSkillHelper)
│   ├── Models/           # Eloquent ORMs for all tables (Characters, Items, Giveaway, Mail, etc.)
│   └── Services/         
│       └── Amf/          # AMF exposed classes directly resolving client requests.
│           ├── SystemLoginService.php
│           ├── CharacterService.php
│           ├── EventsService.php
│           ├── PvPService/          # Sub-divided modular action services
│           ├── PetService/          # Sub-divided modular action services 
│           └── ... (Other Services)
├── database/             # Migrations and Seeders 
└── actionscript/         # (Untracked) Raw Flash AS3 project files
```

---

## Contribution Guidelines

This project leverages a strict separation of concerns logic inside the `app/Services/Amf` namespace.
- **Monolith Destruction:** Features are grouped logically into Proxy files (e.g. `PvPService.php`).
- **Single Responsibility Principals:** Actual AMF execution logic lives inside decoupled individual service classes (e.g. `app/Services/Amf/PvPService/PvPStatsService.php`).
- **AMF Output Arrays:** When crafting features, note that `sabre/amf` requires Objects mapping exactly to AS3 typed object names.
