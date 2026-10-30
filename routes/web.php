<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketPriorityController;
use App\Http\Controllers\TicketReplyController;
use App\Http\Controllers\TicketStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
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
