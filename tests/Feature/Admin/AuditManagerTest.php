<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AuditManager;
use App\Models\AppConfig;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class AuditManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The package disables auditing for console-run processes by default
        // (avoids noise from seeders/artisan commands) — tests run via `artisan
        // test`, which counts as console, so we opt back in just for these tests.
        config(['audit.console' => true]);

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creating_a_model_records_a_created_audit(): void
    {
        $config = AppConfig::create(['key' => 'app_name', 'value' => 'Channelly']);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => AppConfig::class,
            'auditable_id' => $config->id,
            'event' => 'created',
        ]);
    }

    public function test_updating_a_model_records_an_updated_audit_with_old_and_new_values(): void
    {
        $config = AppConfig::create(['key' => 'app_name', 'value' => 'Old Name']);

        $config->update(['value' => 'New Name']);

        $audit = Audit::where('auditable_type', AppConfig::class)
            ->where('auditable_id', $config->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Old Name', $audit->old_values['value']);
        $this->assertSame('New Name', $audit->new_values['value']);
    }

    public function test_deleting_a_model_records_a_deleted_audit(): void
    {
        $config = AppConfig::create(['key' => 'app_name', 'value' => 'Channelly']);
        $configId = $config->id;

        $config->delete();

        $this->assertDatabaseHas('audits', [
            'auditable_type' => AppConfig::class,
            'auditable_id' => $configId,
            'event' => 'deleted',
        ]);
    }

    public function test_user_password_is_not_recorded_in_the_audit_trail(): void
    {
        $user = User::factory()->create();

        $audit = Audit::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_user_without_view_audits_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.audits.index'))->assertForbidden();
    }

    public function test_admin_can_view_and_delete_an_audit(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $config = AppConfig::create(['key' => 'app_name', 'value' => 'Channelly']);
        $audit = Audit::where('auditable_id', $config->id)->first();

        Livewire::actingAs($admin)
            ->test(AuditManager::class)
            ->call('delete', $audit->id);

        $this->assertDatabaseMissing('audits', ['id' => $audit->id]);
    }
}
