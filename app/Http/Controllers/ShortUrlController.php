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
        abort_if($user->hasRole('SuperAdmin'), 403);
        abort_unless($user->company_id, 403);

        $query = ShortUrl::query()->with(['user', 'company'])->latest();

        if ($user->hasRole('Admin')) {
           
            $query->where('company_id', '!=', $user->company_id);
        } elseif ($user->hasRole('Member')) {
            $query->where('company_id', $user->company_id)
                ->where('user_id', '!=', $user->id);
        } elseif ($user->hasRole('Sales', 'Manager')) {
            $query->where('company_id', $user->company_id);
        } else {
            abort(403);
        }

        return view('urls.index', ['urls' => $query->paginate(15)]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('Sales', 'Manager') && $user->company_id, 403);

        $data = $request->validate([
            'original_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                ShortUrl::create([
                    'original_url' => $data['original_url'],
                    'code' => Str::random(10),
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                ]);
                return redirect()->route('urls.index')->with('status', 'Short URL created.');
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === 4) {
                    throw $e;
                }
            }
        }

        abort(500);
    }

    public function resolve(Request $request, string $code)
    {
        $url = ShortUrl::query()->where('code', $code)->firstOrFail();
        $user = $request->user();
        abort_unless(
            $user->company_id && (int) $user->company_id === (int) $url->company_id,
            403
        );
        $url->increment('visits');
        return redirect()->away($url->original_url, 302);
    }
}
