@extends('layouts.app')
@section('content')
    <h1>Dashboard</h1>
    <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
    @if(auth()->user()->hasRole('SuperAdmin'))
        <p>Manage companies, invite their first Admin, and review short URLs across all companies. You cannot create short URLs.
        </p>
        <p>
            <a href="{{ route('companies.index') }}">Companies</a> .
            <a href="{{ route('invitations.create') }}">Invite first Admin</a> ·
            <a href="{{ route('urls.index') }}">All short URLs</a>
        </p>
    @elseif(auth()->user()->hasRole('Admin'))
        <p>Invite Admins, Members, Sales or Managers to your company, generate short URLs and view all URLs within your company.
        </p>
        <p><a href="{{ route('invitations.create') }}">Invite teammate</a>
            <a href="{{ route('urls.index') }}">Create URL</a>
        </p>
    @elseif(auth()->user()->hasRole('Member'))
        <p>Generate short URLs and view URLs created by you.</p>
        <p><a href="{{ route('urls.index') }}">Create URL</a></p>
    @elseif(auth()->user()->hasRole('Sales'))
        <p>You can view links created by you, but cannot generate new short URLs.</p>
        <p><a href="{{ route('urls.index') }}">My URLs</a></p>
    @elseif(auth()->user()->hasRole('Manager'))
        <p>You can view your company's links, but cannot generate new short URLs.</p>
        <p><a href="{{ route('urls.index') }}">Company URLs</a></p>
    @endif
@endsection