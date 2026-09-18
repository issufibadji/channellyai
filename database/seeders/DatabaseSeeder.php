<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(AcademicoPermissionSeeder::class);
        $this->call(AjudaPermissionSeeder::class);
        $this->call(AjudaSeeder::class);
        $this->call(MenuSideBarSeeder::class);
        $this->call(TestUsersSeeder::class);

        User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $testUser->assignRole('admin');
    }
}
