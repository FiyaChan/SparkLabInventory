<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_model_implements_must_verify_email(): void
    {
        $user = new User();
        $this->assertInstanceOf(MustVerifyEmail::class, $user);
    }

    public function test_registration_dispatches_registered_event_and_leaves_email_unverified(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post(route('register'), [
            'name' => 'Alice Explorer',
            'email' => 'alice@test.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        Event::assertDispatched(Registered::class);

        $user = User::where('email', 'alice@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verification_email_can_be_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_email_can_be_verified_via_signed_url(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard'));
    }

    public function test_resend_verification_notification_endpoint(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('verification.send'));

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $response->assertRedirect();
    }
}
