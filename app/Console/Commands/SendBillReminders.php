<?php

namespace App\Console\Commands;

use App\Models\BillReminder;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendBillReminders extends Command
{
    protected $signature = 'bills:remind';
    protected $description = 'Send push notifications for upcoming and overdue bill reminders';

    public function handle(PushNotificationService $notificationService): int
    {
        $today = now()->startOfDay();

        // Get unpaid bills due in N days or overdue
        $bills = BillReminder::with(['user', 'category'])
            ->where('is_paid', false)
            ->get();

        $sentCount = 0;

        foreach ($bills as $bill) {
            $user = $bill->user;
            if (!$user || !$user->device_token) {
                continue;
            }

            $daysUntil = (int) $today->diffInDays($bill->due_date, false);

            if ($daysUntil < 0) {
                // Overdue bill
                $title = "⚠️ Bill Overdue: {$bill->name}";
                $body = "Your bill of {$bill->currency_code} {$bill->amount} was due on {$bill->due_date->toDateString()}. Tap to mark as paid.";
                $notificationService->sendToUser($user, $title, $body, [
                    'type' => 'bill_overdue',
                    'bill_id' => (string)$bill->id,
                ]);
                $sentCount++;
            } else if ($daysUntil <= $bill->remind_days_before) {
                // Upcoming bill due soon
                $dueStr = $daysUntil === 0 ? "today" : ($daysUntil === 1 ? "tomorrow" : "in {$daysUntil} days");
                $title = "🔔 Upcoming Bill: {$bill->name}";
                $body = "{$bill->name} ({$bill->currency_code} {$bill->amount}) is due {$dueStr}.";
                $notificationService->sendToUser($user, $title, $body, [
                    'type' => 'bill_upcoming',
                    'bill_id' => (string)$bill->id,
                ]);
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} bill reminder push notifications.");
        return Command::SUCCESS;
    }
}
