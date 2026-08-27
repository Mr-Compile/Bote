<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Database;
use App\Http\Controllers\BaseController;

final class SmsSettingsController extends BaseController
{
    private Database $db;

    public function __construct(\App\Core\Container $container)
    {
        parent::__construct($container);
        $this->db = $container->database;
        $this->requireInclude('sms_notifications.php');
    }

    /**
     * @return list<string>
     */
    protected function allowedRoles(): array
    {
        return ['admin'];
    }

    public function render(): void
    {
        $success_message = null;
        $error_message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Csrf::validate($_POST['csrf_token'] ?? '');
            } catch (\InvalidArgumentException) {
                $error_message = 'Invalid or expired security token.';
            }

            if ($error_message === null) {
                $action = $_POST['action'] ?? '';

                if ($action === 'test_sms') {
                    $user_id = (int) ($_POST['test_user_id'] ?? 0);
                    $message = trim((string) ($_POST['test_message'] ?? ''));

                    if ($user_id <= 0 || $message === '') {
                        $error_message = 'Please select a user and enter a message.';
                    } elseif (sendSMSNotification($user_id, $message, 'test')) {
                        $success_message = 'Test SMS sent successfully!';
                    } else {
                        $error_message = 'Failed to send test SMS. Check logs for details.';
                    }
                } elseif ($action === 'send_bulk_sms') {
                    $message = trim((string) ($_POST['bulk_message'] ?? ''));
                    $user_ids = is_array($_POST['user_ids'] ?? null) ? $_POST['user_ids'] : [];

                    if ($message === '' || $user_ids === []) {
                        $error_message = 'Please enter a message and select at least one user.';
                    } else {
                        $success_count = 0;
                        foreach ($user_ids as $user_id) {
                            if (sendSMSNotification((int) $user_id, $message, 'bulk')) {
                                $success_count++;
                            }
                        }
                        $success_message = "Bulk SMS sent: {$success_count}/" . count($user_ids) . ' successful';
                    }
                }
            }
        }

        $users = $this->db->query(
            'SELECT user_id, first_name, last_name, phone, role FROM users ORDER BY role, first_name, last_name',
        );

        $recent_sms = $this->db->query(
            'SELECT sn.*, u.first_name, u.last_name FROM smsnotifications sn JOIN users u ON sn.user_id = u.user_id ORDER BY sn.sent_at DESC LIMIT 20',
        );

        $total_sms = (int) ($this->db->selectOne('SELECT COUNT(*) as total_sms FROM smsnotifications')['total_sms'] ?? 0);

        $today_sms = (int) ($this->db->selectOne('SELECT COUNT(*) as today_sms FROM smsnotifications WHERE DATE(sent_at) = CURDATE()')['today_sms'] ?? 0);

        $this->view('admin.sms_settings', [
            'success_message' => $success_message,
            'error_message' => $error_message,
            'users' => $users,
            'recent_sms' => $recent_sms,
            'total_sms' => $total_sms,
            'today_sms' => $today_sms,
            'pageScript' => null,
        ]);
    }
}
