@extends('layouts.app')
@section('content')
    <h1>Dashboard</h1>
    <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
    @if(auth()->user()->hasRole('SuperAdmin'))
        <p>Manage the single company and invite users. SuperAdmin cannot create or list short URLs.</p>
        <p><a href="{{ route('companies.index') }}">Manage company</a> · <a href="{{ route('invitations.create') }}">Invite
                user</a></p>
    @elseif(auth()->user()->hasRole('Sales', 'Manager'))
        <p>You can generate short URLs and view URLs created in your company.</p>
        <p><a href="{{ route('urls.index') }}">Generate a short URL</a></p>
    @elseif(auth()->user()->hasRole('Admin'))
        <p>You can invite Sales or Manager into your company, but cannot generate short URLs.</p>
        <p>Under the literal visibility restriction and single-company constraint, your URL list must be empty.</p>
        <p><a href="{{ route('invitations.create') }}">Invite team member</a></p>
    @else
        <p>You can view shortened URLs created by other users, but cannot create new ones.</p>
        <p><a href="{{ route('urls.index') }}">View URLs</a></p>
    @endif
@endsection