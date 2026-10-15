<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company URL Shortener</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>

<body>
    <header><strong>URL Shortener</strong>
        <nav>
            @auth
                <a href="{{ route('dashboard') }}">Dashboard</a>
                @if(!auth()->user()->hasRole('SuperAdmin'))<a href="{{ route('urls.index') }}">URLs</a>@endif
                @if(auth()->user()->hasRole('SuperAdmin'))<a href="{{ route('companies.index') }}">Company</a>@endif
                @if(auth()->user()->hasRole('SuperAdmin', 'Admin'))<a
                href="{{ route('invitations.create') }}">Invite</a>@endif
                <form class="inline" method="post" action="{{ route('logout') }}">@csrf<button>Logout</button></form>
            @endauth
        </nav>
    </header>
    <main>
        @auth<p class="muted">{{ auth()->user()->name }} — {{ auth()->user()->role }}</p>@endauth
        @if(session('status'))
        <div class="notice">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="error">
                <ul>@foreach($errors->all() as $message)
                <li>{{ $message }}</li>@endforeach
                </ul>
        </div>@endif
        @yield('content')
    </main>
</body>

</html>