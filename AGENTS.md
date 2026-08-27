# BoteBank Project Notes

## Stack
- PHP (WAMP / Apache)
- MySQL 8.4.7 on 127.0.0.1:3306 (this is the server the app actually connects to)
- MariaDB 11.4.9 on 127.0.0.1:3307 (also installed; migrations must target the MySQL instance)
- MySQLi with prepared statements (most queries)
- Tailwind CSS CDN, Lucide icons, Chart.js, Leaflet, Feather Icons
- Composer only under `sms/` for `capcom6/android-sms-gateway`

## Entry Points
- Public landing: `index.php`
- Auth: `login.php`, `login_handler.php`, `register.php`, `logout.php`
- Admin role: `admin/admin_dashboard.php` (and `admin/*.php`)
- Resident role: `resident/resident_dashboard.php` (and `resident/*.php`)
- Worker role: `worker/worker_dashboard.php` (and `worker/*.php`)
- Shared API: `shared/upload_photo.php`
- SMS bridge: `sms/function/sms.php`, `sms/index.php`

## Core Includes
- `config/database.php` — DB credentials loaded from `.env`
- `includes/session.php` — starts session and redirects if not logged in
- `includes/auth_*.php` — role guards (admin, resident, worker, staff)
- `includes/system_functions.php` — settings, points, display helpers
- `includes/auto_route_functions.php` — OSRM route generation logic
- `includes/upload_handler.php` — centralized upload class
- `includes/sms_notifications.php` — SMS wrapper (credentials from `.env`)

## Database Tables
`users`, `puroks`, `recyclabletypes`, `pickuprequests`, `direct_dropoffs`, `pointsledger`, `rewards`, `rewardredemptions`, `collectionroutes`, `routestops`, `collection_points`, `carousel_slides`, `system_settings`, `smsnotifications`, `daily_reports`

## Recent Schema Repairs
- Created missing `bote` database and imported `bote (1).sql`.
- Added `Cancelled` to `pickuprequests.status` enum.
- Added `description` to `pointsledger` for audit context.
- Dropped unused `rewards.image_url`.
- Added missing `smsnotifications` table.
- Created `daily_reports` table for worker self-reported summaries.
- Widened `users.first_name`/`last_name`/`email`, `recyclabletypes.name`, and added performance indexes.

## Key Red Flags
- SMS gateway credentials must be supplied through `.env`; default is empty.
- `sms/function/sms.php` requires `SMS_API_KEY` in `.env`.
- `worker_daily_reports.php` now persists to `daily_reports`.
- `pointsledger.description` column exists and is used by `App\Repositories\MysqliPointsRepository`.
- `rewards.image_url` removed; only `image` is used.
- Sidebars are complete standalone HTML documents duplicated across roles.
- JS `onclick` handlers that interpolate strings now use `json_encode()` with JSON hex flags.
- User-facing output in forms and tables should use `htmlspecialchars()` or `json_encode()`.

## Recently Addressed
- DB credentials moved to `.env` and `.env`/`.htaccess` blocks direct web access.
- Session cookie set to HttpOnly and SameSite=Lax; session ID regenerated on login.
- `resident_staff_verification.php` now uses staff/worker auth.
- CSRF tokens added to all role forms and validated in `auth_admin.php`, `auth_worker.php`, `auth_res.php`, and `auth_staff.php`.
- Success/error messages in touched pages escaped with `htmlspecialchars()`.
- `shared/upload_photo.php` validates CSRF tokens.
- JavaScript `onclick` attributes that passed user data with `addslashes()` now use `json_encode()` with JSON hex flags.
- User-facing output in dashboards, request lists, leaderboards, and admin grids escaped with `htmlspecialchars()` or `json_encode()`.
- Database schema repaired and `bote` database created/imported from the SQL dump.
- `config/database.php` fixed by removing unsupported `NO_AUTO_CREATE_USER` from the `sql_mode` init command.
- `worker_daily_reports.php` now persists reports to `daily_reports` and displays saved entries.
- `admin_pickup_requests.php` status dashboard and dropdown include all enum values (Pending, Scheduled, In Progress, Completed, Cancelled).
- `smsnotifications` table and `daily_reports` table created.
- SMS bridge test form (`sms/index.php`) no longer hardcodes credentials.
- All 42 PHPUnit tests and PHPStan level 9 pass; login/role paths smoke-tested.

## Environment Constraints
- The live database contains 6 user accounts (1 admin, 2 workers, 3 residents).
- Avoid destructive user/pickup/points operations; prefer creating isolated test data over modifying the live accounts.

## Core Application (new app/ layer)
- `app/Core/ConnectionInterface.php` and `app/Core/Database.php` — MySQLi wrapper with typed `query/selectOne/execute/insert` and transaction helpers.
- `app/Core/TypeCaster.php` — database-row-to-typed-value helper used by models.
- `app/Models/{RecyclableType,Pickup,Dropoff}.php` — immutable domain models with `fromArray` factories.
- `app/Enums/{Unit,PickupStatus,DropoffStatus,TransactionType}.php` — backed enums for schema enums.
- `app/Services/{PointsService,PickupService,DropoffService}.php` — canonical business logic for awarding points, completing pickups, and verifying/rejecting drop-offs.
- `app/Repositories/{Pickup,Dropoff,Points}RepositoryInterface.php` and `Mysqli*` implementations.

## Build / Test
- PHP CLI: `C:\wamp64\bin\php\php8.2.29\php.exe`
- Composer: `C:\wamp64\bin\php\php8.2.29\php.exe C:\wamp64\bin\php\php8.2.29\composer`
- Run tests: `C:\wamp64\bin\php\php8.2.29\php.exe vendor\bin\phpunit`
- Run static analysis: `C:\wamp64\bin\php\php8.2.29\php.exe vendor\bin\phpstan analyse app tests --level=9`
- WAMP MySQL service must be running for integration tests / web use (PHP connects to `localhost` -> 127.0.0.1:3306).
- MySQL CLI: `C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe -u root -h 127.0.0.1 -P 3306` (use this for schema changes, not the MariaDB client on 3307).
- Live DB backup created at `backups/bote_backup_mysql_2026_08_27.sql`.

## TypeScript UI Build
- Node.js is installed at `C:\wamp64\bin\nodejs\v20.20.2` and the user PATH was updated to include it.
- Source TypeScript files live in `{admin,resident,worker}/assets/ts/` and `assets/ts/` for shared/public pages.
- Compiled output goes to the matching `assets/js/` directory.
- Per-role `tsconfig.json` files extend `tsconfig.base.json` and output `module: ES2022` scripts that are loaded with `<script type="module" src="...">`.
- Shared helpers are in `admin/assets/ts/shared.ts`, `resident/assets/ts/shared.ts`, `worker/assets/ts/shared.ts`, and `assets/ts/shared.ts`.
- Page data should be passed through `<script type="application/json" id="<page>-data">` and read with `getPageData<T>()` from `./shared.js`; avoid inline executable JavaScript.
- Public functions used by `onclick` attributes are attached to `window` at the end of each module (`Object.assign(window, { ... })`) with matching `declare global` blocks.
- Build command: `npm run build` (or `build.bat` on Windows if the PATH is not yet loaded in the current shell).
- `package-lock.json` and `node_modules` are created by the build; `node_modules` and `*.js.map` files are `.gitignore`d.

## Agent Workflow Rules
- Do not auto-commit changes. Pause and ask the user for explicit confirmation before creating a commit.
- Do not push to the remote repository unless the user explicitly requests it.
- Continue running tests, lint, and smoke checks proactively.
