# BoteBank Project Notes

## Stack
- PHP (WAMP / Apache)
- MariaDB 10.4.x (dumped schema in `bote (1).sql`)
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
`users`, `puroks`, `recyclabletypes`, `pickuprequests`, `direct_dropoffs`, `pointsledger`, `rewards`, `rewardredemptions`, `collectionroutes`, `routestops`, `collection_points`, `carousel_slides`, `system_settings`, `smsnotifications`

## Key Red Flags
- SMS gateway credentials must be supplied through `.env`; default is empty.
- `sms/function/sms.php` now requires `SMS_API_KEY` in `.env`.
- `pointsledger.description` column is still missing from the live schema.
- `worker_daily_reports.php` collects form data but does not persist it.
- `rewards` table has redundant `image_url` and `image` columns.
- Sidebars are complete standalone HTML documents duplicated across roles.
- The most common XSS vectors in role dashboards and lists have been escaped, but new pages may still introduce unescaped output.

## Recently Addressed
- DB credentials moved to `.env`.
- Session cookie set to HttpOnly and SameSite=Lax; session ID regenerated on login.
- `resident_staff_verification.php` now uses staff/worker auth.
- CSRF tokens added to all role forms and validated in `auth_admin.php`, `auth_worker.php`, `auth_res.php`, and `auth_staff.php`.
- Success/error messages in touched pages escaped with `htmlspecialchars()`.
- `shared/upload_photo.php` validates CSRF tokens.
- JavaScript `onclick` attributes that passed user data with `addslashes()` now use `json_encode()` with JSON hex flags.
- User-facing output in dashboards, request lists, leaderboards, and admin grids escaped with `htmlspecialchars()` or `json_encode()`.

## Environment Constraints
- The live database contains only 3 user accounts.
- Avoid destructive user/pickup/points operations; prefer creating isolated test data over modifying the 3 real accounts.

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
- WAMP database service `wampmariadb64` must be running for integration tests / web use.
