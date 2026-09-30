<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
        $this->user->assignRole('customer');
    }

    public function test_customer_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));

        $response->assertStatus(200)
            ->assertSee('Original Name')
            ->assertSee('original@example.com');
    }

    public function test_customer_can_update_profile_with_phone_and_address(): void
    {
        $response = $this->actingAs($this->user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+60182428922',
            'address' => 'No 12, Jalan Sains, Putrajaya, Malaysia',
            'tin' => 'IG12345678090',
            'id_type' => 'NRIC',
            'id_number' => '920512-10-5544',
            'sst_number' => 'W10-1808-31000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Profile updated successfully.');

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('updated@example.com', $this->user->email);
        $this->assertEquals('+60182428922', $this->user->phone);
        $this->assertEquals('No 12, Jalan Sains, Putrajaya, Malaysia', $this->user->address);
        $this->assertEquals('IG12345678090', $this->user->tin);
        $this->assertEquals('920512-10-5544', $this->user->id_number);
    }
}
