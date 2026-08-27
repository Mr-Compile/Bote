<?php
require_once '../config/database.php';
require '../includes/auth_admin.php'; // Ensure only admins can access
require '../includes/sms_notifications.php'; // Include SMS notification system

// Handle SMS settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'test_sms':
                $user_id = (int)$_POST['test_user_id'];
                $message = $_POST['test_message'];
                
                if (sendSMSNotification($user_id, $message, 'test')) {
                    $success_message = "Test SMS sent successfully!";
                } else {
                    $error_message = "Failed to send test SMS. Check logs for details.";
                }
                break;
                
            case 'send_bulk_sms':
                $message = $_POST['bulk_message'];
                $user_ids = $_POST['user_ids'];
                
                $success_count = 0;
                $total_count = count($user_ids);
                
                foreach ($user_ids as $user_id) {
                    if (sendSMSNotification($user_id, $message, 'bulk')) {
                        $success_count++;
                    }
                }
                
                $success_message = "Bulk SMS sent: {$success_count}/{$total_count} successful";
                break;
        }
    }
}

// Get all users for testing
$stmt = prepare_statement($conn, "SELECT user_id, first_name, last_name, phone, role FROM users ORDER BY role, first_name, last_name");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent SMS notifications
$stmt = prepare_statement($conn, "
    SELECT sn.*, u.first_name, u.last_name 
    FROM smsnotifications sn 
    JOIN users u ON sn.user_id = u.user_id 
    ORDER BY sn.sent_at DESC 
    LIMIT 20
");
$stmt->execute();
$recent_sms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get SMS statistics
$stmt = prepare_statement($conn, "SELECT COUNT(*) as total_sms FROM smsnotifications");
$stmt->execute();
$total_sms = $stmt->get_result()->fetch_assoc()['total_sms'];

$stmt = prepare_statement($conn, "SELECT COUNT(*) as today_sms FROM smsnotifications WHERE DATE(sent_at) = CURDATE()");
$stmt->execute();
$today_sms = $stmt->get_result()->fetch_assoc()['today_sms'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Settings - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .sms-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .sms-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sms-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .sms-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sms-table th, .sms-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .sms-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="sms-container">
            <h1>SMS Settings & Notifications</h1>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <!-- SMS Statistics -->
            <div class="sms-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo e($total_sms); ?></div>
                    <div>Total SMS Sent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo e($today_sms); ?></div>
                    <div>SMS Sent Today</div>
                </div>
            </div>
            
            <!-- Test SMS -->
            <div class="sms-card">
                <h3>Test SMS Functionality</h3>
                <form method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="test_sms">
                    
                    <div class="form-group">
                        <label for="test_user_id">Select User:</label>
                        <select name="test_user_id" id="test_user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo e($user['user_id']); ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['phone'] . ') - ' . ucfirst($user['role'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="test_message">Test Message:</label>
                        <textarea name="test_message" id="test_message" rows="3" required>Test message from BoteBank admin panel. This is a test SMS notification.</textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">Send Test SMS</button>
                </form>
            </div>
            
            <!-- Bulk SMS -->
            <div class="sms-card">
                <h3>Send Bulk SMS</h3>
                <form method="POST">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <input type="hidden" name="action" value="send_bulk_sms">
                    
                    <div class="form-group">
                        <label>Select Users:</label>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            <?php foreach ($users as $user): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="user_ids[]" value="<?php echo e($user['user_id']); ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['phone'] . ') - ' . ucfirst($user['role'])); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_message">Bulk Message:</label>
                        <textarea name="bulk_message" id="bulk_message" rows="3" required placeholder="Enter your bulk message here..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Send Bulk SMS</button>
                </form>
            </div>
            
            <!-- Recent SMS Notifications -->
            <div class="sms-card">
                <h3>Recent SMS Notifications</h3>
                <?php if (count($recent_sms) > 0): ?>
                    <table class="sms-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_sms as $sms): ?>
                                <tr>
                                    <td><?php echo e($sms['sms_id']); ?></td>
                                    <td><?php echo htmlspecialchars($sms['first_name'] . ' ' . $sms['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($sms['message'], 0, 50)) . (strlen($sms['message']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($sms['type']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($sms['sent_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No SMS notifications found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
