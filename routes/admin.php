<?php

use App\Livewire\Admin\UserList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'check2fa', 'checkPermission:view-users'])->group(function () {
    Route::get('admin/users', UserList::class)->name('admin.users.index');
});
