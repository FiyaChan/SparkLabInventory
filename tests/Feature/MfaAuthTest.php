<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MfaAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_mfa_setup_screen_renders_with_svg_qr_code(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => false,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('mfa.setup'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.mfa-setup');
        $response->assertSee('<svg', false);
        $this->assertTrue(session()->has('mfa_setup_secret'));
    }

    public function test_user_can_confirm_mfa_setup_with_valid_totp_code(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => false,
        ]);
        $user->assignRole('customer');

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)
            ->withSession(['mfa_setup_secret' => $secret])
            ->post(route('mfa.confirm'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertTrue($user->mfa_enabled);
        $this->assertNotNull($user->mfa_secret);
        $this->assertFalse(session()->has('mfa_setup_secret'));
    }

    public function test_login_intercepts_user_with_mfa_enabled_and_redirects_to_challenge(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'email' => 'mfa-user@test.com',
            'password' => 'Password123!',
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->post(route('login'), [
            'email' => 'mfa-user@test.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('mfa.challenge'));
        $this->assertGuest();
        $this->assertEquals($user->id, session('mfa_pending_user_id'));
    }

    public function test_user_can_complete_mfa_challenge_with_valid_code(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $validCode = $google2fa->getCurrentOtp($secret);

        $user = User::factory()->create([
            'email' => 'mfa-user@test.com',
            'password' => 'Password123!',
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->withSession(['mfa_pending_user_id' => $user->id])
            ->post(route('mfa.verify'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(session('mfa_authenticated'));
    }

    public function test_invalid_mfa_code_fails_verification(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'email' => 'mfa-user@test.com',
            'password' => 'Password123!',
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->withSession(['mfa_pending_user_id' => $user->id])
            ->from(route('mfa.challenge'))
            ->post(route('mfa.verify'), [
                'code' => '000000',
            ]);

        $response->assertRedirect(route('mfa.challenge'));
        $response->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_user_can_disable_mfa_with_password(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'mfa_secret' => 'SECRETKEY1234567',
            'mfa_enabled' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)
            ->post(route('mfa.disable'), [
                'current_password' => 'Password123!',
            ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->assertFalse($user->mfa_enabled);
        $this->assertNull($user->mfa_secret);
    }
}
