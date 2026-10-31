<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreateAdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_sends_verification_and_records_the_new_account(): void
    {
        Notification::fake();
        $this->artisan('tourism:create-admin')
            ->expectsQuestion('Nombre', 'Administración')
            ->expectsQuestion('Correo electrónico', 'admin@example.test')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'Strong!Password123')
            ->expectsQuestion('Confirma la contraseña', 'Strong!Password123')
            ->assertSuccessful();
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('superadministrador'));
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertTrue(Hash::check('Strong!Password123', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.admin_created', 'subject_id' => $user->id]);
    }
}
