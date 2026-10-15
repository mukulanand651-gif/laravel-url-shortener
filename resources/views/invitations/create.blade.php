@extends('layouts.app')
@section('content')
    <h1>Invite a user</h1>
    <p>An invitation expires in 48 hours and can be accepted once.</p>
    <form method="post" action="{{ route('invitations.store') }}">
        @csrf
        <label>Email<input name="email" type="email" required value="{{ old('email') }}"></label>
        @if(auth()->user()->hasRole('SuperAdmin'))<label>Existing company<select name="company_id" required>
            <option value="">Choose</option>@foreach($companies as $company)
            <option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach
        </select></label>@endif
        <label>Role<select name="role">@if(auth()->user()->hasRole('SuperAdmin'))
            <option>Admin</option>
        <option>Member</option>
        @endif<option>Sales</option>
                <option>Manager</option>
            </select></label><button>Create invitation link</button>
    </form>
@endsection