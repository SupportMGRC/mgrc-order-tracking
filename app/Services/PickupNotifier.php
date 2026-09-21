<?php

namespace App\Services;

use App\Mail\PickupNotification;
use App\Models\Pickup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Who gets an email for each pickup event:
 *
 *   new         -> requester (confirmation) + Dispatcher department
 *   on_the_way  -> requester + Dispatcher department
 *   picked_up   -> requester + Dispatcher department + receiving department
 *   received    -> requester + Dispatcher department
 *   cancelled   -> requester + Dispatcher department
 *
 * The person who pressed the button is not emailed about their own action,
 * except the requester, who gets a confirmation of the pickup they created.
 */
class PickupNotifier
{
    public const EVENTS = [
        'new' => [
            'subject'  => 'New Pickup Request',
            'headline' => 'New Pickup Request',
            'intro'    => 'A new pickup has been requested and is waiting for a despatcher.',
        ],
        Pickup::STATUS_ON_THE_WAY => [
            'subject'  => 'On the Way',
            'headline' => 'Despatcher On the Way',
            'intro'    => 'A despatcher has taken this pickup and is on the way to collect it.',
        ],
        Pickup::STATUS_PICKED_UP => [
            'subject'  => 'Picked Up',
            'headline' => 'Items Picked Up',
            'intro'    => 'The items have been collected and are on their way to MGRC.',
        ],
        Pickup::STATUS_RECEIVED => [
            'subject'  => 'Received at MGRC',
            'headline' => 'Received at MGRC',
            'intro'    => 'The items have been received at MGRC. This pickup is complete.',
        ],
        Pickup::STATUS_CANCELLED => [
            'subject'  => 'Cancelled',
            'headline' => 'Pickup Cancelled',
            'intro'    => 'This pickup has been cancelled.',
        ],
    ];

    public function send(?Pickup $pickup, string $event, ?int $actorId = null, ?string $previousStatus = null): void
    {
        if (!$pickup || !isset(self::EVENTS[$event])) {
            return;
        }

        try {
            $recipients = $this->recipients($pickup, $event)
                ->filter(function (User $u) use ($pickup, $event, $actorId) {
                    if (empty($u->email)) {
                        return false;
                    }
                    // Requester always gets the confirmation of their own new pickup.
                    if ($event === 'new' && (int) $u->id === (int) $pickup->user_id) {
                        return true;
                    }
                    return (int) $u->id !== (int) $actorId;
                })
                ->unique(fn (User $u) => strtolower($u->email));

            if ($recipients->isEmpty()) {
                Log::info("Pickup {$pickup->reference_no}: no recipients for '{$event}' email.");
                return;
            }

            foreach ($recipients as $user) {
                try {
                    Mail::to($user->email)->send(new PickupNotification(
                        $pickup,
                        $event,
                        self::EVENTS[$event],
                        $user->username,
                        $this->reasonFor($user, $pickup)
                    ));
                    Log::info("Pickup {$pickup->reference_no}: '{$event}' email sent to {$user->email}");
                } catch (\Throwable $e) {
                    Log::error("Pickup {$pickup->reference_no}: '{$event}' email to {$user->email} failed: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("Pickup notification error ({$event}): " . $e->getMessage());
        }
    }

    private function recipients(Pickup $pickup, string $event): Collection
    {
        $requester = $pickup->user_id ? User::whereKey($pickup->user_id)->get() : collect();
        $despatchers = $this->department([Pickup::DESPATCH_DEPARTMENT]);

        $list = $requester->merge($despatchers);

        // Receiving department also hears when tubes are on their way in.
        if ($event === Pickup::STATUS_PICKED_UP) {
            $list = $list->merge($this->department(Pickup::RECEIVING_DEPARTMENTS));
        }

        return $list;
    }

    /** The "You are receiving this email because..." line, like order emails. */
    private function reasonFor(User $user, Pickup $pickup): string
    {
        if ((int) $user->id === (int) $pickup->user_id) {
            return 'You are receiving this email because you requested this pickup in the MGRC Order Tracking system.';
        }

        return 'You are receiving this email because you are in the ' . $user->department
            . ' department in the MGRC Order Tracking system.';
    }

    private function department(array $departments): Collection
    {
        return User::whereIn('department', $departments)->whereNotNull('email')->get();
    }
}
