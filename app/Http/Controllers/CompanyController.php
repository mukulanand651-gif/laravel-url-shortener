<?php
namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('SuperAdmin'), 403);
        return view('companies.index', ['companies' => Company::withCount(['users', 'shortUrls'])->orderBy('id')->paginate(15)]);
    }
    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('SuperAdmin'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);
        Company::create($data);
        return redirect()->route('companies.index')->with('status', 'Company created. Invite its first Admin from the Invite page.');
    }
}
