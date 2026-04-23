<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Services\NepaliDateService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $nepaliDateService;

    public function __construct(NepaliDateService $nepaliDateService)
    {
        $this->nepaliDateService = $nepaliDateService;
    }

    /**
     * Send a notification to a user
     */
    public function sendNotification(User $user, string $type, array $data, array $channels = ['web']): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title_np' => $data['title_np'] ?? $this->getDefaultTitleNp($type),
            'title_en' => $data['title_en'] ?? $this->getDefaultTitleEn($type),
            'message_np' => $data['message_np'] ?? $this->getDefaultMessageNp($type, $data),
            'message_en' => $data['message_en'] ?? $this->getDefaultMessageEn($type, $data),
            'data' => $data['notification_data'] ?? [],
            'related_model' => $data['related_model'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'channel' => in_array('web', $channels) ? 'web' : $channels[0],
            'priority' => $data['priority'] ?? 'normal',
        ]);

        // Send through each channel
        foreach ($channels as $channel) {
            $this->sendViaChannel($notification, $channel, $user);
        }

        $notification->sent_at = now();
        $notification->save();

        Log::info("Notification sent to user {$user->id} via channels: " . implode(', ', $channels), [
            'notification_id' => $notification->id,
            'type' => $type,
        ]);

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification(array $users, string $type, array $data, array $channels = ['web']): array
    {
        $notifications = [];

        foreach ($users as $user) {
            if ($user instanceof User) {
                $notifications[] = $this->sendNotification($user, $type, $data, $channels);
            }
        }

        return $notifications;
    }

    /**
     * Send notification via specific channel
     */
    protected function sendViaChannel(Notification $notification, string $channel, User $user): bool
    {
        try {
            switch ($channel) {
                case 'web':
                    // Web notifications are stored in database, already created
                    return true;

                case 'email':
                    return $this->sendEmail($notification, $user);

                case 'sms':
                    return $this->sendSms($notification, $user);

                case 'push':
                    return $this->sendPush($notification, $user);

                default:
                    Log::warning("Unknown notification channel: {$channel}");
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to send notification via channel {$channel}", [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmail(Notification $notification, User $user): bool
    {
        if (!$user->email) {
            Log::warning("User {$user->id} has no email address for email notification");
            return false;
        }

        $emailData = [
            'title_np' => $notification->title_np,
            'title_en' => $notification->title_en,
            'message_np' => $notification->message_np,
            'message_en' => $notification->message_en,
            'user' => $user,
            'notification' => $notification,
            'today_bs' => $this->nepaliDateService->adToBs(now()->format('Y-m-d')),
            'today_ad' => now()->format('Y-m-d'),
        ];

        try {
            Mail::send('emails.notification', $emailData, function ($message) use ($user, $notification) {
                $message->to($user->email)
                    ->subject($notification->title_en ?: $notification->title_np);
            });

            Log::info("Email sent to {$user->email} for notification {$notification->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$user->email}", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification
     */
    protected function sendSms(Notification $notification, User $user): bool
    {
        if (!$user->phone) {
            Log::warning("User {$user->id} has no phone number for SMS notification");
            return false;
        }

        // In production, integrate with SMS gateway like Sparrow SMS, etc.
        $message = $notification->message_np ?: $notification->message_en;
        $phone = $user->phone;

        // Mock SMS sending for now
        Log::info("SMS would be sent to {$phone}: {$message}");

        return true;
    }

    /**
     * Send push notification
     */
    protected function sendPush(Notification $notification, User $user): bool
    {
        // In production, integrate with Firebase Cloud Messaging or similar
        // For now, just log
        Log::info("Push notification would be sent to user {$user->id}");

        return true;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        if (!$notification->read_at) {
            $notification->read_at = now();
            return $notification->save();
        }

        return true;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get notifications for a user with pagination
     */
    public function getUserNotifications(User $user, int $limit = 20, bool $unreadOnly = false)
    {
        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->paginate($limit);
    }

    /**
     * Get default title in Nepali based on notification type
     */
    protected function getDefaultTitleNp(string $type): string
    {
        $titles = [
            'bid_submission' => 'बोलपत्र दाखिला',
            'bid_opening' => 'बोलपत्र खुलाइ',
            'contract_award' => 'सम्झौता प्रदान',
            'payment_approved' => 'भुक्तानी स्वीकृत',
            'work_completion' => 'कार्य पूर्णता',
            'deadline_reminder' => 'अन्तिम मिति स्मरण',
            'system_alert' => 'प्रणाली सचेतना',
            'approval_required' => 'स्वीकृति आवश्यक',
            'document_ready' => 'कागजात तयार',
        ];

        return $titles[$type] ?? 'सूचना';
    }

    /**
     * Get default title in English based on notification type
     */
    protected function getDefaultTitleEn(string $type): string
    {
        $titles = [
            'bid_submission' => 'Bid Submission',
            'bid_opening' => 'Bid Opening',
            'contract_award' => 'Contract Award',
            'payment_approved' => 'Payment Approved',
            'work_completion' => 'Work Completion',
            'deadline_reminder' => 'Deadline Reminder',
            'system_alert' => 'System Alert',
            'approval_required' => 'Approval Required',
            'document_ready' => 'Document Ready',
        ];

        return $titles[$type] ?? 'Notification';
    }

    /**
     * Get default message in Nepali based on notification type
     */
    protected function getDefaultMessageNp(string $type, array $data): string
    {
        $projectName = $data['project_name_np'] ?? 'परियोजना';
        $projectCode = $data['project_code'] ?? '';
        $dateBs = $data['date_bs'] ?? $this->nepaliDateService->adToBs(now()->format('Y-m-d'));

        $messages = [
            'bid_submission' => "{$projectName} ({$projectCode}) को लागि बोलपत्र दाखिला गर्नुहोस्। अन्तिम मिति: {$dateBs}",
            'bid_opening' => "{$projectName} ({$projectCode}) को बोलपत्र खुल्ने मिति: {$dateBs}",
            'contract_award' => "{$projectName} ({$projectCode}) को सम्झौता प्रदान गरिएको छ।",
            'payment_approved' => "{$projectName} ({$projectCode}) को भुक्तानी स्वीकृत गरिएको छ।",
            'work_completion' => "{$projectName} ({$projectCode}) को कार्य पूर्ण भएको छ।",
            'deadline_reminder' => "{$projectName} ({$projectCode}) को अन्तिम मिति नजिकिँदैछ: {$dateBs}",
            'system_alert' => "प्रणालीमा महत्वपूर्ण सचेतना: " . ($data['alert_message_np'] ?? 'कृपया जाँच गर्नुहोस्'),
            'approval_required' => "{$projectName} ({$projectCode}) को लागि तपाईंको स्वीकृति आवश्यक छ।",
            'document_ready' => "{$projectName} ({$projectCode}) को कागजात तयार भएको छ। डाउनलोड गर्नुहोस्।",
        ];

        return $messages[$type] ?? 'नयाँ सूचना आएको छ।';
    }

    /**
     * Get default message in English based on notification type
     */
    protected function getDefaultMessageEn(string $type, array $data): string
    {
        $projectName = $data['project_name_en'] ?? 'Project';
        $projectCode = $data['project_code'] ?? '';
        $dateAd = $data['date_ad'] ?? now()->format('Y-m-d');

        $messages = [
            'bid_submission' => "Please submit bid for {$projectName} ({$projectCode}). Deadline: {$dateAd}",
            'bid_opening' => "Bid opening for {$projectName} ({$projectCode}) on: {$dateAd}",
            'contract_award' => "Contract awarded for {$projectName} ({$projectCode}).",
            'payment_approved' => "Payment approved for {$projectName} ({$projectCode}).",
            'work_completion' => "Work completed for {$projectName} ({$projectCode}).",
            'deadline_reminder' => "Deadline approaching for {$projectName} ({$projectCode}): {$dateAd}",
            'system_alert' => "Important system alert: " . ($data['alert_message_en'] ?? 'Please check'),
            'approval_required' => "Your approval is required for {$projectName} ({$projectCode}).",
            'document_ready' => "Document ready for {$projectName} ({$projectCode}). Please download.",
        ];

        return $messages[$type] ?? 'New notification received.';
    }

    /**
     * Send deadline reminder notifications
     */
    public function sendDeadlineReminders(): array
    {
        // This would typically query database for upcoming deadlines
        // For now, return empty array
        return [];
    }

    /**
     * Send daily digest to users
     */
    public function sendDailyDigest(User $user): bool
    {
        $unreadCount = $this->getUnreadCount($user);
        
        if ($unreadCount === 0) {
            return false;
        }

        $data = [
            'title_np' => 'दैनिक सारांश',
            'title_en' => 'Daily Digest',
            'message_np' => "तपाईंसँग {$unreadCount} वटा नपढिएका सूचना छन्।",
            'message_en' => "You have {$unreadCount} unread notifications.",
            'unread_count' => $unreadCount,
        ];

        return $this->sendNotification($user, 'system_alert', $data, ['email'])->exists();
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        
        return Notification::where('created_at', '<', $cutoffDate)
            ->whereNotNull('read_at')
            ->delete();
    }
}