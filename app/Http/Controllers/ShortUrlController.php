<?php
namespace App\Http\Controllers;
use App\Models\ShortUrl;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class ShortUrlController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ShortUrl::with(['user', 'company'])->latest();
        if ($user->hasRole('SuperAdmin')) {
        } elseif ($user->hasRole('Admin')) {
            abort_unless($user->company_id, 403);
            $query->where('company_id', $user->company_id);
        } elseif ($user->hasRole('Member')) {
            abort_unless($user->company_id, 403);
            $query->where('company_id', $user->company_id)->where('user_id', $user->id);
        } else {
            abort(403);
        }
        return view('urls.index', ['urls' => $query->paginate(15)]);
    }
    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Admin', 'Member') && $user->company_id, 403);
        $data = $request->validate(['original_url' => ['required', 'url:http,https', 'max:2048']]);
        for ($i = 0; $i < 5; $i++) {
            try {
                ShortUrl::create([
                    'original_url' => $data['original_url'],
                    'code' => Str::random(10),
                    'company_id' => $user->company_id,
                    'user_id' => $user->id
                ]);
                return redirect()->route('urls.index')->with('status', 'Short URL created.');
            } catch (UniqueConstraintViolationException $e) {
                if ($i === 4)
                    throw $e;
            }
        }
        abort(500);
    }
    public function resolve(string $code)
    {
        $url = ShortUrl::where('code', $code)->firstOrFail();
        $url->increment('visits');
        return redirect()->away($url->original_url, 302);
    }
}
