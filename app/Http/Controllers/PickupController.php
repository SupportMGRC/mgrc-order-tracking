<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use App\Models\Customer;
use App\Models\Pickup;
use App\Models\PickupItem;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\PickupNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PickupController extends Controller
{
    /**
     * Same rule as Order History: these departments see only the pickups
     * they requested. Everyone else, and admin/superadmin, sees all.
     * Kept in step with OrderController::OWN_ORDERS_ONLY_DEPARTMENTS.
     */
    private const OWN_PICKUPS_ONLY_DEPARTMENTS = [
        'medical affairs',
        'business development',
    ];

    private function restrictedToOwnPickups(): bool
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['admin', 'superadmin'], true)) {
            return false;
        }

        $department = strtolower(trim((string) $user->department));

        return in_array($department, self::OWN_PICKUPS_ONLY_DEPARTMENTS, true);
    }

    private function isAdmin(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'superadmin'], true);
    }

    /** Same rule as Mark as Delivered: Dispatcher department or admin/superadmin. */
    private function canDespatch(): bool
    {
        return Auth::user()->department === Pickup::DESPATCH_DEPARTMENT || $this->isAdmin();
    }

    private function canReceive(): bool
    {
        return in_array(Auth::user()->department, Pickup::RECEIVING_DEPARTMENTS, true) || $this->isAdmin();
    }

    /**
     * The requester can cancel while the pickup is still New. The despatcher
     * side can cancel until the items are collected. After Picked Up the tubes
     * are in transit, so the only way forward is Received.
     */
    private function canCancel(Pickup $pickup): bool
    {
        if ($pickup->status === Pickup::STATUS_NEW && (int) $pickup->user_id === (int) Auth::id()) {
            return true;
        }

        return $this->canDespatch()
            && in_array($pickup->status, [Pickup::STATUS_NEW, Pickup::STATUS_ON_THE_WAY], true);
    }

    /** Which action buttons the details page shows for this user. */
    private function permissionsFor(Pickup $pickup): array
    {
        return [
            'on_the_way' => $pickup->status === Pickup::STATUS_NEW && $this->canDespatch(),
            'picked_up'  => $pickup->status === Pickup::STATUS_ON_THE_WAY && $this->canDespatch(),
            'received'   => $pickup->status === Pickup::STATUS_PICKED_UP && $this->canReceive(),
            'cancel'     => $this->canCancel($pickup),
        ];
    }

    /** MA/BD may only open pickups they requested. Returns a redirect if refused. */
    private function refuseIfNotVisible(Pickup $pickup)
    {
        if ($this->restrictedToOwnPickups() && (int) $pickup->user_id !== (int) Auth::id()) {
            Log::warning('User attempted to access a pickup they did not request', [
                'user_id'   => Auth::id(),
                'pickup_id' => $pickup->id,
            ]);

            return redirect()->route('pickups.index')
                ->with('error', 'You can only view pickups that you have requested.');
        }

        return null;
    }

    /**
     * Runs a status change inside a transaction with the row locked, so two
     * people pressing the same button cannot both move the pickup on.
     * $apply receives the locked, fresh Pickup and must set its fields.
     */
    private function transition(Pickup $pickup, string $from, callable $apply)
    {
        return DB::transaction(function () use ($pickup, $from, $apply) {
            $locked = Pickup::whereKey($pickup->id)->lockForUpdate()->first();

            if (!$locked || $locked->status !== $from) {
                return false;
            }

            $apply($locked);
            $locked->save();

            return true;
        });
    }

    private function userName(): string
    {
        return (string) Auth::user()->username;
    }

    /**
     * Emails go out after the response is sent, same as order emails, so the
     * user does not wait on the mail server.
     */
    private function notify(Pickup $pickup, string $event, ?string $previousStatus = null): void
    {
        $actorId = Auth::id();

        dispatch(function () use ($pickup, $event, $previousStatus, $actorId) {
            (new PickupNotifier())->send($pickup->fresh(['customer', 'items.product']), $event, $actorId, $previousStatus);
        })->afterResponse();
    }

    /** Date & time from the modals: "2026-09-22 14:30". */
    private function parseDateTime(string $value): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $value);
    }

    /**
     * New Pickup form. Open to every authenticated user.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::forPickups()->orderBy('name')->get();
        $blockedDates = BlockedDate::getBlockedDatesArray();

        return view('pickups.newpickup', compact('customers', 'products', 'blockedDates'));
    }

    public function store(Request $request)
    {
        if ($request->filled('pickup_date') && BlockedDate::isDateBlocked($request->pickup_date)) {
            $blocked = BlockedDate::where('blocked_date', $request->pickup_date)
                ->where('is_active', true)
                ->first();
            $reason = $blocked ? $blocked->reason : 'Holiday/Maintenance';

            return redirect()->back()->withInput()->with(
                'error',
                'Pickups cannot be scheduled for ' . Carbon::parse($request->pickup_date)->format('d/m/Y') . ". Reason: {$reason}"
            );
        }

        $validator = Validator::make($request->all(), [
            'customer_id'        => 'nullable|exists:customers,id',
            'customer_name'      => 'required|string|max:255',
            'customer_email'     => 'nullable|email|max:255',
            'customer_phone'     => 'required|string|max:20',
            'customer_address'   => 'required|string',
            'contact_person'     => 'nullable|string|max:255',
            'pickup_address'     => 'required|string',
            'pickup_date'        => 'required|date|after_or_equal:today',
            'pickup_time'        => 'required|date_format:g:i A',
            'remarks'            => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('usage_type', Product::USAGE_PICKUP),
            ],
            'items.*.quantity'   => 'required|integer|min:1|max:999',
        ], [
            'customer_name.required'      => 'The customer name is required.',
            'customer_phone.required'     => 'The phone number is required.',
            'customer_address.required'   => 'The customer address is required.',
            'pickup_address.required'     => 'The pickup address is required.',
            'pickup_date.required'        => 'The pickup date is required.',
            'pickup_date.after_or_equal'  => 'The pickup date cannot be in the past.',
            'pickup_time.required'        => 'The pickup time is required.',
            'pickup_time.date_format'     => 'The pickup time must look like 02:30 PM.',
            'items.required'              => 'Add at least one item.',
            'items.*.product_id.required' => 'Select an item for every line.',
            'items.*.product_id.exists'   => 'One of the selected items is not a pickup item.',
            'items.*.quantity.required'   => 'Enter a quantity for every line.',
            'items.*.quantity.min'        => 'Quantity must be at least 1.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the errors in the form.');
        }

        $data = $validator->validated();

        DB::beginTransaction();

        try {
            if (!empty($data['customer_id'])) {
                $customerId = $data['customer_id'];
            } else {
                $customer = new Customer();
                $customer->name = $data['customer_name'];
                $customer->email = $data['customer_email'] ?? null;
                $customer->phoneNo = $data['customer_phone'];
                $customer->address = $data['customer_address'];
                $customer->userID = Auth::id();
                $customer->save();

                $customerId = $customer->id;
            }

            // Created without the audit observer, then logged once below with
            // the reference number set, instead of "Created Pickup #12"
            // followed by "Updated reference_no".
            $pickup = Pickup::withoutEvents(fn () => Pickup::create([
                'customer_id'    => $customerId,
                'user_id'        => Auth::id(),
                'requested_by'   => Auth::user()->username,
                'contact_person' => $data['contact_person'] ?? null,
                'contact_phone'  => $data['customer_phone'],
                'pickup_address' => $data['pickup_address'],
                'pickup_date'    => $data['pickup_date'],
                'pickup_time'    => Carbon::createFromFormat('g:i A', $data['pickup_time'])->format('H:i:s'),
                'time_sensitive' => $request->boolean('time_sensitive'),
                'remarks'        => $data['remarks'] ?? null,
                'status'         => Pickup::STATUS_NEW,
            ]));

            $pickup->reference_no = Pickup::makeReference($pickup->id, $pickup->created_at);
            $pickup->saveQuietly();

            foreach ($data['items'] as $line) {
                PickupItem::create([
                    'pickup_id'  => $pickup->id,
                    'product_id' => $line['product_id'],
                    'quantity'   => (int) $line['quantity'],
                ]);
            }

            DB::commit();

            ActivityLogger::record('created', $pickup);
            $this->notify($pickup, 'new');

            return redirect()->route('pickups.show', $pickup->id)
                ->with('success', 'Pickup ' . $pickup->reference_no . ' created successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pickup creation failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Pickup could not be saved. Please try again.');
        }
    }

    /**
     * Pickup History.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $restrict = $this->restrictedToOwnPickups();

        // Base query with every filter except status, so the tab counts
        // reflect the same search and date range as the list.
        $base = Pickup::query();

        if ($restrict) {
            $base->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $base->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('requested_by', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%");
                    });
            });
        }

        switch ($request->get('date_range', 'all')) {
            case 'today':
                $base->whereDate('pickup_date', Carbon::today());
                break;
            case 'weekly':
                $base->whereBetween('pickup_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'monthly':
                $base->whereBetween('pickup_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
                break;
            case 'custom':
                if ($request->filled('date_from')) {
                    $base->whereDate('pickup_date', '>=', Carbon::parse($request->date_from));
                }
                if ($request->filled('date_to')) {
                    $base->whereDate('pickup_date', '<=', Carbon::parse($request->date_to));
                }
                break;
        }

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $status = $request->get('status', 'all');
        $query = (clone $base)->with(['customer', 'items.product']);

        if (array_key_exists($status, Pickup::STATUSES)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $pickups = $query
            ->orderByDesc('pickup_date')
            ->orderByDesc('pickup_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $allCount = $counts->sum();

        return view('pickups.history', compact('pickups', 'counts', 'allCount', 'status'));
    }

    /**
     * Pickup details.
     */
    public function show(Pickup $pickup)
    {
        if ($refused = $this->refuseIfNotVisible($pickup)) {
            return $refused;
        }

        $pickup->load(['customer', 'user', 'items.product']);
        $can = $this->permissionsFor($pickup);

        return view('pickups.details', compact('pickup', 'can'));
    }

    /**
     * New -> On the Way. The despatcher taking the job is recorded.
     */
    public function markOnTheWay(Pickup $pickup)
    {
        if (!$this->canDespatch()) {
            return redirect()->back()->with('error', 'Only the Dispatcher department or an admin can take a pickup.');
        }

        $done = $this->transition($pickup, Pickup::STATUS_NEW, function (Pickup $p) {
            $p->status = Pickup::STATUS_ON_THE_WAY;
            $p->on_the_way_at = now();
            $p->despatcher_id = Auth::id();
            $p->despatcher_name = $this->userName();
        });

        if ($done) {
            $this->notify($pickup, Pickup::STATUS_ON_THE_WAY);
        }

        return $done
            ? redirect()->route('pickups.show', $pickup->id)->with('success', 'Pickup marked as On the Way.')
            : redirect()->route('pickups.show', $pickup->id)->with('error', 'This pickup is no longer New. Someone may have updated it already.');
    }

    /**
     * On the Way -> Picked Up, with photos and handover details.
     */
    public function markPickedUp(Request $request, Pickup $pickup)
    {
        set_time_limit(300);

        if (!$this->canDespatch()) {
            return redirect()->back()->with('error', 'Only the Dispatcher department or an admin can confirm a pickup.');
        }

        $validator = Validator::make($request->all(), [
            'pickup_photos'      => 'required|array|min:1|max:5',
            // mimes rather than image: the image rule rejects HEIC from iPhones.
            'pickup_photos.*'    => 'file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'picked_up_at'       => 'required|date_format:Y-m-d H:i|before_or_equal:now',
            'handed_over_by'     => 'required|string|max:255',
            'pickup_temperature' => 'nullable|string|max:20',
            'items_confirmed'    => 'accepted',
            'pickup_remarks'     => 'nullable|string|max:2000',
        ], [
            'pickup_photos.required'         => 'Take at least one photo of the collected items.',
            'pickup_photos.max'              => 'Upload up to 5 photos.',
            'pickup_photos.*.mimes'          => 'Photos must be JPEG, PNG, GIF, WebP or HEIC.',
            'pickup_photos.*.max'            => 'Each photo must be 10MB or smaller.',
            'picked_up_at.required'          => 'Enter the pickup date and time.',
            'picked_up_at.before_or_equal'   => 'Pickup time cannot be in the future.',
            'handed_over_by.required'        => 'Enter the name of the person who handed over the items.',
            'items_confirmed.accepted'       => 'Confirm the items and quantities collected.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pickups.show', $pickup->id)
                ->withErrors($validator, 'pickedUp')
                ->withInput();
        }

        // Save the photos first. If the status change then fails, remove them.
        $saved = [];
        try {
            foreach ($request->file('pickup_photos') as $i => $file) {
                $saved[] = $this->storePhoto($file, $pickup->id, $i);
            }
        } catch (\Throwable $e) {
            $this->deletePhotos($saved);
            Log::error('Pickup photo upload failed', ['pickup_id' => $pickup->id, 'error' => $e->getMessage()]);

            return redirect()->route('pickups.show', $pickup->id)
                ->withInput()
                ->with('error', 'Photo upload failed. Please try again.');
        }

        $done = $this->transition($pickup, Pickup::STATUS_ON_THE_WAY, function (Pickup $p) use ($request, $saved) {
            $p->status = Pickup::STATUS_PICKED_UP;
            $p->picked_up_at = $this->parseDateTime($request->picked_up_at);
            $p->picked_up_by_id = Auth::id();
            $p->picked_up_by_name = $this->userName();
            $p->handed_over_by = $request->handed_over_by;
            $p->pickup_temperature = $request->pickup_temperature;
            $p->pickup_photos = $saved;
            $p->pickup_remarks = $request->pickup_remarks;
        });

        if (!$done) {
            $this->deletePhotos($saved);

            return redirect()->route('pickups.show', $pickup->id)
                ->with('error', 'This pickup is no longer On the Way. Someone may have updated it already.');
        }

        $this->notify($pickup, Pickup::STATUS_PICKED_UP);

        return redirect()->route('pickups.show', $pickup->id)->with('success', 'Pickup marked as Picked Up.');
    }

    /**
     * Picked Up -> Received, by the receiving department at MGRC.
     */
    public function markReceived(Request $request, Pickup $pickup)
    {
        if (!$this->canReceive()) {
            return redirect()->back()->with('error', 'Only the receiving department or an admin can mark a pickup as Received.');
        }

        $pickedUpAt = $pickup->picked_up_at ? $pickup->picked_up_at->format('Y-m-d H:i') : null;

        $validator = Validator::make($request->all(), [
            'received_at'          => array_filter([
                'required',
                'date_format:Y-m-d H:i',
                'before_or_equal:now',
                $pickedUpAt ? 'after_or_equal:' . $pickedUpAt : null,
            ]),
            'received_temperature' => 'nullable|string|max:20',
            'items_confirmed'      => 'accepted',
            'received_remarks'     => 'nullable|string|max:2000',
        ], [
            'received_at.required'        => 'Enter the date and time received.',
            'received_at.before_or_equal' => 'Received time cannot be in the future.',
            'received_at.after_or_equal'  => 'Received time cannot be before the pickup time.',
            'items_confirmed.accepted'    => 'Confirm the items and quantities received.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pickups.show', $pickup->id)
                ->withErrors($validator, 'received')
                ->withInput();
        }

        $done = $this->transition($pickup, Pickup::STATUS_PICKED_UP, function (Pickup $p) use ($request) {
            $p->status = Pickup::STATUS_RECEIVED;
            $p->received_at = $this->parseDateTime($request->received_at);
            $p->received_by_id = Auth::id();
            $p->received_by_name = $this->userName();
            $p->received_temperature = $request->received_temperature;
            $p->received_remarks = $request->received_remarks;
        });

        if ($done) {
            $this->notify($pickup, Pickup::STATUS_RECEIVED);
        }

        return $done
            ? redirect()->route('pickups.show', $pickup->id)->with('success', 'Pickup marked as Received.')
            : redirect()->route('pickups.show', $pickup->id)->with('error', 'This pickup is no longer Picked Up. Someone may have updated it already.');
    }

    /**
     * Cancel, with a reason. See canCancel() for who and when.
     */
    public function cancel(Request $request, Pickup $pickup)
    {
        if ($refused = $this->refuseIfNotVisible($pickup)) {
            return $refused;
        }

        if (!$this->canCancel($pickup)) {
            return redirect()->route('pickups.show', $pickup->id)
                ->with('error', 'You cannot cancel this pickup at its current status.');
        }

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required|string|max:1000',
        ], [
            'cancel_reason.required' => 'Enter a reason for cancelling.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('pickups.show', $pickup->id)
                ->withErrors($validator, 'cancel')
                ->withInput();
        }

        $from = $pickup->status;

        $done = $this->transition($pickup, $from, function (Pickup $p) use ($request) {
            $p->status = Pickup::STATUS_CANCELLED;
            $p->cancelled_at = now();
            $p->cancelled_by_id = Auth::id();
            $p->cancelled_by_name = $this->userName();
            $p->cancel_reason = $request->cancel_reason;
        });

        if ($done) {
            $this->notify($pickup, Pickup::STATUS_CANCELLED, $from);
        }

        return $done
            ? redirect()->route('pickups.show', $pickup->id)->with('success', 'Pickup cancelled.')
            : redirect()->route('pickups.show', $pickup->id)->with('error', 'This pickup was updated by someone else. Please check its status.');
    }

    /**
     * Same storage pattern as order photos: save under storage/app/public,
     * then copy into public/storage because the cPanel host has no symlink.
     */
    private function storePhoto($file, int $pickupId, int $index): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = 'pickup_' . $pickupId . '_' . time() . '_' . $index . '.' . $ext;

        $file->storeAs('public/' . Pickup::PHOTO_DIR, $filename);

        $publicDir = public_path('storage/' . Pickup::PHOTO_DIR);
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        if (!copy(storage_path('app/public/' . Pickup::PHOTO_DIR . '/' . $filename), $publicDir . '/' . $filename)) {
            throw new \RuntimeException('Could not copy photo to public storage: ' . $filename);
        }

        return $filename;
    }

    private function deletePhotos(array $filenames): void
    {
        foreach ($filenames as $filename) {
            @unlink(storage_path('app/public/' . Pickup::PHOTO_DIR . '/' . $filename));
            @unlink(public_path('storage/' . Pickup::PHOTO_DIR . '/' . $filename));
        }
    }
}
