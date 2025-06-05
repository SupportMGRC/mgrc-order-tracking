<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    /**
     * Send new order notification emails to users who have opted in
     *
     * @param Order $order
     * @return void
     */
    public function sendNewOrderNotification(Order $order)
    {
        try {
            // Get all users who want to receive new order emails
            $users = User::where('receive_new_order_emails', true)
                        ->whereNotNull('email')
                        ->get();

            if ($users->isEmpty()) {
                Log::info('No users found with receive_new_order_emails enabled');
                return;
            }

            // Send email to each user
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new NewOrderNotification($order));
                    Log::info("New order notification sent to {$user->email} for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send new order notification to {$user->email}: " . $e->getMessage());
                }
            }

            Log::info("New order notification process completed for order #{$order->id}");
            
        } catch (\Exception $e) {
            Log::error("Error in sendNewOrderNotification: " . $e->getMessage());
        }
    }

    /**
     * Send delivery date/time update notification emails to users who have opted in
     *
     * @param Order $order
     * @param string $originalDateTime
     * @param string $newDateTime
     * @return void
     */
    public function sendDeliveryUpdateNotification(Order $order, $originalDateTime = null, $newDateTime = null)
    {
        try {
            // Get all users who want to receive new order emails (same users for updates)
            $users = User::where('receive_new_order_emails', true)
                        ->whereNotNull('email')
                        ->get();

            if ($users->isEmpty()) {
                Log::info('No users found with receive_new_order_emails enabled for delivery update');
                return;
            }

            // Prepare update data for the email
            $updateData = [
                [
                    'name' => 'Delivery Schedule',
                    'quantity' => 1,
                    'is_new' => false,
                    'field_changes' => [
                        'delivery_date_time' => [
                            'from' => $originalDateTime ? $originalDateTime->format('F j, Y g:i A') : 'Not set',
                            'to' => $newDateTime ? $newDateTime->format('F j, Y g:i A') : 'Not set'
                        ]
                    ]
                ]
            ];

            // Send email to each user
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new \App\Mail\ProductUpdateNotification($order, $updateData));
                    Log::info("Delivery update notification sent to {$user->email} for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send delivery update notification to {$user->email}: " . $e->getMessage());
                }
            }

            Log::info("Delivery update notification process completed for order #{$order->id}");
            
        } catch (\Exception $e) {
            Log::error("Error in sendDeliveryUpdateNotification: " . $e->getMessage());
        }
    }

    /**
     * Send order cancellation notification emails to users who have opted in
     *
     * @param Order $order
     * @return void
     */
    public function sendOrderCanceledNotification(Order $order)
    {
        try {
            // Get all users who want to receive new order emails (same users for cancellation)
            $users = User::where('receive_new_order_emails', true)
                        ->whereNotNull('email')
                        ->get();

            if ($users->isEmpty()) {
                Log::info('No users found with receive_new_order_emails enabled for order cancellation');
                return;
            }

            // Send email to each user
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new \App\Mail\OrderCanceledNotification($order));
                    Log::info("Order cancellation notification sent to {$user->email} for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send order cancellation notification to {$user->email}: " . $e->getMessage());
                }
            }

            Log::info("Order cancellation notification process completed for order #{$order->id}");
            
        } catch (\Exception $e) {
            Log::error("Error in sendOrderCanceledNotification: " . $e->getMessage());
        }
    }

    /**
     * Send photo upload notification email to the person who placed the order
     *
     * @param Order $order
     * @return void
     */
    public function sendPhotoUploadNotification(Order $order)
    {
        try {
            // Find the user who placed the order
            $orderPlacer = null;
            
            // Try to find user by order_placed_by field (username)
            if ($order->order_placed_by) {
                $orderPlacer = User::where('username', $order->order_placed_by)
                                  ->whereNotNull('email')
                                  ->first();
            }
            
            // If not found by username, try to find by the user_id (who created the order)
            if (!$orderPlacer && $order->user_id) {
                $orderPlacer = User::where('id', $order->user_id)
                                  ->whereNotNull('email')
                                  ->first();
            }

            if (!$orderPlacer) {
                Log::info("No valid email found for order placer for order #{$order->id}. order_placed_by: {$order->order_placed_by}, user_id: {$order->user_id}");
                return;
            }

            // Generate mark ready URL
            $markReadyUrl = route('orders.mark.ready.link', $order->id);

            try {
                Mail::to($orderPlacer->email)->send(new \App\Mail\OrderPhotoUploadedNotification($order, $markReadyUrl));
                Log::info("Photo upload notification sent to {$orderPlacer->email} (order placer) for order #{$order->id}");
            } catch (\Exception $e) {
                Log::error("Failed to send photo upload notification to {$orderPlacer->email}: " . $e->getMessage());
            }

            Log::info("Photo upload notification process completed for order #{$order->id}");
            
        } catch (\Exception $e) {
            Log::error("Error in sendPhotoUploadNotification: " . $e->getMessage());
        }
    }

    /**
     * Send order ready notification emails to users who have opted in
     *
     * @param Order $order
     * @return void
     */
    public function sendOrderReadyNotification(Order $order)
    {
        try {
            // Get all users who want to receive order ready emails
            $users = User::where('receive_order_ready_emails', true)
                        ->whereNotNull('email')
                        ->get();

            if ($users->isEmpty()) {
                Log::info('No users found with receive_order_ready_emails enabled');
                return;
            }

            // Send email to each user
            foreach ($users as $user) {
                try {
                    Mail::to($user->email)->send(new \App\Mail\OrderReadyNotification($order));
                    Log::info("Order ready notification sent to {$user->email} for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send order ready notification to {$user->email}: " . $e->getMessage());
                }
            }

            Log::info("Order ready notification process completed for order #{$order->id}");
            
        } catch (\Exception $e) {
            Log::error("Error in sendOrderReadyNotification: " . $e->getMessage());
        }
    }
} 