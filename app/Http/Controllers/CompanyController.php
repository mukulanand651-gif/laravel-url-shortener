<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('SuperAdmin'), 403);
        return view('companies.index', ['companies' => Company::all()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('SuperAdmin'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        if (Company::exists()) {
            return back()->withErrors(['name' => 'Only one company is permitted.']);
        }
        try {
            Company::create(['name' => $data['name'], 'singleton_key' => 1]);
        } catch (UniqueConstraintViolationException $exception) {
            return back()->withErrors(['name' => 'Only one company is permitted.']);
        }
        return redirect()->route('companies.index')->with('status', 'Company created.');
    }
}
