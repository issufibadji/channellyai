<?php

use App\Http\Controllers\PushSubscriptionController;
use App\Livewire\Dashboard;
use App\Livewire\NotificationList;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified', 'check2fa'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('notifications', NotificationList::class)->name('notifications.index');
    Route::get('settings/profile', Profile::class)->name('settings.profile');

    Route::post('push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/atendimento.php';
