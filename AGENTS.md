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
- `config/database.php` — DB credentials (currently hardcoded root/blank)
- `includes/session.php` — starts session and redirects if not logged in
- `includes/auth_*.php` — role guards (admin, resident, worker, staff)
- `includes/system_functions.php` — settings, points, display helpers
- `includes/auto_route_functions.php` — OSRM route generation logic
- `includes/upload_handler.php` — centralized upload class
- `includes/sms_notifications.php` — SMS wrapper (hardcoded credentials)

## Database Tables
`users`, `puroks`, `recyclabletypes`, `pickuprequests`, `direct_dropoffs`, `pointsledger`, `rewards`, `rewardredemptions`, `collectionroutes`, `routestops`, `collection_points`, `carousel_slides`, `system_settings`, `smsnotifications`

## Key Red Flags
- Hardcoded DB password in `config/database.php`
- Hardcoded SMS API credentials in `includes/sms_notifications.php`
- `sms/function/sms.php` has no authentication and accepts arbitrary credentials
- Many `echo $variable` outputs are not escaped (`htmlspecialchars`) — XSS risk
- No CSRF tokens on state-changing forms or AJAX endpoints
- No session regeneration on login/logout (session fixation risk)
- `resident_staff_verification.php` uses resident auth instead of staff/worker auth
- `admin_pickup_requests.php` references a non-existent `description` column on `pointsledger`
- `worker_daily_reports.php` collects form data but does not persist it
- `rewards` table has redundant `image_url` and `image` columns
- Sidebars are complete standalone HTML documents duplicated across roles

## Environment Constraints
- The live database contains only 3 user accounts.
- Avoid destructive user/pickup/points operations; prefer creating isolated test data over modifying the 3 real accounts.
