@extends('layouts.app')
@section('content')
    <h1>Short URLs</h1>
    @if(auth()->user()->hasRole('Admin', 'Member'))
        <h2>Create a short URL</h2>
        <form method="post" action="{{ route('urls.store') }}">
            @csrf
            <label>Original URL<input type="url" name="original_url" placeholder="" required></label>
            <button>Generate</button>
        </form>
    @endif
    <p class="muted">Visible URLs depend on your assigned role.</p>
    <table>
        <thead>
            <tr>
                <th>Short URL</th>
                <th>Original URL</th>
                <th>Company</th>
                <th>Creator</th>
                <th>Visits</th>
            </tr>
        </thead>
        <tbody>
            @forelse($urls as $url)
                <tr>
                    <td><a href="{{ route('urls.resolve', $url->code) }}">{{ route('urls.resolve', $url->code) }}</a></td>
                    <td class="break">{{ $url->original_url }}</td>
                    <td>{{ $url->company->name }}</td>
                    <td>{{ $url->user->name }}</td>
                    <td>{{ $url->visits }}</td>
            </tr>@empty<tr>
                    <td colspan="5">No URLs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    {{ $urls->links() }}
@endsection