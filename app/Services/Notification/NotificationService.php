<?php
namespace App\Services\Notification;

use App\Config\Database;
use App\Models\Notification;
use App\Services\Notification\SMSService;
use App\Services\Notification\EmailService;

class NotificationService {
    public function createNotification(
        int $userId,
        string $title,
        string $message,
        string $type = 'system',
        array $metadata = []
    ): int {
        $db = Database::getInstance();
        
        $notificationId = (new Notification())->create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'metadata' => $metadata
        ]);
        
        // Send real-time notification if possible (WebSocket, etc.)
        $this->sendRealTimeNotification($userId, $title);
        
        return $notificationId;
    }
    
    public function sendUserNotification(
        int $userId,
        string $title,
        string $message,
        string $via = 'all',
        array $metadata = []
    ): bool {
        $user = (new \App\Models\User())->findById($userId);
        if (!$user) {
            return false;
        }
        
        $notificationId = $this->createNotification($userId, $title, $message, 'user', $metadata);
        
        // Send via selected channels
        if ($via === 'all' || $via === 'sms') {
            (new SMSService())->sendSMS($user['phone'], $message);
        }
        
        if (($via === 'all' || $via === 'email') && !empty($user['email'])) {
            (new EmailService())->sendEmail(
                $user['email'],
                $title,
                $message,
                $metadata
            );
        }
        
        return true;
    }
    
    private function sendRealTimeNotification(int $userId, string $message): void {
        // Implement WebSocket or other real-time notification system
        // This is just a placeholder for the actual implementation
        try {
            // Example: Send via Pusher, Socket.io, etc.
            // $pusher->trigger("user-$userId", 'notification', ['message' => $message]);
        } catch (\Exception $e) {
            error_log("Real-time notification failed: " . $e->getMessage());
        }
    }
    
    public function markAsRead(int $notificationId, int $userId): bool {
        return (new Notification())->update($notificationId, [
            'is_read' => true,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
}