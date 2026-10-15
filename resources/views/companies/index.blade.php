@extends('layouts.app')
@section('content')
    <h1>Companies</h1>
    <form method="post" action="{{ route('companies.store') }}">
        @csrf
        <label>New company name <input name="name" required maxlength="150" value="{{ old('name') }}"></label>
        <button>Create company</button>
    </form>
    <p>To create a company and send its first Admin invitation in one action, use <a
            href="{{ route('invitations.create') }}">Invite</a>.</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Users</th>
                <th>Short URLs</th>
            </tr>
        </thead>
        <tbody>
            @forelse($companies as $company)
                <tr>
                    <td>{{ $company->id }}</td>
                    <td>{{ $company->name }}</td>
                    <td>{{ $company->users_count }}</td>
                    <td>{{ $company->short_urls_count }}</td>
                </tr>
            @empty<tr>
                <td colspan="4">No companies yet.</td>
            </tr>@endforelse
        </tbody>
    </table>
    {{ $companies->links() }}
@endsection