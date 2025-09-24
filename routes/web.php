<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlockedDateController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PRFController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Route::get('/', function () {
//     return view('login');
// })->name('login');

Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home');

// Protected routes that require authentication
Route::middleware(['auth'])->group(function () {
    // User management routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    
    // Additional explicit user routes for form handling
    Route::post('/users/{user}/update', [UserController::class, 'update'])->name('users.update.post');
    Route::post('/users/{user}/delete', [UserController::class, 'destroy'])->name('users.delete.post');
    
    // Customer routes
    Route::resource('customers', CustomerController::class);
    
    // Product routes
    Route::resource('products', ProductController::class);
    
    // Order routes
    Route::resource('orders', OrderController::class);
    Route::post('/orders/{order}/batch', [OrderController::class, 'updateBatch'])->name('orders.batch');
    Route::post('/orders/{order}/delivery', [OrderController::class, 'updateDelivery'])->name('orders.delivery')->middleware('department.permission:mark-delivered');
    Route::get('/orders/{order}/batch/edit', [OrderController::class, 'editBatchInfo'])->name('orders.batch.edit');
    Route::post('/orders/{order}/batch/update', [OrderController::class, 'updateBatchInfo'])->name('orders.batch.update');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update.status');
    
    // Additional route with middleware for marking orders as ready
    Route::patch('/orders/{order}/mark-ready', [OrderController::class, 'markReady'])->name('orders.mark.ready')->middleware('department.permission:mark-ready');
    
    // New route for updating individual product ready status
    Route::patch('/orders/{order}/products/{product}/ready', [OrderController::class, 'updateProductReadyStatus'])->name('orders.product.ready');
    
    // Visit routes
    Route::resource('visits', VisitController::class);
    
    // Legacy routes - keeping them for backward compatibility
    Route::get('/neworder', [OrderController::class, 'newOrder'])->name('neworder')->middleware('department.permission:view-new-order');
    Route::post('/neworder', [OrderController::class, 'storeNewOrder'])->name('neworder.store')->middleware('department.permission:view-new-order');
    
    // Customer API route for AJAX
    Route::get('/api/customers/{id}', [CustomerController::class, 'getCustomerData'])->name('api.customers.data');
    
    Route::get('/orderhistory', [OrderController::class, 'history'])->name('orderhistory');
    Route::get('/orderdetails/{order}', [OrderController::class, 'orderDetails'])->name('orderdetails');
    
    // PRF routes - for displaying and printing PRF forms
    Route::get('/orders/{order}/prf', [PRFController::class, 'show'])->name('orders.prf');
    Route::get('/orders/{order}/prf/print', [PRFController::class, 'print'])->name('orders.prf.print');
    
    Route::get('/calendar', [HomeController::class, 'index'])->name('dashboard');

    Route::post('order-batch-update/{id}', [OrderController::class, 'updateBatch'])->name('orders.update.batch');
    

    
    // Profile Routes
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::post('/orders/{order}/upload-photo', [OrderController::class, 'uploadOrderPhoto'])->name('orders.upload.photo');

    Route::delete('/orders/{order}/delete-photo', [OrderController::class, 'deleteOrderPhoto'])->name('orders.delete.photo');
    
    Route::delete('/orders/{order}/delete-photo/{filename}', [OrderController::class, 'deleteSpecificOrderPhoto'])->name('orders.delete.specific.photo');

    Route::get('/orders/{order}/mark-ready-link', [OrderController::class, 'markReadyLink'])->name('orders.mark.ready.link');

    // Order delivery date/time update route
    Route::patch('/orders/{id}/delivery-datetime', [OrderController::class, 'updateDeliveryDateTime'])->name('orders.delivery.datetime.update');
    
    // Order signature route
    Route::post('/orders/{order}/signature', [OrderController::class, 'saveSignature'])->name('orders.signature');
    
    // Blocked Dates Management Routes
    Route::get('/settings/blocked-dates', [BlockedDateController::class, 'index'])->name('blocked-dates.index');
    Route::post('/settings/blocked-dates', [BlockedDateController::class, 'store'])->name('blocked-dates.store');
    Route::put('/settings/blocked-dates/{blockedDate}', [BlockedDateController::class, 'update'])->name('blocked-dates.update');
    Route::patch('/settings/blocked-dates/{blockedDate}/toggle', [BlockedDateController::class, 'toggle'])->name('blocked-dates.toggle');
    Route::delete('/settings/blocked-dates/{blockedDate}', [BlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');
    
    // API route for getting blocked dates (accessible to all authenticated users)
    Route::get('/api/blocked-dates', [BlockedDateController::class, 'api'])->name('blocked-dates.api');
    
});


