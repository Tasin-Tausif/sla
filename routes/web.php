<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SlaPolicyController;
use App\Http\Controllers\Admin\UserController;
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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', AdminController::class)->name('index');

    Route::middleware('can:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->except('show');
    });
    Route::middleware('can:sla.manage')->group(function () {
        Route::get('sla/policies', [SlaPolicyController::class, 'index'])->name('sla.policies.index');
        Route::put('sla/policies', [SlaPolicyController::class, 'update'])->name('sla.policies.update');
        Route::get('sla/business-hours', [CalendarController::class, 'businessHours'])->name('sla.business-hours.index');
        Route::put('sla/business-hours', [CalendarController::class, 'updateBusinessHours'])->name('sla.business-hours.update');
        Route::get('holidays', [CalendarController::class, 'holidays'])->name('holidays.index');
        Route::get('holidays/create', [CalendarController::class, 'createHoliday'])->name('holidays.create');
        Route::post('holidays', [CalendarController::class, 'storeHoliday'])->name('holidays.store');
        Route::get('holidays/{holiday}/edit', [CalendarController::class, 'editHoliday'])->name('holidays.edit');
        Route::put('holidays/{holiday}', [CalendarController::class, 'updateHoliday'])->name('holidays.update');
        Route::delete('holidays/{holiday}', [CalendarController::class, 'destroyHoliday'])->name('holidays.destroy');
    });
    Route::middleware('can:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::post('users/{user}/make-agent', [UserController::class, 'makeAgent'])->name('users.make-agent');
        Route::post('users/{user}/remove-agent', [UserController::class, 'removeAgent'])->name('users.remove-agent');
    });
    Route::middleware('can:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except('show');
        Route::post('roles/{role}/reset', [RoleController::class, 'reset'])->name('roles.reset');
    });
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
    Route::get('history', [AuditController::class, 'index'])->middleware('can:audit.view')->name('audit.index');
});

require __DIR__.'/auth.php';
