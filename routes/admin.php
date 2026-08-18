<?php

use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppConfigManager;
use App\Livewire\Admin\AuditManager;
use App\Livewire\Admin\MenuSideBarManager;
use App\Livewire\Admin\PermissionManager;
use App\Livewire\Admin\RoleManager;
use App\Livewire\Admin\RoleUserLinker;
use App\Livewire\Admin\UserManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'check2fa'])->group(function () {
    Route::middleware('checkPermission:view-users')
        ->get('admin/users', UserManager::class)->name('admin.users.index');

    Route::middleware('checkPermission:manage-menu')
        ->get('admin/menu', MenuSideBarManager::class)->name('admin.menu.index');

    Route::middleware('checkPermission:manage-roles')->group(function () {
        Route::get('admin/roles', RoleManager::class)->name('admin.roles.index');
        Route::get('admin/roles-user', RoleUserLinker::class)->name('admin.roles-user.index');
    });

    Route::middleware('checkPermission:manage-permissions')
        ->get('admin/permissions', PermissionManager::class)->name('admin.permissions.index');

    Route::middleware('checkPermission:manage-config')
        ->get('admin/config', AppConfigManager::class)->name('admin.config.index');

    Route::middleware('checkPermission:view-audits')
        ->get('admin/audits', AuditManager::class)->name('admin.audits.index');

    Route::middleware('checkPermission:send-notifications')
        ->get('admin/announcements', AnnouncementManager::class)->name('admin.announcements.index');
});
