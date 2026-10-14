@extends('layouts.app')
@section('content')
<h1>Company</h1>
@if($companies->isEmpty())
    <form method="post" action="{{ route('companies.store') }}">@csrf
        <label>Company name <input name="name" required maxlength="150"></label>
        <button>Create company</button>
    </form>
@else
    <p>Only one company is allowed in this application.</p>
    <table><thead><tr><th>ID</th><th>Name</th></tr></thead><tbody>
    @foreach($companies as $company)
        <tr><td>{{ $company->id }}</td><td>{{ $company->name }}</td></tr>
    @endforeach
    </tbody></table>
@endif
@endsection
