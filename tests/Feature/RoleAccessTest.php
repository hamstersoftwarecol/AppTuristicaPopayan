<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_ignores_supplied_privileged_roles(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Viajera',
            'email' => 'viajera@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::SuperAdmin->value,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'viajera@example.com')->firstOrFail();
        $this->assertSame([Role::Customer->value], $user->getRoleNames()->all());
        $this->assertFalse($user->can('settings.manage'));
    }

    public function test_each_role_can_only_open_its_assigned_panel(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create([
                'two_factor_secret' => encrypt('secret'),
                'two_factor_confirmed_at' => now(),
            ]);
            $user->assignRole($role->value);
            $this->actingAs($user)->get(route('dashboard'))
                ->assertRedirect(route('dashboard.role', $role->value));

            foreach (Role::cases() as $target) {
                $response = $this->get(route('dashboard.role', $target->value));
                if ($target === $role) {
                    $response->assertOk()->assertInertia(fn (Assert $page) => $page
                        ->component('Dashboard')->where('role', $role->value));
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    public function test_privileged_accounts_require_confirmed_two_factor_setup(): void
    {
        foreach ([Role::Admin, Role::SuperAdmin, Role::Finance] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role->value);
            $this->actingAs($user)->get(route('dashboard'))
                ->assertRedirect(route('security.edit'));
            $user->forceFill(['two_factor_secret' => encrypt('secret')])->save();
            $this->get(route('dashboard.role', $role->value))
                ->assertRedirect(route('security.edit'));
        }
    }

    public function test_unverified_and_unassigned_users_cannot_enter_a_panel(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole(Role::Customer->value);
        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([]);
        $this->get(route('dashboard'))->assertForbidden();
    }

    public function test_role_seeding_is_repeatable_and_permissions_are_restricted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->assertDatabaseCount('roles', 9);
        $customer = User::factory()->create();
        $customer->assignRole(Role::Customer->value);
        $this->assertTrue($customer->can('reservations.view-own'));
        $this->assertFalse($customer->can('operations.manage'));
        $this->assertFalse($customer->can('finance.manage'));
    }
}
