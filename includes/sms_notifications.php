<?php
/**
 * SMS Notification System for BoteBank
 * Handles automatic SMS notifications for pickup requests
 */

require_once __DIR__ . '/../sms/vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use AndroidSmsGateway\Client;
use AndroidSmsGateway\Domain\Message;

class SMSNotification {
    private $conn;
    private $client;
    private $username;
    private $password;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        // SMS Gateway credentials from environment
        $this->username = (string) env('SMS_GATEWAY_USERNAME', '');
        $this->password = (string) env('SMS_GATEWAY_PASSWORD', '');
        
        try {
            $this->client = new Client($this->username, $this->password);
        } catch (Exception $e) {
            error_log("SMS Client initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send SMS notification to a user
     */
    public function sendSMS($user_id, $message, $type = 'pickup_update') {
        try {
            // Get user phone number
            $stmt = $this->conn->prepare("SELECT phone, first_name, last_name FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (!$user || empty($user['phone'])) {
                error_log("No phone number found for user ID: $user_id");
                return false;
            }
            
            // Format phone number for Philippines
            $phone = $this->formatPhoneNumber($user['phone']);
            if (!$phone) {
                error_log("Invalid phone number format for user ID: $user_id");
                return false;
            }
            
            // Send SMS (will appear to come from admin's phone if configured in gateway)
            $smsMessage = new Message($message, [$phone]);
            $messageState = $this->client->Send($smsMessage);
            
            // Log SMS notification in database
            $this->logSMSNotification($user_id, $message, $type, $messageState->ID());
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get admin name for SMS signature
     */
    private function getAdminName() {
        try {
            $stmt = $this->conn->prepare("SELECT first_name, last_name FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            return $admin ? "{$admin['first_name']} {$admin['last_name']}" : "BoteBank Admin";
        } catch (Exception $e) {
            return "BoteBank Admin";
        }
    }
    
    /**
     * Format phone number for Philippines (+63 format)
     */
    private function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Handle different formats
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '9') {
            // Format: 9XXXXXXXXX -> +639XXXXXXXXX
            return '+63' . $phone;
        } elseif (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
            // Format: 09XXXXXXXXX -> +639XXXXXXXXX
            return '+63' . substr($phone, 1);
        } elseif (strlen($phone) == 13 && substr($phone, 0, 3) == '639') {
            // Format: 639XXXXXXXXX -> +639XXXXXXXXX
            return '+' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 2) == '63') {
            // Format: 63XXXXXXXXX -> +639XXXXXXXXX
            return '+' . $phone;
        }
        
        return false;
    }
    
    /**
     * Log SMS notification in database
     */
    private function logSMSNotification($user_id, $message, $type, $sms_id) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO smsnotifications (user_id, message, type, sms_id, sent_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("issi", $user_id, $message, $type, $sms_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log SMS notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send pickup request confirmation SMS
     */
    public function sendPickupRequestConfirmation($request_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.resident_id, pr.preferred_date, rt.name as type_name, u.first_name, u.last_name, u.phone
                FROM pickuprequests pr 
                JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
                JOIN users u ON pr.resident_id = u.user_id 
                WHERE pr.request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if ($request) {
                $admin_name = $this->getAdminName();
                $message = "Hi {$request['first_name']}! Your pickup request for {$request['type_name']} has been submitted successfully. We'll contact you within 24 hours. Thank you for helping our community! - {$admin_name}";
                return $this->sendSMS($request['resident_id'], $message, 'pickup_confirmation');
            }
        } catch (Exception $e) {
            error_log("Failed to send pickup confirmation SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send pickup assigned SMS
     */
    public function sendPickupAssigned($request_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.resident_id, pr.preferred_date, rt.name as type_name, u.first_name, u.last_name, u.phone
                FROM pickuprequests pr 
                JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
                JOIN users u ON pr.resident_id = u.user_id 
                WHERE pr.request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if ($request) {
                $preferred_date = date('M d, Y', strtotime($request['preferred_date']));
                $message = "Hi {$request['first_name']}! Your {$request['type_name']} pickup has been assigned and will be collected on {$preferred_date}. Please prepare your recyclables. Thank you!";
                return $this->sendSMS($request['resident_id'], $message, 'pickup_assigned');
            }
        } catch (Exception $e) {
            error_log("Failed to send pickup assigned SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send pickup completed SMS
     */
    public function sendPickupCompleted($request_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.resident_id, pr.actual_weight, pr.points_earned, rt.name as type_name, u.first_name, u.last_name, u.phone
                FROM pickuprequests pr 
                JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
                JOIN users u ON pr.resident_id = u.user_id 
                WHERE pr.request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if ($request) {
                $message = "Hi {$request['first_name']}! Your {$request['type_name']} pickup has been completed successfully. Weight: {$request['actual_weight']}kg. You earned {$request['points_earned']} points! Thank you for your contribution to our green community!";
                return $this->sendSMS($request['resident_id'], $message, 'pickup_completed');
            }
        } catch (Exception $e) {
            error_log("Failed to send pickup completed SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send pickup status update SMS
     */
    public function sendPickupStatusUpdate($request_id, $status) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.resident_id, rt.name as type_name, u.first_name, u.last_name, u.phone
                FROM pickuprequests pr 
                JOIN recyclabletypes rt ON pr.type_id = rt.type_id 
                JOIN users u ON pr.resident_id = u.user_id 
                WHERE pr.request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if ($request) {
                $status_messages = [
                    'Pending' => "Your {$request['type_name']} pickup request is being reviewed.",
                    'Scheduled' => "Your {$request['type_name']} pickup has been scheduled.",
                    'Completed' => "Your {$request['type_name']} pickup has been completed successfully."
                ];
                
                $message = "Hi {$request['first_name']}! " . ($status_messages[$status] ?? "Your pickup request status has been updated to: {$status}");
                return $this->sendSMS($request['resident_id'], $message, 'pickup_status_update');
            }
        } catch (Exception $e) {
            error_log("Failed to send pickup status update SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send drop-off submission confirmation SMS
     */
    public function sendDropoffSubmission($dropoff_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT dd.resident_id, dd.points_earned, rt.name as type_name, 
                       cp.name as collection_point, u.first_name, u.last_name, u.phone
                FROM direct_dropoffs dd
                JOIN recyclabletypes rt ON dd.type_id = rt.type_id
                JOIN collection_points cp ON dd.point_id = cp.point_id
                JOIN users u ON dd.resident_id = u.user_id
                WHERE dd.dropoff_id = ?
            ");
            $stmt->bind_param("i", $dropoff_id);
            $stmt->execute();
            $dropoff = $stmt->get_result()->fetch_assoc();
            
            if ($dropoff) {
                $admin_name = $this->getAdminName();
                $message = "Hi {$dropoff['first_name']}! Your drop-off of {$dropoff['type_name']} at {$dropoff['collection_point']} has been submitted successfully. Expected points: {$dropoff['points_earned']}. Please wait for staff verification. Thank you! - {$admin_name}";
                return $this->sendSMS($dropoff['resident_id'], $message, 'dropoff_submission');
            }
        } catch (Exception $e) {
            error_log("Failed to send drop-off submission SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send drop-off verification SMS
     */
    public function sendDropoffVerification($dropoff_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT dd.resident_id, dd.points_earned, dd.weight_kg, dd.quantity_pcs,
                       rt.name as type_name, rt.unit, u.first_name, u.last_name, u.phone
                FROM direct_dropoffs dd
                JOIN recyclabletypes rt ON dd.type_id = rt.type_id
                JOIN users u ON dd.resident_id = u.user_id
                WHERE dd.dropoff_id = ?
            ");
            $stmt->bind_param("i", $dropoff_id);
            $stmt->execute();
            $dropoff = $stmt->get_result()->fetch_assoc();
            
            if ($dropoff) {
                $quantity_info = "";
                if (!empty($dropoff['weight_kg']) && $dropoff['weight_kg'] > 0) {
                    $quantity_info = "Weight: {$dropoff['weight_kg']}kg";
                } elseif (!empty($dropoff['quantity_pcs']) && $dropoff['quantity_pcs'] > 0) {
                    $quantity_info = "Quantity: {$dropoff['quantity_pcs']} pieces";
                }
                
                $message = "Hi {$dropoff['first_name']}! Your {$dropoff['type_name']} drop-off has been verified and approved! {$quantity_info}. You earned {$dropoff['points_earned']} points! Thank you for your contribution to our green community!";
                return $this->sendSMS($dropoff['resident_id'], $message, 'dropoff_verified');
            }
        } catch (Exception $e) {
            error_log("Failed to send drop-off verification SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send drop-off rejection SMS
     */
    public function sendDropoffRejection($dropoff_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT dd.resident_id, dd.verification_notes, rt.name as type_name, 
                       u.first_name, u.last_name, u.phone
                FROM direct_dropoffs dd
                JOIN recyclabletypes rt ON dd.type_id = rt.type_id
                JOIN users u ON dd.resident_id = u.user_id
                WHERE dd.dropoff_id = ?
            ");
            $stmt->bind_param("i", $dropoff_id);
            $stmt->execute();
            $dropoff = $stmt->get_result()->fetch_assoc();
            
            if ($dropoff) {
                $reason = !empty($dropoff['verification_notes']) ? "Reason: {$dropoff['verification_notes']}" : "Please contact us for more details.";
                $message = "Hi {$dropoff['first_name']}! Your {$dropoff['type_name']} drop-off could not be verified. {$reason} Please try again or contact our staff for assistance.";
                return $this->sendSMS($dropoff['resident_id'], $message, 'dropoff_rejected');
            }
        } catch (Exception $e) {
            error_log("Failed to send drop-off rejection SMS: " . $e->getMessage());
        }
        return false;
    }
    
    /**
     * Send admin notification when worker completes pickup
     */
    public function sendAdminPickupCompletion($request_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT pr.resident_id, pr.actual_weight, pr.points_earned, 
                       rt.name as type_name, u.first_name, u.last_name, u.phone,
                       CONCAT(w.first_name, ' ', w.last_name) as worker_name
                FROM pickuprequests pr
                JOIN recyclabletypes rt ON pr.type_id = rt.type_id
                JOIN users u ON pr.resident_id = u.user_id
                JOIN users w ON pr.assigned_worker_id = w.user_id
                WHERE pr.request_id = ?
            ");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $pickup = $stmt->get_result()->fetch_assoc();
            
            if ($pickup) {
                // Get admin phone number
                $admin_stmt = $this->conn->prepare("SELECT user_id, phone, first_name, last_name FROM users WHERE role = 'admin' LIMIT 1");
                $admin_stmt->execute();
                $admin = $admin_stmt->get_result()->fetch_assoc();
                
                if ($admin && !empty($admin['phone'])) {
                    $message = "ADMIN ALERT: Worker {$pickup['worker_name']} completed pickup for {$pickup['first_name']} {$pickup['last_name']}. Material: {$pickup['type_name']}, Weight: {$pickup['actual_weight']}kg, Points: {$pickup['points_earned']}. Request ID: #{$request_id}";
                    
                    // Send to admin
                    $admin_phone = $this->formatPhoneNumber($admin['phone']);
                    if ($admin_phone) {
                        $smsMessage = new Message($message, [$admin_phone]);
                        $messageState = $this->client->Send($smsMessage);
                        $this->logSMSNotification($admin['user_id'], $message, 'admin_pickup_completion', $messageState->ID());
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to send admin pickup completion SMS: " . $e->getMessage());
        }
        return false;
    }
}

// Global function for easy access
function sendSMSNotification($user_id, $message, $type = 'pickup_update') {
    $sms = new SMSNotification();
    return $sms->sendSMS($user_id, $message, $type);
}

function sendPickupConfirmationSMS($request_id) {
    $sms = new SMSNotification();
    return $sms->sendPickupRequestConfirmation($request_id);
}

function sendPickupAssignedSMS($request_id) {
    $sms = new SMSNotification();
    return $sms->sendPickupAssigned($request_id);
}

function sendPickupCompletedSMS($request_id) {
    $sms = new SMSNotification();
    return $sms->sendPickupCompleted($request_id);
}

function sendPickupStatusUpdateSMS($request_id, $status) {
    $sms = new SMSNotification();
    return $sms->sendPickupStatusUpdate($request_id, $status);
}

/**
 * Send drop-off submission confirmation SMS
 */
function sendDropoffSubmissionSMS($dropoff_id) {
    $sms = new SMSNotification();
    return $sms->sendDropoffSubmission($dropoff_id);
}

/**
 * Send drop-off verification SMS
 */
function sendDropoffVerificationSMS($dropoff_id) {
    $sms = new SMSNotification();
    return $sms->sendDropoffVerification($dropoff_id);
}

/**
 * Send drop-off rejection SMS
 */
function sendDropoffRejectionSMS($dropoff_id) {
    $sms = new SMSNotification();
    return $sms->sendDropoffRejection($dropoff_id);
}

/**
 * Send admin notification when worker completes pickup
 */
function sendAdminPickupCompletionSMS($request_id) {
    $sms = new SMSNotification();
    return $sms->sendAdminPickupCompletion($request_id);
}
?>
