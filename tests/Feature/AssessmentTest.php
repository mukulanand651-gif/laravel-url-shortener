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
    private function company(string $name = 'Company A'): Company
    {
        return Company::create(['name' => $name]);
    }
    private function user(string $role, ?Company $company = null): User
    {
        return User::create([
            'name' => $role,
            'email' => uniqid('user_', true) . '@example.test',
            'password' => 'a-long-test-password',
            'role' => $role,
            'company_id' => $company?->id
        ]);
    }
    private function url(User $owner): ShortUrl
    {
        return ShortUrl::create([
            'company_id' => $owner->company_id,
            'user_id' => $owner->id,
            'code' => bin2hex(random_bytes(5)),
            'original_url' => 'https://example.com/target'
        ]);
    }
    public function test_sales_and_manager_are_invitable_into_admin_company(): void
    {
        $company = $this->company();
        $admin = $this->user('Admin', $company);
        foreach (['Sales', 'Manager'] as $role) {
            $email = strtolower($role) . '@example.test';
            $this->actingAs($admin)->post('/invite', [
                'email' => $email,
                'role' => $role,
            ])->assertRedirect();
            $this->assertDatabaseHas('invitations', [
                'email' => $email,
                'role' => $role,
                'company_id' => $company->id,
            ]);
        }
    }

    public function test_sales_and_manager_read_permissions_without_creation_permission(): void
    {
        $a = $this->company();
        $b = $this->company('Other company');
        $sales = $this->user('Sales', $a);
        $manager = $this->user('Manager', $a);
        $own = $this->url($sales);
        $sameCompany = $this->url($this->user('Member', $a));
        $otherCompany = $this->url($this->user('Member', $b));

        $this->actingAs($sales)->get('/urls')->assertOk()
            ->assertSee($own->code)
            ->assertDontSee($sameCompany->code)
            ->assertDontSee($otherCompany->code);
        $this->actingAs($manager)->get('/urls')->assertOk()
            ->assertSee($own->code)
            ->assertSee($sameCompany->code)
            ->assertDontSee($otherCompany->code);

        foreach ([$sales, $manager] as $user) {
            $this->actingAs($user)->post('/urls', [
                'original_url' => 'https://example.com/new',
            ])->assertForbidden();
        }
    }

    public function test_multiple_companies_and_multiple_users_are_supported(): void
    {
        $a = $this->company();
        $b = $this->company('Company B');
        $this->user('Admin', $a);
        $this->user('Member', $a);
        $this->user('Admin', $b);
        $this->assertDatabaseCount('companies', 2);
        $this->assertSame(2, $a->users()->count());
        $this->assertSame(1, $b->users()->count());
    }
    public function test_admin_and_member_can_create_short_urls_but_superadmin_cannot(): void
    {
        $a = $this->company();
        foreach (['Admin', 'Member'] as $role) {
            $this->actingAs($this->user($role, $a))->post('/urls', ['original_url' => 'https://example.com'])->assertRedirect('/urls');
        }
        $this->actingAs($this->user('SuperAdmin'))->post('/urls', ['original_url' => 'https://example.com'])->assertForbidden();
        $this->assertDatabaseCount('short_urls', 2);
    }
    public function test_superadmin_can_list_all_companies_urls(): void
    {
        $a = $this->company();
        $b = $this->company('Company B');
        $u = $this->url($this->user('Member', $a));
        $v = $this->url($this->user('Member', $b));
        $this->actingAs($this->user('SuperAdmin'))->get('/urls')->assertOk()->assertSee($u->code)->assertSee($v->code);
    }
    public function test_admin_sees_everyone_in_own_company_but_not_other_companies(): void
    {
        $a = $this->company();
        $b = $this->company('Company B');
        $own = $this->user('Admin', $a);
        $within = $this->url($this->user('Member', $a));
        $other = $this->url($this->user('Member', $b));
        $created = $this->url($own);
        $this->actingAs($own)->get('/urls')->assertOk()->assertSee($within->code)->assertSee($created->code)->assertDontSee($other->code);
    }
    public function test_member_sees_only_own_urls(): void
    {
        $a = $this->company();
        $member = $this->user('Member', $a);
        $own = $this->url($member);
        $other = $this->url($this->user('Admin', $a));
        $this->actingAs($member)->get('/urls')->assertOk()->assertSee($own->code)->assertDontSee($other->code);
    }
    public function test_public_redirect_works_when_logged_out(): void
    {
        $a = $this->company();
        $url = $this->url($this->user('Member', $a));
        $this->get('/s/' . $url->code)->assertRedirect('https://example.com/target');
        $this->assertSame(1, $url->fresh()->visits);
        $this->get('/s/nonexistentcode')->assertNotFound();
    }
    public function test_superadmin_can_invite_first_admin_into_new_company(): void
    {
        $root = $this->user('SuperAdmin');
        $this->actingAs($root)->post('/invite', ['email' => 'first@example.test', 'role' => 'Admin', 'new_company_name' => 'Fresh Co'])->assertRedirect();
        $this->assertDatabaseHas('companies', ['name' => 'Fresh Co']);
        $company = Company::where('name', 'Fresh Co')->firstOrFail();
        $this->assertDatabaseHas('invitations', ['company_id' => $company->id, 'email' => 'first@example.test', 'role' => 'Admin']);
        $this->post('/invite', ['email' => 'bad@example.test', 'role' => 'Member', 'new_company_name' => 'Unwanted Co'])->assertSessionHasErrors('new_company_name');
        $this->assertDatabaseCount('companies', 1);
    }
    public function test_admin_can_invite_admin_or_member_only_to_own_company(): void
    {
        $a = $this->company();
        $b = $this->company('Company B');
        $admin = $this->user('Admin', $a);
        foreach (['Admin', 'Member'] as $role) {
            $this->actingAs($admin)->post('/invite', ['email' => uniqid() . '@example.test', 'role' => $role])->assertRedirect();
        }
        $this->assertDatabaseCount('invitations', 2);
        $this->post('/invite', ['email' => 'escape@example.test', 'role' => 'Member', 'company_id' => $b->id])->assertForbidden();
        $this->actingAs($this->user('Member', $a))->post('/invite', ['email' => 'no@example.test', 'role' => 'Member'])->assertForbidden();
    }
    public function test_invitation_single_use_and_expiry(): void
    {
        $company = $this->company();
        $root = $this->user('SuperAdmin');
        $this->actingAs($root)->post('/invite', ['company_id' => $company->id, 'role' => 'Admin', 'email' => 'invited@example.test'])->assertRedirect();
        preg_match('~/invitations/([A-Za-z0-9]+)~', (string) session('status'), $m);
        $this->assertNotEmpty($m[1] ?? null);
        $token = $m[1];
        auth()->logout();
        $this->post('/invitations/' . $token, ['name' => 'Invited', 'password' => 'A-long-password-123', 'password_confirmation' => 'A-long-password-123'])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'invited@example.test', 'role' => 'Admin']);
        auth()->logout();
        $this->get('/invitations/' . $token)->assertNotFound();
        Invitation::create(['company_id' => $company->id, 'invited_by' => $root->id, 'email' => 'expired@example.test', 'role' => 'Member', 'token_hash' => hash('sha256', 'expired-token'), 'expires_at' => now()->subMinute()]);
        $this->get('/invitations/expired-token')->assertNotFound();
    }
    public function test_login_logout(): void
    {
        $u = $this->user('SuperAdmin');
        $this->post('/login', ['email' => $u->email, 'password' => 'a-long-test-password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
