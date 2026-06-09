<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\MicrosoftController;

/*
|--------------------------------------------------------------------------
| LANDING PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return view('landing.index');

});

/*
|--------------------------------------------------------------------------
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

/*Route::get('/login', function () {

    return view('auth.login');

})->name('login');*/

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {

    if (Auth::user()->user_role_id == 1) {

        return redirect('/admin/dashboard');

    }

    elseif (Auth::user()->user_role_id == 2) {

        return redirect('/maintenance/dashboard');

    }

    elseif (Auth::user()->user_role_id == 3) {

        return redirect('/purchaser/dashboard');

    }

    elseif (Auth::user()->user_role_id == 4) {

        return redirect('/president/dashboard');

    }

    elseif (Auth::user()->user_role_id == 5) {

        return redirect('/accounting/dashboard');

    }

    elseif (Auth::user()->user_role_id == 6) {

        return redirect('/receiving/dashboard');

    }

    abort(403);

})->middleware(['auth'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->group(function () {

    Route::get('/admin/dashboard', function () {

        return view('admin.dashboard');

    });

    Route::get('/admin/users', function () {

        return view('admin.users.index');

    });

    Route::get('/admin/users/create', function () {

        return view('admin.users.create');

    });

    Route::post('/admin/users/store',
        [AdminController::class, 'storeUser']);

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout',
    [AuthenticatedSessionController::class, 'destroy']
)->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MAINTENANCE PERSONNEL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/maintenance/dashboard', function () {

        return view('maintenance-personnel.dashboard');

    });

});

// MICROSOFT LOGIN

Route::get(
    '/auth/microsoft',
    [MicrosoftController::class, 'redirectToMicrosoft']
)->name('auth.microsoft.redirect');

Route::get(
    '/auth/microsoft/callback',
    [MicrosoftController::class, 'handleMicrosoftCallback']
)->name('auth.microsoft.callback');

require __DIR__.'/auth.php';