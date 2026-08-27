<?php

declare(strict_types=1);

/**
 * Bulk migration script for remaining BoteBank role pages.
 * Generates controllers, views, root TypeScript modules and dispatchers.
 */

$baseDir = realpath(__DIR__ . '/..');

$pages = [
    // Admin pages
    ['role' => 'admin', 'file' => 'admin/admin_users.php', 'controller' => 'Users', 'view' => 'admin.users', 'title' => 'Users', 'ts' => ['old' => 'admin/assets/ts/admin_users.ts', 'new' => 'assets/ts/pages/admin/users.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_pickup_requests.php', 'controller' => 'PickupRequests', 'view' => 'admin.pickup_requests', 'title' => 'Pickup Requests', 'ts' => ['old' => 'admin/assets/ts/pickup_request.ts', 'new' => 'assets/ts/pages/admin/pickup_requests.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_rewards.php', 'controller' => 'Rewards', 'view' => 'admin.rewards', 'title' => 'Rewards', 'ts' => ['old' => 'admin/assets/ts/rewards.ts', 'new' => 'assets/ts/pages/admin/rewards.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_reports.php', 'controller' => 'Reports', 'view' => 'admin.reports', 'title' => 'Reports', 'ts' => ['old' => 'admin/assets/ts/admin_reports.ts', 'new' => 'assets/ts/pages/admin/reports.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_puroks.php', 'controller' => 'Puroks', 'view' => 'admin.puroks', 'title' => 'Puroks', 'ts' => ['old' => 'admin/assets/ts/purok.ts', 'new' => 'assets/ts/pages/admin/puroks.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_recyclable_types.php', 'controller' => 'RecyclableTypes', 'view' => 'admin.recyclable_types', 'title' => 'Recyclable Types', 'ts' => ['old' => 'admin/assets/ts/recyclable_types.ts', 'new' => 'assets/ts/pages/admin/recyclable_types.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_collection_points.php', 'controller' => 'CollectionPoints', 'view' => 'admin.collection_points', 'title' => 'Collection Points', 'ts' => ['old' => 'admin/assets/ts/admin_collection_points.ts', 'new' => 'assets/ts/pages/admin/collection_points.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_profile.php', 'controller' => 'Profile', 'view' => 'admin.profile', 'title' => 'My Profile', 'ts' => ['old' => 'admin/assets/ts/profile.ts', 'new' => 'assets/ts/pages/admin/profile.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_settings.php', 'controller' => 'Settings', 'view' => 'admin.settings', 'title' => 'System Settings', 'ts' => ['old' => 'admin/assets/ts/admin_settings.ts', 'new' => 'assets/ts/pages/admin/settings.ts']],
    ['role' => 'admin', 'file' => 'admin/admin_sms_settings.php', 'controller' => 'SmsSettings', 'view' => 'admin.sms_settings', 'title' => 'SMS Settings', 'ts' => null],
    ['role' => 'admin', 'file' => 'admin/carousel_editor.php', 'controller' => 'CarouselEditor', 'view' => 'admin.carousel_editor', 'title' => 'Carousel Editor', 'ts' => ['old' => 'admin/assets/ts/carousel_editor.ts', 'new' => 'assets/ts/pages/admin/carousel_editor.ts']],

    // Resident pages
    ['role' => 'resident', 'file' => 'resident/resident_profile.php', 'controller' => 'Profile', 'view' => 'resident.profile', 'title' => 'My Profile', 'ts' => ['old' => 'resident/assets/ts/profile.ts', 'new' => 'assets/ts/pages/resident/profile.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_new_request.php', 'controller' => 'NewRequest', 'view' => 'resident.new_request', 'title' => 'Schedule Pickup', 'ts' => ['old' => 'resident/assets/ts/resident_new_request.ts', 'new' => 'assets/ts/pages/resident/new_request.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_save_request.php', 'controller' => 'SaveRequest', 'view' => 'resident.save_request', 'title' => 'Save Request', 'ts' => null, 'api' => true],
    ['role' => 'resident', 'file' => 'resident/resident_my_requests.php', 'controller' => 'MyRequests', 'view' => 'resident.my_requests', 'title' => 'My Requests', 'ts' => ['old' => 'resident/assets/ts/my_request.ts', 'new' => 'assets/ts/pages/resident/my_requests.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_history.php', 'controller' => 'History', 'view' => 'resident.history', 'title' => 'History', 'ts' => ['old' => 'resident/assets/ts/resident_history.ts', 'new' => 'assets/ts/pages/resident/history.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_rewards.php', 'controller' => 'Rewards', 'view' => 'resident.rewards', 'title' => 'Redeem Rewards', 'ts' => ['old' => 'resident/assets/ts/rewards.ts', 'new' => 'assets/ts/pages/resident/rewards.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_redeem_reward.php', 'controller' => 'RedeemReward', 'view' => 'resident.redeem_reward', 'title' => 'Redeem Reward', 'ts' => null],
    ['role' => 'resident', 'file' => 'resident/resident_leaderboard.php', 'controller' => 'Leaderboard', 'view' => 'resident.leaderboard', 'title' => 'Leaderboard', 'ts' => ['old' => 'resident/assets/ts/leaderboard.ts', 'new' => 'assets/ts/pages/resident/leaderboard.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_direct_dropoff.php', 'controller' => 'DirectDropoff', 'view' => 'resident.direct_dropoff', 'title' => 'Direct Drop-off', 'ts' => ['old' => 'resident/assets/ts/resident_direct_dropoff.ts', 'new' => 'assets/ts/pages/resident/direct_dropoff.ts']],
    ['role' => 'resident', 'file' => 'resident/resident_dropoff_success.php', 'controller' => 'DropoffSuccess', 'view' => 'resident.dropoff_success', 'title' => 'Drop-off Success', 'ts' => ['old' => 'resident/assets/ts/resident_dropoff_success.ts', 'new' => 'assets/ts/pages/resident/dropoff_success.ts']],

    // Worker pages
    ['role' => 'worker', 'file' => 'worker/worker_profile.php', 'controller' => 'Profile', 'view' => 'worker.profile', 'title' => 'My Profile', 'ts' => ['old' => 'worker/assets/ts/worker_profile.ts', 'new' => 'assets/ts/pages/worker/profile.ts']],
    ['role' => 'worker', 'file' => 'worker/worker_assigned_pickups.php', 'controller' => 'AssignedPickups', 'view' => 'worker.assigned_pickups', 'title' => 'Assigned Pickups', 'ts' => ['old' => 'worker/assets/ts/assigned_pickups.ts', 'new' => 'assets/ts/pages/worker/assigned_pickups.ts']],
    ['role' => 'worker', 'file' => 'worker/worker_collection_routes.php', 'controller' => 'CollectionRoutes', 'view' => 'worker.collection_routes', 'title' => 'Collection Routes', 'ts' => ['old' => 'worker/assets/ts/worker_collection_routes.ts', 'new' => 'assets/ts/pages/worker/collection_routes.ts']],
    ['role' => 'worker', 'file' => 'worker/worker_completed_collections.php', 'controller' => 'CompletedCollections', 'view' => 'worker.completed_collections', 'title' => 'Completed Collections', 'ts' => ['old' => 'worker/assets/ts/worker_completed_collections.ts', 'new' => 'assets/ts/pages/worker/completed_collections.ts']],
    ['role' => 'worker', 'file' => 'worker/worker_daily_reports.php', 'controller' => 'DailyReports', 'view' => 'worker.daily_reports', 'title' => 'Daily Reports', 'ts' => ['old' => 'worker/assets/ts/worker_daily_reports.ts', 'new' => 'assets/ts/pages/worker/daily_reports.ts']],
    ['role' => 'worker', 'file' => 'worker/worker_verification.php', 'controller' => 'Verification', 'view' => 'worker.verification', 'title' => 'Worker Verification', 'ts' => ['old' => 'worker/assets/ts/worker_verification.ts', 'new' => 'assets/ts/pages/worker/verification.ts']],

    // Handlers / AJAX endpoints
    ['role' => 'admin', 'file' => 'admin/carousel_handler.php', 'controller' => 'Api\\CarouselHandler', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'admin', 'file' => 'admin/collection_points_handler.php', 'controller' => 'Api\\CollectionPointsHandler', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'admin', 'file' => 'admin/save_settings.php', 'controller' => 'Api\\SaveSettings', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'resident', 'file' => 'resident/cancel_request.php', 'controller' => 'Api\\CancelRequest', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'worker', 'file' => 'worker/complete_pickup.php', 'controller' => 'Api\\CompletePickup', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'worker', 'file' => 'worker/generate_route.php', 'controller' => 'Api\\GenerateRoute', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'worker', 'file' => 'worker/get_map_data.php', 'controller' => 'Api\\GetMapData', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'worker', 'file' => 'worker/get_route_coordinates.php', 'controller' => 'Api\\GetRouteCoordinates', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
    ['role' => 'worker', 'file' => 'worker/get_route_details.php', 'controller' => 'Api\\GetRouteDetails', 'view' => null, 'title' => null, 'ts' => null, 'api' => true],
];

function writeFile(string $path, string $content): void
{
    $full = $GLOBALS['baseDir'] . '/' . $path;
    $dir = dirname($full);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new \RuntimeException("Failed to create directory: {$dir}");
    }
    if (file_put_contents($full, $content) === false) {
        throw new \RuntimeException("Failed to write file: {$full}");
    }
}

function readSource(string $path): string
{
    $full = $GLOBALS['baseDir'] . '/' . $path;
    if (!file_exists($full)) {
        return '';
    }

    return (string) file_get_contents($full);
}

function slugToClass(string $view): string
{
    $parts = explode('.', $view);

    return implode('\\', array_map('ucfirst', $parts));
}

function allowedRolesFromAuth(string $phpLogic): string
{
    if (preg_match('/auth_staff\.php/', $phpLogic)) {
        return "'worker', 'admin'";
    }
    if (preg_match('/auth_admin\.php/', $phpLogic)) {
        return "'admin'";
    }
    if (preg_match('/auth_worker\.php/', $phpLogic)) {
        return "'worker'";
    }
    if (preg_match('/auth_res\.php/', $phpLogic)) {
        return "'resident'";
    }

    return "'{$GLOBALS['role']}'";
}

function generateController(string $role, string $controllerName, ?string $view, ?string $title, string $logic, bool $api = false): string
{
    $subNamespace = '';
    $className = $controllerName;
    if (str_contains($controllerName, '\\')) {
        [$subNamespace, $className] = explode('\\', $controllerName, 2);
    }
    $ns = 'App\\Http\\Controllers\\' . ucfirst($role);
    if ($subNamespace !== '') {
        $ns .= '\\' . $subNamespace;
    }

    $allowed = allowedRolesFromAuth($logic);
    $logic = transformLogic($logic);

    if ($api) {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use App\\Core\\TypeCaster;
use App\\Http\\Controllers\\BaseController;

final class {$className}Controller extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [{$allowed}];
    }

    public function render(): void
    {
{$logic}
    }
}
PHP;
    }

    return <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use App\\Core\\TypeCaster;
use App\\Http\\Controllers\\BaseController;

final class {$className}Controller extends BaseController
{
    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return [{$allowed}];
    }

    public function render(): void
    {
{$logic}

        \$data = get_defined_vars();

        \$this->view('{$view}', \$data);
    }
}
PHP;
}

function transformLogic(string $logic): string
{
    // Determine allowed roles before stripping auth includes
    $GLOBALS['role'] = $GLOBALS['role'] ?? '';

    $replacements = [
        // Remove standard requires
        '/^\s*require_once\s+[\'"](?:\.\.\/)+config\/database\.php[\'"];\s*$/m' => '',
        '/^\s*require(?:_once)?\s+[\'"](?:\.\.\/)+includes\/(?:auth_\w+|system_functions)\.php[\'"];\s*(?:\/\/.*)?$/m' => '',

        // Convert include helper includes to the base controller helper
        '/^\s*require(?:_once)?\s+[\'"](?:\.\.\/)+includes\/(upload_handler|sms_notifications|auto_route_functions)\.php[\'"];\s*(?:\/\/.*)?$/m' => '$this->requireInclude(\'$1.php\');',

        // Remove sidebar / shared includes
        '/^\s*include\s+[\'"](?:\.\.\/|\.)?\/?(?:admin|resident|worker)_sidebar\.php[\'"];\s*$/m' => '',
        '/^\s*include\s+[\'"](?:\.\.\/)+(?:admin|resident|worker)\/(?:admin|resident|worker)_sidebar\.php[\'"];\s*$/m' => '',
        '/^\s*include\s+[\'"](?:\.\.\/)+shared\/[^\'"]+[\'"];\s*$/m' => '',

        // Legacy statement helpers
        '/prepare_statement\(\$conn,\s*/' => '$this->legacy()->prepare(',
        '/execute_query\(\$conn,\s*/' => '$this->legacy()->query(',
        '/\$conn->query\(/' => '$this->legacy()->query(',
        '/\$conn->prepare\(/' => '$this->legacy()->prepare(',
        '/\$conn->begin_transaction\(\)/' => '$this->legacy()->beginTransaction()',
        '/\$conn->commit\(\)/' => '$this->legacy()->commit()',
        '/\$conn->rollback\(\)/' => '$this->legacy()->rollback()',
        '/\$conn->insert_id/' => '$this->legacy()->last_insert_id',
        '/\$conn->affected_rows/' => '$this->legacy()->last_statement_affected_rows',

        // Settings helpers
        '/getSystemSetting\(\$conn,\s*/' => '$this->setting(',
        '/getAllSystemSettings\(\$conn\)/' => '$this->allSettings()',
        '/setSystemSetting\(\$conn,\s*/' => '$this->setSetting(',
        '/updateSystemSetting\(\$conn,\s*/' => '$this->setSetting(',
        '/ensureSystemSettingsTable\(\$conn\)/' => '$this->ensureSystemSettingsTable()',

        // Service container refs
        '/\$container->/' => '$this->container->',

        // Global class / function calls without leading backslash
        '/\bnew BoteBankUploadHandler\(/' => 'new \\BoteBankUploadHandler(',
        '/\bcatch \(Exception\b/' => 'catch (\\Exception',
        '/\bthrow new Exception\b/' => 'throw new \\Exception',
        '/\bnew Exception\b/' => 'new \\Exception',


    ];

    foreach ($replacements as $pattern => $replacement) {
        $logic = preg_replace($pattern, $replacement, $logic);
    }

    // Redirects
    $logic = preg_replace_callback(
        '/header\s*\(\s*[\'"]Location:\s*([^\'"]+)[\'"]\s*\);\s*(?:exit;|die;)?/',
        static fn ($m) => "\$this->redirect('" . trim($m[1]) . "');",
        $logic,
    );

    // JSON responses
    $logic = preg_replace('/header\s*\(\s*[\'"]Content-Type:\s*application\/json[\'"]\s*\);\s*/i', '', $logic);
    $logic = preg_replace('/echo\s+json_encode\(\$response,\s*[^)]+\);\s*exit;?/', '$this->json($response);', $logic);
    $logic = preg_replace('/echo\s+json_encode\(\$response\);\s*exit;?/', '$this->json($response);', $logic);
    $logic = preg_replace('/echo\s+json_encode\(\$response\);/', '$this->json($response);', $logic);
    $logic = preg_replace('/exit;?\s*$/m', '', $logic);

    // Trim blank lines and leading/trailing whitespace, then indent
    $logic = trim($logic);
    if ($logic !== '') {
        $logic = implode("\n", array_map(static fn ($line) => '        ' . $line, explode("\n", $logic)));
    }

    return $logic;
}

function transformView(string $html, ?string $oldTsName): string
{
    // Remove sidebar includes
    $html = preg_replace('/<\?php\s+include\s+[\'"](?:\.\.\/|\.)?\/?(?:admin|resident|worker)_sidebar\.php[\'"];\s*\?>/', '', $html);
    $html = preg_replace('/<\?php\s+include\s+[\'"](?:\.\.\/)+shared\/[^\'"]+[\'"];\s*\?>/', '', $html);

    // Keep style/link/script inside head, then drop head/body wrappers
    if (preg_match('/<head[^>]*>(.*?)<\/head>/s', $html, $headMatch)) {
        $headContent = $headMatch[1];
    } else {
        $headContent = '';
    }

    if (preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $bodyMatch)) {
        $body = $bodyMatch[1];
    } else {
        $body = preg_replace('/<\/html>\s*$/s', '', $html);
        $body = preg_replace('/<\?xml.*\?>/', '', $body);
    }

    // Remove duplicated CDNs / theme scripts
    $headContent = preg_replace('/<script\s+src="https:\/\/cdn\.tailwindcss\.com"><\/script>/', '', $headContent);
    $headContent = preg_replace('/<script\s+src="https:\/\/unpkg\.com\/lucide@latest\/dist\/umd\/lucide\.js"><\/script>/', '', $headContent);
    $headContent = preg_replace('/<script\s+src="https:\/\/cdn\.jsdelivr\.net\/npm\/chart\.js"><\/script>/', '', $headContent);
    $headContent = preg_replace('/<script\s+type="module"\s+src="(?:\.\.\/)*assets\/js\/theme\.js"><\/script>/', '', $headContent);

    // Remove closing theme / page scripts from body
    $body = preg_replace('/<script\s+type="module"\s+src="(?:\.\.\/)*assets\/js\/theme\.js"><\/script>/', '', $body);
    $body = preg_replace('/<script\s+type="module"\s+src="(?:\.\.\/)*assets\/js\/shared\.js"><\/script>/', '', $body);
    $body = preg_replace('/<script\s+type="module"\s+src="(?:\.\.\/)*assets\/js\/[^"]+\.js"><\/script>/', '', $body);
    $body = preg_replace('/<script\s+src="assets\/js\/[^"]+\.js"><\/script>/', '', $body);

    // Remove old inline scripts with type="module" or normal (we'll move logic to root TS)
    $body = preg_replace('/<script>(?!<\/script>).*?<\/script>/s', '', $body);
    $body = preg_replace('/<script\s+type="module"(?:\s+src="[^"]+")?\s*>(?!<\/script>).*?<\/script>/s', '', $body);

    // Remove trailing body/html tags
    $body = preg_replace('/<\/body>\s*<\/html>\s*$/s', '', $body);
    $body = preg_replace('/<\/html>\s*$/s', '', $body);

    $viewContent = trim($headContent) . "\n" . trim($body);

    // Use basePath for explicit admin/resident/worker asset references
    $viewContent = preg_replace_callback(
        '/(href|src)="((?:\.\.\/)+)?(admin|resident|worker)\/assets\//',
        static fn ($m) => $m[1] . '="<?php echo $basePath; ?>' . $m[3] . '/assets/',
        $viewContent,
    );

    return trim($viewContent);
}

function generateDispatcher(string $controllerClass): string
{
    $use = str_replace('\\', '\\', $controllerClass);

    return <<<PHP
<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/system_functions.php';
use {$use};
\$controller = new {$controllerClass}(\$container);
\$controller->render();
PHP;
}

function migrateTs(string $oldPath, string $newPath): void
{
    $content = readSource($oldPath);
    if ($content === '') {
        return;
    }

    // Root TS files are at assets/ts/pages/{role}/{name}.ts. The shared module is at assets/ts/shared.ts, two levels up.
    $content = preg_replace('/from\s+[\'"](?:\.\.\/)*(?:\.\.\/)*shared\.js[\'"]/', "from '../../shared.js'", $content);

    // If the role folder had its own shared.ts, point to the root shared helpers instead.
    $content = preg_replace('/from\s+[\'"]\.\/shared\.js[\'"]/', "from '../../shared.js'", $content);

    writeFile($newPath, $content);
}

$results = [];

foreach ($pages as $page) {
    $role = $page['role'];
    $source = $page['file'];
    $controllerName = $page['controller'];
    $view = $page['view'] ?? null;
    $title = $page['title'] ?? null;
    $api = $page['api'] ?? false;
    $ts = $page['ts'] ?? null;

    $sourcePath = $baseDir . '/' . $source;
    $sourceContent = readSource($source);

    // Split PHP logic and HTML view using the PHP tokenizer
    $phpLogic = '';
    $viewHtml = $sourceContent;

    if (str_starts_with(ltrim($sourceContent), '<?php')) {
        $tokens = token_get_all($sourceContent);
        $closeTagPos = null;
        $openSeen = 0;
        $offset = 0;
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $len = strlen($token[1]);
                if ($token[0] === T_OPEN_TAG) {
                    $openSeen++;
                } elseif ($token[0] === T_CLOSE_TAG) {
                    $openSeen--;
                    if ($openSeen === 0 && $closeTagPos === null) {
                        $closeTagPos = $offset + $len;
                        break;
                    }
                }
                $offset += $len;
            } else {
                $offset += strlen($token);
            }
        }

        if ($closeTagPos !== null) {
            $phpLogic = substr($sourceContent, 0, $closeTagPos);
            $phpLogic = preg_replace('/^\s*<\?php\s*/', '', $phpLogic);
            $phpLogic = preg_replace('/\?>(\s*)$/s', '$1', $phpLogic);
            $viewHtml = substr($sourceContent, $closeTagPos);
        } else {
            // Pure PHP file
            $phpLogic = preg_replace('/^\s*<\?php\s*/', '', $sourceContent);
            $viewHtml = '';
        }
    }

    // Build controller class namespace segments
    $className = $controllerName;
    $subNamespace = '';
    if (str_contains($controllerName, '\\')) {
        [$subNamespace, $className] = explode('\\', $controllerName, 2);
        $controllerNs = 'App\\Http\\Controllers\\' . ucfirst($role) . '\\' . $subNamespace;
    } else {
        $controllerNs = 'App\\Http\\Controllers\\' . ucfirst($role);
    }

    $controllerFile = 'app/Http/Controllers/' . ucfirst($role) . '/' . ($subNamespace ? $subNamespace . '/' : '') . $className . 'Controller.php';

    $GLOBALS['role'] = $role;
    if ($api) {
        $controllerCode = generateController($role, $controllerName, '', null, $phpLogic, true);
    } else {
        $controllerCode = generateController($role, $controllerName, $view, $title, $phpLogic, false);
    }

    writeFile($controllerFile, $controllerCode);

    if (!$api && $view !== null) {
        $viewFile = 'resources/views/' . str_replace('.', '/', $view) . '.php';
        $viewContent = transformView($viewHtml, $ts['old'] ?? null);
        writeFile($viewFile, $viewContent);
    }

    if ($ts !== null) {
        migrateTs($ts['old'], $ts['new']);
    }

    // Overwrite original entry file with dispatcher
    $dispatcherClass = $controllerNs . '\\' . $className . 'Controller';
    $dispatcher = generateDispatcher($dispatcherClass);
    file_put_contents($sourcePath, $dispatcher);

    $results[] = $source . ' -> ' . $controllerFile;
}

echo "Migrated " . count($results) . " files:\n";
echo implode("\n", $results) . "\n";
