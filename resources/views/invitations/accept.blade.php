@extends('layouts.app') 
@section('content')
    <h1>Accept invitation</h1>
    <p>You're joining as {{ $invitation->role }} ({{ $invitation->email }}).</p>
    <form method="post" action="{{ route('invitations.register', $token) }}">
        @csrf
        <label>Name<input name="name" required></label>
        <label>Password (12+ characters)<input type="password" name="password" minlength="12" required></label>
        <label>Confirm password<input type="password" name="password_confirmation" minlength="12" required></label>
        <button>Create account</button>
    </form>
@endsection