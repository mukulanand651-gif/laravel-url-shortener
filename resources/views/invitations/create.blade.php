@extends('layouts.app')
@section('content')
    <h1>Invite a user</h1>
    <p>Invitations expire in 48 hours and can be used once. The link is displayed after submission; this demo does not send
        email.</p>
    <form method="post" action="{{ route('invitations.store') }}">
        @csrf
        <label>Email <input type="email" name="email" required value="{{ old('email') }}"></label>
        @if(auth()->user()->hasRole('SuperAdmin'))
            <label>Existing company (leave unselected when creating a new one)
                <select name="company_id">
                    <option value="">Choose existing company</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select></label>
            <label>OR new company name (for the first Admin invitation only)
                <input name="new_company_name" maxlength="150" value="{{ old('new_company_name') }}"
                    placeholder="New company name"></label>
        @endif
        <label>Role <select name="role">
                <option value="Admin">Admin</option>
                <option value="Member">Member</option>
            </select></label>
        <button>Create invitation link</button>
    </form>
@endsection