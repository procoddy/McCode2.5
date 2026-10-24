# McCode 2.5

A modular rewrite of McCodes on plain PHP 8.2+, with server-rendered pages enhanced by [HTMX](https://htmx.org) and styled with [Tailwind CSS](https://tailwindcss.com). There are no PHP framework dependencies.

---

## Quick start

```bash
cd /var/www/html/McCode2.5
cp .env.example .env                      # then edit the DB_* values
php bin/migrate.php                       # create tables
php bin/seed.php                          # add admin + test players
npm install && npm run build              # only needed when you change styles (built files are included)
php -S 0.0.0.0:8000 -t public public/index.php
```

Open **http://localhost:8000** and log in as `admin` with the password `password`, or create an account at `/register`.

---

## Command reference

### Database setup (MySQL, one time)

On Ubuntu, MySQL `root` can only log in through `sudo`, so the app uses its own user:

```bash
sudo mysql -e "
CREATE DATABASE IF NOT EXISTS mccode25 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'mccode'@'localhost' IDENTIFIED BY 'your-password';
CREATE USER IF NOT EXISTS 'mccode'@'127.0.0.1' IDENTIFIED BY 'your-password';
GRANT ALL PRIVILEGES ON mccode25.* TO 'mccode'@'localhost';
GRANT ALL PRIVILEGES ON mccode25.* TO 'mccode'@'127.0.0.1';
FLUSH PRIVILEGES;"
```

Then set the same values in `.env`:

```ini
DB_DATABASE=mccode25
DB_USERNAME=mccode
DB_PASSWORD=your-password
```

Start or check MySQL:

```bash
sudo service mysql start
sudo service mysql status
```

### Migrations

| Command | What it does |
|---------|--------------|
| `php bin/migrate.php` | Runs every migration in `database/migrations/` that has not run yet |
| `php bin/migrate.php --rollback` | Undoes the most recent batch of migrations. **Deletes those tables and their data** |
| `composer migrate` | Same as `php bin/migrate.php` (needs Composer) |

Output:

```
Migrated: 2026_10_24_000001_create_users_table
Successfully migrated (1).
```

`Nothing to migrate. Database is up to date.` means every migration has already run. Applied migrations are recorded in the `migrations` table, and each run of the command is one "batch".

**Start over with a fresh database** by rolling back until nothing is left, then migrating and seeding again:

```bash
php bin/migrate.php --rollback
php bin/migrate.php
php bin/seed.php
```

### Seeders

| Command | What it does |
|---------|--------------|
| `php bin/seed.php` | Runs `DatabaseSeeder`, which calls every other seeder |
| `php bin/seed.php UserSeeder` | Runs one seeder by class name |
| `composer seed` | Same as `php bin/seed.php` (needs Composer) |

Output: `Database seeded successfully.`

`UserSeeder` creates `admin` / `password` (role `admin`) plus 5 random players with the password `password`. It does nothing if `admin` already exists, so running it twice is safe.

> The seeded passwords are for local development only. Never run the seeders on a live server.

### Front end (Tailwind + HTMX)

| Command | What it does |
|---------|--------------|
| `npm install` | Installs Tailwind CLI and HTMX into `node_modules/` (one time) |
| `npm run build` | Builds minified CSS and copies HTMX + `app.js` into `public/assets/` |
| `npm run dev` | Rebuilds the CSS on every save; leave it running while you edit views |
| `npm run build:css` | CSS only |
| `npm run build:js` | JavaScript only |

The built files in `public/assets/` are committed, so a server only needs PHP. Run `npm run build` after changing any classes in views or stubs, otherwise new classes have no styles.

### Code generators

| Command | What it does |
|---------|--------------|
| `php bin/make.php resource <Name>` | Full CRUD resource: controller, model, repository, form request, 5 views, migration, route and sidebar link |
| `php bin/make.php resource <Name> --module=<Module> --fields="..."` | Same, inside a chosen module and with your own columns |
| `php bin/make.php migration <snake_name>` | Empty migration to fill in (e.g. adding a column) |
| `php bin/make.php` | Shows usage help |

Example:

```bash
php bin/make.php resource Item --module=Inventory \
    --fields="name:string,quantity:int,description:text,is_tradable:bool"
php bin/migrate.php
```

Then open **http://localhost:8000/items**. See [Generating a resource](#generating-a-resource) for details.

### Cron (game ticks)

| Command | Schedule | What it does |
|---------|----------|--------------|
| `php bin/cron.php five-minutes` | every 5 minutes | Regenerates energy, brave, HP and will (rates in `config/game.php` → `regen`) |
| `php bin/cron.php daily` | once a day | Pays bank and cyber bank interest |

Jail, hospital, course and donator times are stored as end dates, so nothing has to count them down. Add to `crontab -e`:

```cron
*/5 * * * * cd /var/www/html/McCode2.5 && php bin/cron.php five-minutes >> storage/logs/cron.log 2>&1
0 0 * * *   cd /var/www/html/McCode2.5 && php bin/cron.php daily >> storage/logs/cron.log 2>&1
```

### Development server

| Command | URL |
|---------|-----|
| `php -S 0.0.0.0:8000 -t public public/index.php` | http://localhost:8000 |

The server runs until you press `Ctrl+C`. On WSL, if `localhost` does not reach the server from the Windows browser, use the WSL IP address instead (`hostname -I`).

### Tests

| Command | What it does |
|---------|--------------|
| `composer test` | Runs the whole suite |
| `vendor/bin/phpunit` | Same, after `composer install` |
| `vendor/bin/phpunit --testsuite Unit` | Unit tests only (also `Feature`, `Integration`) |
| `vendor/bin/phpunit --filter RegisterTest` | One test class or method |
| `INTEGRATION_DB=1 vendor/bin/phpunit --testsuite Integration` | Tests against the real MySQL database from `.env` |

Without Composer, download the PHPUnit phar once and run it from the project root:

```bash
curl -sSL -o ~/phpunit.phar https://phar.phpunit.de/phpunit-11.phar
php ~/phpunit.phar
```

Unit and feature tests use in-memory fakes and never touch MySQL; any query that slips through fails the test. The integration suite (`tests/Integration`) checks the real SQL. Each test runs inside a transaction that is rolled back, so the database is left exactly as it was. It is skipped unless `INTEGRATION_DB=1` is set.

### Composer (optional)

The app runs without Composer thanks to a fallback autoloader in `bootstrap/autoload.php`.

```bash
composer install             # installs PHPUnit, builds the optimized autoloader
composer dump-autoload       # after adding new namespaces to composer.json
```

### Maintenance

| Command | What it does |
|---------|--------------|
| `tail -f storage/logs/app-$(date +%F).log` | Watch today's log |
| `rm -f storage/sessions/sess_*` | Log everyone out |
| `rm -rf storage/cache/*` | Clear the cache and rate limits (keeps `.gitkeep`) |
| `find . -name '*.php' -not -path './vendor/*' -print0 \| xargs -0 -n1 php -l \| grep -v 'No syntax'` | Syntax-check every PHP file |

---

## Generating a resource

```bash
php bin/make.php resource Item --module=Inventory --fields="name:string,quantity:int,description:text,is_tradable:bool"
```

```
Created: app/Modules/Inventory/Models/Item.php
Created: app/Modules/Inventory/Repositories/ItemRepository.php
Created: app/Modules/Inventory/Requests/ItemRequest.php
Created: app/Modules/Inventory/Controllers/ItemController.php
Created: resources/views/pages/items/index.php
Created: resources/views/pages/items/create.php
Created: resources/views/pages/items/edit.php
Created: resources/views/pages/items/show.php
Created: resources/views/pages/items/_form.php
Created: database/migrations/2026_10_24_120850_create_items_table.php
Updated: routes/web.php
Updated: resources/views/partials/sidebar.php

Resource created successfully.
Next: run `php bin/migrate.php`, then open /items
```

**Options**

| Option | Default | Meaning |
|--------|---------|---------|
| `<Name>` | (required) | Singular name in StudlyCase or snake_case: `Item`, `BankAccount`, `bank_account` |
| `--module=` | same as `<Name>` | Folder under `app/Modules/` |
| `--fields=` | `name:string` | Comma-separated `field:type` list |

**Field types**

| Type | Column | Validation | Form input |
|------|--------|------------|------------|
| `string` | `VARCHAR(255) NOT NULL` | required, max 255 chars | text box |
| `text` | `TEXT NOT NULL` | required | textarea (not shown in the list table) |
| `int` | `INT NOT NULL DEFAULT 0` | required, whole number | number box |
| `bool` | `TINYINT(1) NOT NULL DEFAULT 0` | true/false | checkbox |

Every table also gets `id`, `created_at` and `updated_at`.

**Names are derived from `<Name>`.** For `BankAccount`: table `bank_accounts`, URL `/bank-accounts`, views in `resources/views/pages/bank_accounts/`, classes `BankAccountController`, `BankAccount`, `BankAccountRepository`, `BankAccountRequest`.

**Routes.** The generator adds one line to `routes/web.php`, just above the `// make:resource routes` marker inside the `auth` group (so only logged-in players can reach it):

```php
$router->resource('/items', \App\Modules\Inventory\Controllers\ItemController::class);
```

That one line registers seven routes:

| Method | URL | Controller method | Page |
|--------|-----|-------------------|------|
| GET | `/items` | `index` | Paginated list |
| GET | `/items/create` | `create` | New form |
| POST | `/items` | `store` | Save new record |
| GET | `/items/{id}` | `show` | Detail page |
| GET | `/items/{id}/edit` | `edit` | Edit form |
| PUT | `/items/{id}` | `update` | Save changes |
| DELETE | `/items/{id}` | `destroy` | Delete (asks for confirmation) |

To make a resource staff-only, move its line into the `/admin` group in `routes/admin.php`. If you delete the marker comment, the generator prints the route line for you to paste instead.

**Safety.** If any target file already exists, nothing is written. Names that clash with view variables (`Title`, `User`, `Status`, ...) are rejected.

**Customising.** The templates live in `app/Console/stubs/`. Edit them to change what every future resource looks like. Generated files are ordinary code: add business rules in a `Services/` class, add permission checks in `Policies/`, and change the migration *before* running it.

**Removing a generated resource:** run `php bin/migrate.php --rollback` (only if its migration was the last one run), delete the generated files, and remove its lines from `routes/web.php` and `partials/sidebar.php`.

## Writing migrations and seeders by hand

### New migration

`php bin/make.php migration add_bio_to_users_table` creates an empty one, or create the file yourself. Name it `database/migrations/YYYY_MM_DD_HHMMSS_description.php`. Files run in filename order.

```php
<?php

declare(strict_types=1);

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->query('CREATE TABLE bank_accounts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            balance BIGINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT bank_accounts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Database $db): void
    {
        $db->query('DROP TABLE IF EXISTS bank_accounts');
    }
};
```

Then run `php bin/migrate.php`. Never edit a migration that has already run on a shared database; add a new one instead.

### New seeder

Create `database/seeders/BankSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;

final class BankSeeder
{
    public function run(Database $db): void
    {
        foreach ($db->fetchAll('SELECT id FROM users') as $user) {
            $db->insert('bank_accounts', ['user_id' => $user['id'], 'balance' => 0]);
        }
    }
}
```

Run it alone with `php bin/seed.php BankSeeder`, or add `(new BankSeeder())->run($db);` to `DatabaseSeeder` so the default seed includes it.

---

## Project layout

| Path | Purpose |
|------|---------|
| `app/Core` | Framework code: container, router, request/response, views, session, DB, validation, logging, error handling |
| `app/Http` | Middleware, form-request base class, response types |
| `app/Modules/<Feature>` | Business features. Each one has Controllers → Services → Repositories, plus Models, Requests and Policies |
| `app/Legacy` | Adapters that let old McCodes pages share the new DB connection and session |
| `bin/` | CLI entry points (`migrate.php`, `seed.php`, `make.php`); the command logic lives in `routes/console.php` |
| `app/Console` | Code generator and its templates (`stubs/`) |
| `bootstrap/` | Autoloader and application startup, shared by the web, CLI and tests |
| `config/` | App, database, session, security, cache and logging settings, read from `.env` |
| `routes/` | `web.php`, `admin.php` (HTTP) and `console.php` (CLI) |
| `resources/views` | Layouts (`app`, `guest`, `admin`, `fragment`), pages, components, partials, error pages |
| `resources/assets` | Tailwind entry CSS and `app.js` (sources; built into `public/assets`) |
| `database/` | Migrations, seeders, factories |
| `public/` | The **only** web root. `index.php` is the front controller |
| `storage/` | Logs, cache, sessions, uploads. Never web-accessible |
| `legacy/` | Original McCodes code, moved here until it is migrated |

## Routes

| Method | URL | Access | Purpose |
|--------|-----|--------|---------|
| GET | `/` | anyone | Redirects to `/dashboard` |
| GET / POST | `/login` | guests | Log in (5 attempts per minute) |
| GET / POST | `/register` | guests | Create an account (5 attempts per 10 minutes) |
| POST | `/logout` | logged in | Log out |
| GET | `/dashboard` | logged in | Player home |
| GET | `/dashboard/stats` | logged in | Stats block only (HTMX partial) |
| GET | `/announcements` | logged in | Announcements; viewing marks them read |
| GET | `/announcements/unread-count` | logged in | Sidebar badge (HTMX partial) |
| GET / POST | `/admin/announcements` | admins | Post announcements (HTMX form) |
| GET | `/bank`, `/cyber-bank` | logged in | Bank accounts (`POST …/open`, `…/deposit`, `…/withdraw`) |
| GET | `/crystal-temple` | logged in | Spend crystals (`POST …/refill`, `…/iq`, `…/money`) |
| GET / POST | `/crimes`, `/crimes/{id}` | logged in | Criminal centre; POST commits a crime |
| POST | `/register/check` | guests | Live username/email availability (HTMX) |
| GET | `/events` | logged in | Your notifications (sidebar badge shows unread) |
| GET | `/players`, `/players/online`, `/staff-list`, `/stats` | logged in | Player list, who's online, staff list, game statistics |
| GET | `/players/{id}` | logged in | Profile, with transfer/report links and staff info for staff |
| GET / POST | `/players/{id}/send/{bank,cyber}` | logged in | Bank / cyber bank transfer |
| GET / POST | `/players/{id}/report` | logged in | Report a player to staff |
| GET | `/admin/...` | staff | Staff panel (see below) |
| GET | `/admin` | admins | Staff panel |

## Request lifecycle

`public/index.php` → `bootstrap/app.php` → `Application::run()` → global middleware
(security headers, CSRF) → route middleware (`auth`, `guest`, `admin`, `throttle:5,1`) →
controller → `Response`.

Any `BaseRequest` subclass type-hinted in a controller action is validated before the action runs. On failure the user is redirected back with `$errors` and `old()` input, or receives a 422 response for JSON requests. Override `prepareForValidation()` to trim or normalise input first.

## Front end: HTMX and Tailwind

### How pages load

Every layout's `<body>` has `hx-boost="true" hx-target="#content"`, so ordinary links and forms become HTMX requests that replace only the `<main id="content">` area. The header, sidebar, scroll position and JavaScript state stay put. With JavaScript off, the same links and forms work as normal page loads.

`view()` decides what to send back:

| Request | Response |
|---------|----------|
| Normal browser request | Full page in its layout |
| HTMX request, same layout as the page on screen | Only the page content (`layouts/fragment.php`) plus `HX-Push-Url` so the address bar updates |
| HTMX request, different layout (e.g. login → dashboard) | Empty response with `HX-Redirect`, so the browser loads the full page; flash messages are kept |
| HTMX back/forward cache miss | Full page |
| `view('...', $data, layout: null)` | Just the template, for `hx-target` partial updates |

Each layout tells the server which layout is on screen with an `X-Layout` header (set in `hx-headers` on `<body>`, together with the CSRF token).

Redirects need no special handling. The browser follows them inside the HTMX request, so a failed form submission redirects back and the form is swapped in with its errors, while a successful one shows the next page.

### Writing pages

- **Title:** call `<?php $this->set('title', 'Bank') ?>` at the top of the page. It sets the browser tab title on full loads and HTMX swaps.
- **Partial update:** see the dashboard's Refresh button and `DashboardController::stats()`:

  ```html
  <button hx-get="/dashboard/stats" hx-target="#stats" hx-swap="outerHTML">Refresh</button>
  ```

  ```php
  return view('partials.dashboard-stats', [...], layout: null);
  ```

- **Confirmation:** add `hx-confirm="Are you sure?"` to a form or button.
- **Opt out:** add `hx-boost="false"` to a link that should always do a full page load (e.g. links to the staff panel, which uses another layout).
- **Errors:** 403, 404 and 422 responses are swapped into the page; other errors show a message in `#notifications` (see `resources/assets/js/app.js`). A thin bar at the top of the page shows while a request is running.
- **Security:** HTMX and app scripts are served from `/assets/js/`. The Content-Security-Policy allows only same-origin scripts, so inline `<script>` blocks and `onclick=` attributes are blocked; put JavaScript in `resources/assets/js/app.js`. HTMX's `hx-on` attributes are disabled for the same reason.

### Styling

Use Tailwind utility classes directly in views. A few shared classes are defined in `resources/assets/css/app.css`:

| Class | Use |
|-------|-----|
| `btn` + `btn-primary` / `btn-secondary` / `btn-danger` | Buttons and button-styled links |
| `input`, `input-invalid` | Text inputs and textareas |
| `card` | White panel with border |
| `link` | Accent-coloured text link |
| `alert` + `alert-success` / `alert-error` / `alert-info` / `alert-warning` | Messages |

The accent colour is `brand-500/600/700` in the `@theme` block of `app.css`; change those three values to re-theme the game. Dark mode follows the visitor's system setting via `dark:` classes.

Components in `resources/views/components/`: `button`, `alert`, `form-field` (text, email, password, number, textarea, checkbox), `modal`, `pagination`. The `form-field` component picks up validation errors for its field automatically.

## Migrating a legacy McCodes page

`app/Modules/Announcements` is the reference migration of the old `announcements.php`. Use it as the template for other pages.

| Legacy McCodes | New project |
|----------------|-------------|
| `require 'globals.php'` (DB connection, session, `$ir`, header) | Nothing to include. DB settings live in `.env` / `config/database.php`; any class that needs the database asks for `Database` in its constructor and the container provides it |
| `$ir`, `$userid` | `$request->attribute('user')` (a `User` model) on routes behind the `auth` middleware |
| `$db->query("... '{$userid}'")` | Repository method with `?` placeholders: `$this->db->fetch('... WHERE id = ?', [$id])` |
| Logic mixed into the page (`new_announcements` counter) | Service method (`AnnouncementService::feedFor()`) |
| `echo '<table>...'` | View template; every value through `e()` |
| `nl2br($text)` on raw input | `nl2br(e($text))`: escape first, so staff or player text can't inject HTML/JS |
| `$h->endpage()` | The layout does it |
| `staff_*.php` pages | Controller under the `/admin` routes (`auth` + `admin` middleware) |
| Columns added by hand in phpMyAdmin | A migration in `database/migrations/` |

Steps for each page:

1. **Migration:** create or alter the tables (`php bin/make.php migration ...`).
2. **Model:** one class per row type, built with `fromRow()`.
3. **Repository + interface:** all SQL for the feature. Bind the interface in `Application::registerServices()`.
4. **Service:** the game rules (costs, cooldowns, unread logic).
5. **Controller:** read input, call the service, return `view()`.
6. **View:** the HTML, using the components.
7. **Routes:** in `routes/web.php` (players) or `routes/admin.php` (staff).
8. **Test:** a feature test with an in-memory repository (see `tests/Feature/Announcements/`).

### Porting status

| Legacy file | Status |
|-------------|--------|
| `announcements.php` | Done: `Modules/Announcements` |
| `authenticate.php` | Done: `Modules/Auth` (login) |
| `bank.php`, `cyberbank.php` | Done: `Modules/Banking` (one service, both banks configured in `config/game.php`) |
| `crystaltemple.php` | Done: `Modules/CrystalTemple` |
| `criminal.php`, `docrime.php` | Done: `Modules/Crimes`. Item rewards wait for the Inventory module |
| `checkun.php`, `checkem.php` | Done: live checks on the register form (`/register/check`) |
| `staff.php`, `sglobals.php`, `smenu.php`, `staff_roles.php`, `staff_users.php`, `staffnotes.php`, `staff_logs.php` | Done: `Modules/Staff` (see Staff panel) |
| `staff_cities.php`, `staff_houses.php`, `staff_courses.php`, `staff_crimes.php`, `staff_items.php`, `staff_shops.php`, `staff_jobs.php` | Done: game-data editors (`config/staff_resources.php`). Item effects editor comes with Inventory |
| `staff_punit.php` | Federal jail done; mail/forum bans and IP search come with Mail/Forums |
| `viewuser.php`, `userlist.php`, `usersonline.php`, `stafflist.php`, `stats.php` | Done: `Modules/Players` |
| `sendcyber.php` (and bank equivalent) | Done: `/players/{id}/send/{bank,cyber}` |
| `attack*.php`, `battletent.php`, `staff_battletent.php`, `blacklist.php`, `cmarket.php`, `creategang.php`, `yourgang.php`, `staff_gangs.php` | Tables ready; Combat and Gangs modules next |
| `shops.php`, `unequip.php` | Need the Inventory module (shops and stock can already be set up by staff) |
| `slotsmachine.php`, `staff_forums.php`, `staff_polls.php`, `staff_special.php` | Later batches (casino, forums, polls, newspaper/mass mail/donator packs) |
| `willpotion.php`, `willpdone.php` | Waiting on the payments decision |
| `staff_fileviewer.php`, `upgrader.php`, `upgrade_head.php` | Not ported: the first lists every PHP file on the server; the upgrader runs raw SQL files from a web form. Migrations replace it |
| `donator.php`, `donatordone.php`, `dummy_account.php`, `demo_closed.php`, `dlarchive.php` | Waiting on decisions (payment provider, demo accounts, mail module) |
| `captcha_verify.php`, `check.php` | Not ported: rate limiting replaces the captcha; password strength belongs in the browser |
| `droptable.php` | Not ported: it drops a table and contains database credentials. Delete it from any server |

Security fixes made while porting: crime formulas no longer go through `eval()` (`App\Support\Formula` only understands arithmetic and stat names); staff-written text (announcements, crime texts) is escaped; money changes are single conditional `UPDATE`s, so double-clicking can't spend money twice; cyber bank withdrawals now deduct the full amount including the fee.

### Legacy database → new schema

Every table from the legacy `mccodes.sql` has a migration (`database/migrations/2026_10_25_*`). Names follow the project style (`snake_case`, `id` primary keys, `*_id` foreign keys, InnoDB with foreign key constraints, `DATETIME` instead of Unix-time integers).

| Legacy | New |
|--------|-----|
| `users.userid`, `crimes.crimeID`, `gangs.gangRESPECT`, … | `users.id`, `crimes.id`, `gangs.respect`, … |
| `userstats` | `user_stats` |
| `crimegroups`, `orgcrimes`, `oclogs` | `crime_groups`, `organised_crimes`, `organised_crime_logs` |
| `bankxferlogs` + `cashxferlogs` + `crystalxferlogs` | `transfer_logs` (`type` column) |
| `itembuylogs`, `itemselllogs`, `imarketaddlogs`, `imbuylogs`, `imremovelogs`, `itemxferlogs` | `item_logs` (`type` column) |
| `friendslist` + `blacklist` + `contactlist` | `user_lists` (`list` = friend / enemy / contact) |
| `dps_accepted` + `willps_accepted` | `donation_payments` (unique `transaction_id`) |
| `itemmarket`, `crystalmarket` | `item_market_listings`, `crystal_market_listings` |
| `applications`, `gangevents`, `gangwars`, `surrenders` | `gang_applications`, `gang_events`, `gang_wars`, `gang_war_surrenders` |
| `jobranks`, `coursesdone`, `shopitems` | `job_ranks`, `course_completions`, `shop_items` |
| `challengebots`, `challengesbeaten`, `attacklogs` | `challenge_bots`, `challenge_bot_wins`, `attack_logs` |
| `forum_forums` | `forums` (counts and "last post" are queried, not stored) |
| `polls.choice1..10`, `voted1..10`, `users.voted` | `polls.choices` (JSON) + `poll_votes` |
| `items.effect1..3` | `items.effects` (JSON) |
| `papercontent`, `preports`, `referals`, `votes` | `newspaper`, `player_reports`, `referrals`, `vote_site_claims` |
| `staff_roles`, `users_roles`, `stafflog`, `staffnotelogs` | `staff_roles`, `user_staff_roles`, `staff_logs`, `staff_note_logs` |
| `fedjail`, `jaillogs` + `unjaillogs` | `federal_jail`, `federal_jail_logs` |
| `cron_times`, `logs_cron_runtimes`, `logs_cron_fails` | `cron_runs` |
| `settings` | `settings` (staff-editable values); balance numbers moved to `config/game.php` |
| `users.jail`, `hospital`, `cdays`, `donatordays`, `mailban`, `forumban` (countdowns) | `jail_until`, `hospital_until`, `course_ends_at`, `donator_until`, `mail_banned_until`, `forum_banned_until` |
| `users.bankmoney = -1` / `cybermoney = -1` (no account) | `bank_balance` / `cyber_balance` `NULL` |
| `users.new_mail`, `new_events`, `new_announcements`, `friend_count`, `enemy_count`, `posts` | Not stored; counted from `read_at` columns and related tables |

Reference data from the backup (Default City, both houses, the four staff roles with their permissions, settings, newspaper text) is inserted by the migrations.

### HTMX targeting cheat sheet

By default every link and form replaces `#content` (set on `<body>`). To update something else, give the element its own target:

| Goal | Markup | Server returns |
|------|--------|----------------|
| Refresh one block | `<button hx-get="/dashboard/stats" hx-target="#stats" hx-swap="outerHTML">` | `view('partials...', $data, layout: null)` with the same `id="stats"` root |
| Element loads its own content | `<span hx-get="/announcements/unread-count" hx-trigger="load" hx-target="this">` | Just the inner HTML |
| Poll | `hx-trigger="load, every 60s"` | Same |
| Refresh when something happens elsewhere | `hx-trigger="announcements-read from:body"` on the element; `->withHeader('HX-Trigger', 'announcements-read')` on any response | Same |
| Form that updates only its panel | `<form method="post" action="/x" hx-post="/x" hx-target="#panel" hx-swap="outerHTML">` | The panel: status 200 on success, 422 with errors (see `AdminAnnouncementController::store`) |
| Append to a list | `hx-target="#list" hx-swap="beforeend"` | One item |
| Ask first | `hx-confirm="Are you sure?"` | — |

Keep `method`/`action` on HTMX forms so they still work without JavaScript, and check `$request->isHtmx()` in the controller when the two cases need different responses.

## Staff panel

`/admin` is open to anyone holding a staff role and to the game owner (`users.role = 'admin'`, e.g. the seeded `admin`). Inside, each page needs a permission. Roles are combinations of the 26 legacy permissions (`manage_items`, `view_logs`, ...); the `administrator` permission grants everything. Manage roles at **Staff roles**, and give them to players at **Grant / revoke**. The menu only shows what the viewer may use.

| Area | Permission | Replaces |
|------|-----------|----------|
| Staff home, notepad | any staff | `staff.php` |
| Basic settings, announcements, run cron | `administrator` | `staff.php?action=basicset/announce/fire-cron` |
| Roles / grant / revoke | `manage_roles`, `manage_staff` | `staff_roles.php` |
| Find & edit players, force logout, delete, staff notes | `manage_users` | `staff_users.php`, `staffnotes.php` |
| Credit player / mass payment | `credit_user` / `credit_all_users` | `staff_users.php` |
| Player reports | `manage_player_reports` | `staff_users.php?action=reportsview` |
| Federal jail | `manage_punishments` | `staff_punit.php` |
| Logs (staff, attacks, transfers, items, mail) | `view_logs` | `staff_logs.php` |
| Game data: cities, houses, courses, crime groups, crimes, item types, items, shops, jobs, job ranks, organised crimes | `manage_cities`, `manage_houses`, ... | `staff_cities.php`, `staff_houses.php`, `staff_courses.php`, `staff_crimes.php`, `staff_items.php`, `staff_shops.php`, `staff_jobs.php` |

Every staff change is written to the staff log.

Protect routes with the `staff` middleware: `'staff'` for any staff member, `'staff:manage_items'` for one permission, `'staff:credit_item,manage_items'` for either. In code, use `StaffPermissions::can($user, 'manage_items')`.

### Adding a game-data editor

Game data screens are generated from `config/staff_resources.php`. To make another table editable, add an entry:

```php
'houses' => [
    'title' => 'Houses', 'singular' => 'house', 'table' => 'houses', 'permission' => 'manage_houses',
    'fields' => [
        'name'  => ['label' => 'Name', 'type' => 'string', 'rules' => 'required|string|max:255'],
        'price' => ['label' => 'Price', 'type' => 'money', 'rules' => 'required|integer|min:0'],
        'will'  => ['label' => 'Maximum will', 'type' => 'int', 'rules' => 'required|integer|min:1', 'unique' => true],
    ],
    'columns' => ['name', 'price', 'will'],     // shown in the list
    'protected_ids' => [1],                     // can never be deleted
    'reassign_on_delete' => [['users', 'house_id', 1]],
],
```

Field types: `string`, `text`, `int`, `money`, `bool`, `select` (`'source' => 'table'`, optional `'nullable' => true`), `formula` (validated like crime formulas) and `pivot` (many-to-many checkboxes, e.g. shop stock). Use `block_delete_if` to refuse deleting rows that are still in use. The menu link appears automatically.

## Adding a module

1. Create `app/Modules/Banking/{Controllers,Services,Repositories,Models,Requests}`.
2. Put SQL in the repository, rules in the service, and keep the controller thin.
3. Bind any repository interface in `Application::registerServices()`.
4. Register routes in `routes/web.php` inside the `auth` group.
5. Add views under `resources/views/pages/banking/`.
6. Add a migration for the module's tables and run `php bin/migrate.php`.

## Deploying behind nginx

Point the site root at `public/`, not at the project root. The app must sit at the root of its own domain or port, because redirects use absolute paths such as `/login`.

```nginx
server {
    listen 8080;
    root /var/www/html/McCode2.5/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Before going live, set `APP_ENV=production`, `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true` (when served over HTTPS) in `.env`, and make `storage/` writable by the web server user.

## Troubleshooting

| Error | Fix |
|-------|-----|
| `[1698] Access denied for user 'root'@'localhost'` | Don't use MySQL `root`; create the `mccode` user (see Database setup) |
| `[1045] Access denied for user 'mccode'` | `DB_PASSWORD` in `.env` doesn't match the MySQL user's password |
| `[2002] Connection refused` | MySQL isn't running: `sudo service mysql start` |
| `[1049] Unknown database` | Create the database named in `DB_DATABASE` |
| `localhost refused to connect` in the browser | The dev server isn't running; start it with `php -S ...` |
| New Tailwind classes have no effect | Run `npm run build` (or keep `npm run dev` running) |
| Page shows unstyled HTML | `public/assets/css/app.css` is missing; run `npm install && npm run build` |
| "Your session has expired" (419) | The CSRF token is stale; refresh the page and resubmit |
| Any other error | Check `storage/logs/app-YYYY-MM-DD.log`; with `APP_DEBUG=true` the full error also shows on the page |
