<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_nothing_outside_local_environment(): void
    {
        $this->assertNotSame('local', app()->environment());

        $before = User::count();

        $this->seed(TestUsersSeeder::class);

        $this->assertSame($before, User::count());
    }
}
