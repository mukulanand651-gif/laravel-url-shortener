<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invitation;
use App\Models\ShortUrl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        return Company::firstOrCreate(['singleton_key' => 1], ['name' => 'Test Company']);
    }

    private function user(string $role, ?Company $company = null): User
    {
        return User::create([
            'name' => $role,
            'email' => uniqid('test_', true) . '@example.test',
            'password' => 'a-long-test-password',
            'role' => $role,
            'company_id' => $company?->id,
        ]);
    }

    private function url(User $creator): ShortUrl
    {
        return ShortUrl::create([
            'company_id' => $creator->company_id,
            'user_id' => $creator->id,
            'code' => bin2hex(random_bytes(5)),
            'original_url' => 'https://example.com/destination',
        ]);
    }

    public function test_only_sales_and_manager_can_create_short_urls(): void
    {
        $company = $this->company();
        foreach (['SuperAdmin', 'Admin', 'Member'] as $role) {
            $user = $this->user($role, $role === 'SuperAdmin' ? null : $company);
            $this->actingAs($user)->post('/urls', ['original_url' => 'https://example.com'])->assertForbidden();
        }
        foreach (['Sales', 'Manager'] as $role) {
            $this->actingAs($this->user($role, $company))
                ->post('/urls', ['original_url' => 'https://example.com'])->assertRedirect('/urls');
        }
        $this->assertDatabaseCount('short_urls', 2);
    }

    public function test_superadmin_cannot_list_short_urls(): void
    {
        $this->actingAs($this->user('SuperAdmin'))->get('/urls')->assertForbidden();
    }

    public function test_single_company_enforced(): void
    {
        $this->company();
        $this->actingAs($this->user('SuperAdmin'))
            ->post('/companies', ['name' => 'Second'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_admin_url_list_is_empty_under_literal_rule(): void
    {
        $company = $this->company();
        $url = $this->url($this->user('Sales', $company));
        $this->actingAs($this->user('Admin', $company))
            ->get('/urls')->assertOk()->assertDontSee($url->code);
    }

    public function test_member_sees_other_peoples_urls_but_not_their_own(): void
    {
        $company = $this->company();
        $member = $this->user('Member', $company);
        $own = $this->url($member);
        $other = $this->url($this->user('Sales', $company));
        $this->actingAs($member)->get('/urls')->assertOk()
            ->assertDontSee($own->code)->assertSee($other->code);
    }

    public function test_sales_can_list_own_company_urls(): void
    {
        $company = $this->company();
        $url = $this->url($this->user('Manager', $company));
        $this->actingAs($this->user('Sales', $company))
            ->get('/urls')->assertOk()->assertSee($url->code);
    }

    public function test_short_url_is_private_but_redirects_for_authenticated_company_user(): void
    {
        $company = $this->company();
        $creator = $this->user('Sales', $company);
        $url = $this->url($creator);
        $this->get('/s/' . $url->code)->assertRedirect('/login');
        $this->actingAs($this->user('SuperAdmin'))->get('/s/' . $url->code)->assertForbidden();
        $this->actingAs($creator)->get('/s/' . $url->code)
            ->assertRedirect('https://example.com/destination');
        $this->assertSame(1, $url->fresh()->visits);
    }

    public function test_only_authorized_roles_can_invite(): void
    {
        $company = $this->company();
        $admin = $this->user('Admin', $company);
        foreach (['Admin', 'Member'] as $role) {
            $this->actingAs($admin)->post('/invite', [
                'email' => uniqid() . '@example.test',
                'role' => $role,
            ])->assertForbidden();
        }
        foreach (['Sales', 'Manager'] as $role) {
            $this->actingAs($admin)->post('/invite', [
                'email' => uniqid() . '@example.test',
                'role' => $role,
            ])->assertRedirect();
        }
        $this->actingAs($this->user('Member', $company))
            ->post('/invite', ['email' => 'foo@example.test', 'role' => 'Sales'])
            ->assertForbidden();
    }

    public function test_superadmin_can_invite_admin_to_existing_company_only(): void
    {
        $company = $this->company();
        $this->actingAs($this->user('SuperAdmin'))->post('/invite', [
            'company_id' => $company->id,
            'email' => 'admin@example.test',
            'role' => 'Admin',
        ])->assertRedirect();
        $this->assertDatabaseHas('invitations', [
            'email' => 'admin@example.test',
            'role' => 'Admin',
            'company_id' => $company->id,
        ]);
        $this->post('/invite', [
            'email' => 'another@example.test',
            'role' => 'Admin',
        ])->assertStatus(422);
    }

    public function test_invitation_is_single_use(): void
    {
        $company = $this->company();
        $this->actingAs($this->user('SuperAdmin'))->post('/invite', [
            'company_id' => $company->id,
            'email' => 'invitee@example.test',
            'role' => 'Sales',
        ])->assertRedirect();
        preg_match('~/invitations/([A-Za-z0-9]+)~', (string) session('status'), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $token = $matches[1];
        auth()->logout();
        $this->post('/invitations/' . $token, [
            'name' => 'Invitee',
            'password' => 'A-long-password-123',
            'password_confirmation' => 'A-long-password-123',
        ])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'invitee@example.test', 'role' => 'Sales']);
        auth()->logout();
        $this->get('/invitations/' . $token)->assertNotFound();
    }

    public function test_expired_invitation_fails(): void
    {
        $company = $this->company();
        $admin = $this->user('SuperAdmin');
        $token = 'expirytest';
        Invitation::create([
            'company_id' => $company->id,
            'invited_by' => $admin->id,
            'email' => 'expired@example.test',
            'role' => 'Manager',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
        ]);
        $this->get('/invitations/' . $token)->assertNotFound();
    }

    public function test_login_and_logout(): void
    {
        $user = $this->user('SuperAdmin');
        $this->post('/login', ['email' => $user->email, 'password' => 'a-long-test-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
