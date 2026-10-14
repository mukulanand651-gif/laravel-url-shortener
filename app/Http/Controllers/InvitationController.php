<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->user()->hasRole('SuperAdmin', 'Admin'), 403);
        return view('invitations.create', [
            'companies' => $request->user()->hasRole('SuperAdmin') ? Company::all() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor->hasRole('SuperAdmin', 'Admin'), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['Admin', 'Member', 'Sales', 'Manager'])],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        if ($actor->hasRole('Admin')) {
            abort_unless(in_array($data['role'], ['Sales', 'Manager'], true), 403);
            abort_unless($actor->company_id, 403);
            abort_if(isset($data['company_id']) && (int) $data['company_id'] !== (int) $actor->company_id, 403);
            $companyId = $actor->company_id;
        } else {
            // A new-company invitation is not supported; the company must already exist.
            $companyId = $data['company_id'] ?? null;
            abort_unless($companyId, 422, 'Select the existing company first.');
        }

        $token = Str::random(64);
        Invitation::create([
            'company_id' => $companyId,
            'invited_by' => $actor->id,
            'email' => $data['email'],
            'role' => $data['role'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(48),
        ]);

        return back()->with('status', 'Invitation created. Share this one-time link securely: '.route('invitations.accept', $token));
    }

    private function validInvitation(string $token): Invitation
    {
        $invitation = Invitation::where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($invitation->accepted_at || $invitation->expires_at->isPast(), 404);
        return $invitation;
    }

    public function accept(string $token)
    {
        $invitation = $this->validInvitation($token);
        return view('invitations.accept', compact('token', 'invitation'));
    }

    public function register(Request $request, string $token)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($token, $data) {
            $invitation = Invitation::where('token_hash', hash('sha256', $token))
                ->lockForUpdate()->firstOrFail();
            abort_if($invitation->accepted_at || $invitation->expires_at->isPast(), 404);
            abort_if(User::where('email', $invitation->email)->exists(), 409);
            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'company_id' => $invitation->company_id,
                'role' => $invitation->role,
            ]);
            $invitation->update(['accepted_at' => now()]);
            return $user;
        });

        auth()->login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }
}
