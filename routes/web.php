<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;
Route::get('/login', [AuthController::class, 'form'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.submit');
Route::get('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept')->middleware('guest');
Route::post('/invitations/{token}', [InvitationController::class, 'register'])->name('invitations.register')->middleware('guest');
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/invite', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('/invite', [InvitationController::class, 'store'])->name('invitations.store');
    Route::get('/urls', [ShortUrlController::class, 'index'])->name('urls.index');
    Route::post('/urls', [ShortUrlController::class, 'store'])->name('urls.store');
    Route::get('/s/{code}', [ShortUrlController::class, 'resolve'])->name('urls.resolve');
});
