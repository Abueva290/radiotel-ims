<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checks the role-based access described in the Security Plan:
 * each employee can open only the modules of their role.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, array $extra = []): User
    {
        return User::factory()->create(['role' => $role] + $extra);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/sales')->assertRedirect(route('login'));
    }

    public function test_every_role_can_open_the_dashboard(): void
    {
        foreach (array_keys(User::ROLES) as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/dashboard')
                ->assertOk();
        }
    }

    public function test_operational_manager_can_open_admin_only_modules(): void
    {
        $admin = $this->userWithRole('admin');

        foreach (['/users', '/audit-trail', '/reports', '/backups'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_other_roles_cannot_open_admin_only_modules(): void
    {
        foreach (['secretary', 'technical_head', 'technician', 'staff'] as $role) {
            $user = $this->userWithRole($role);

            foreach (['/users', '/audit-trail', '/reports', '/backups'] as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }

            $this->actingAs($user)->post('/backups')->assertForbidden();
        }
    }

    public function test_secretary_can_open_sales_modules_only(): void
    {
        $secretary = $this->userWithRole('secretary');

        foreach (['/sales', '/customers', '/receivables', '/payables'] as $url) {
            $this->actingAs($secretary)->get($url)->assertOk();
        }

        foreach (['/inventory', '/repairs'] as $url) {
            $this->actingAs($secretary)->get($url)->assertForbidden();
        }
    }

    public function test_staff_can_open_inventory_only(): void
    {
        $staff = $this->userWithRole('staff');

        $this->actingAs($staff)->get('/inventory')->assertOk();

        foreach (['/sales', '/repairs', '/payables'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_technical_head_and_technician_can_open_repairs_only(): void
    {
        foreach (['technical_head', 'technician'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get('/repairs')->assertOk();

            foreach (['/sales', '/inventory', '/payables'] as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    public function test_disabled_accounts_cannot_log_in(): void
    {
        $user = $this->userWithRole('secretary', ['status' => 'disabled']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_user_must_change_password_before_using_the_system(): void
    {
        $user = $this->userWithRole('secretary', ['must_change_password' => true]);

        $this->actingAs($user)->get('/sales')->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('password.change'))->assertOk();
    }

    public function test_registration_password_reset_and_profile_pages_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();

        $this->actingAs($this->userWithRole('secretary'))
            ->delete('/profile')
            ->assertNotFound();
    }
}