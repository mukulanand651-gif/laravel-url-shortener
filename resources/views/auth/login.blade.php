@extends('layouts.app')
@section('content')
    <h1>Sign in</h1>
    <form method="post" action="{{ route('login.submit') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button>Login</button>
    </form>
@endsection