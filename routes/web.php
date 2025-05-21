<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PRFController;

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
    
    // Visit routes
    Route::resource('visits', VisitController::class);
    
    // Legacy routes - keeping them for backward compatibility
    Route::get('/neworder', [OrderController::class, 'newOrder'])->name('neworder');
    Route::post('/neworder', [OrderController::class, 'storeNewOrder'])->name('neworder.store');
    
    // Customer API route for AJAX
    Route::get('/api/customers/{id}', [CustomerController::class, 'getCustomerData'])->name('api.customers.data');
    
    Route::get('/orderhistory', [OrderController::class, 'history'])->name('orderhistory');
    Route::get('/orderdetails/{order}', [OrderController::class, 'orderDetails'])->name('orderdetails');
    
    // PRF routes - for displaying and printing PRF forms
    Route::get('/orders/{order}/prf', [PRFController::class, 'show'])->name('orders.prf');
    Route::get('/orders/{order}/prf/print', [PRFController::class, 'print'])->name('orders.prf.print');
    
    Route::get('/calendar', [HomeController::class, 'index'])->name('dashboard');

    Route::post('order-batch-update/{id}', [OrderController::class, 'updateBatch'])->name('orders.update.batch');
    Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.update.status');
    
    // Email testing route - admin only
    Route::get('/test-email', [OrderController::class, 'testEmailNotification'])->middleware('role:admin,superadmin');
});


