<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_failed_login_logs_activity_without_foreign_key_violation(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@inventory-system.test',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'login.failed',
            'user_id' => null,
        ]);
    }

    public function test_record_handles_non_existent_user_id_gracefully(): void
    {
        $log = ActivityLog::record('test.action', ['user_id' => 999999, 'info' => 'test']);

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertEquals('test.action', $log->action);
    }

    public function test_record_associates_authenticated_user_properly(): void
    {
        $user = User::first();
        $this->actingAs($user);

        $log = ActivityLog::record('user.action', ['test' => true]);

        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);
    }
}
