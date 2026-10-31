<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketPriorityController;
use App\Http\Controllers\TicketReplyController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->homeRoute())
        : redirect()->route('login');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/imports/template', [ImportController::class, 'template'])->name('imports.template');
    Route::get('/imports/{importBatch}/errors', [ImportController::class, 'errors'])->name('imports.errors');
    Route::get('/imports/{importBatch}', [ImportController::class, 'show'])->name('imports.show');
    Route::resource('tickets', TicketController::class)->except('destroy');

    Route::post('/tickets/{ticket}/replies', [TicketReplyController::class, 'store'])
        ->name('tickets.replies.store');
    Route::post('/tickets/{ticket}/claim', [TicketAssignmentController::class, 'claim'])
        ->name('tickets.claim');
    Route::post('/tickets/{ticket}/assign', [TicketAssignmentController::class, 'assign'])
        ->name('tickets.assign');
    Route::patch('/tickets/{ticket}/status', [TicketStatusController::class, 'update'])
        ->name('tickets.status');
    Route::patch('/tickets/{ticket}/priority', [TicketPriorityController::class, 'update'])
        ->name('tickets.priority');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])
        ->name('attachments.download');
});

require __DIR__.'/auth.php';
