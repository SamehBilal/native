<?php

use App\NativeComponents\Explore;
use App\NativeComponents\Login;
use App\NativeComponents\Profile;
use App\NativeComponents\ProviderDashboard;
use App\NativeComponents\ProviderOffers;
use App\NativeComponents\ProviderRequestDetail;
use App\NativeComponents\Register;
use App\NativeComponents\RequestOffers;
use App\NativeComponents\Splash;
use App\NativeComponents\Tracking;
use App\NativeComponents\Welcome;
use Illuminate\Support\Facades\Route;

// Namespaced under /app (baked into each literal URI, not a route-group
// prefix — NativePHP's own NativeRouter registry keys off the exact string
// passed to Route::native() and knows nothing about Laravel group prefixes)
// so these native screens never collide with the existing web app's routes
// at the same top-level paths (e.g. /login).

// Cold-start: brief branded splash while we check for a stored session,
// then routes to Welcome (logged out) or straight into the app (logged in).
Route::native('/app/splash', Splash::class);
Route::native('/app/welcome', Welcome::class);

// Auth
Route::native('/app/login', Login::class);
Route::native('/app/register', Register::class);

// Customer flow
Route::native('/app/explore', Explore::class);
Route::native('/app/request-offers/{id}', RequestOffers::class);

// Provider flow
Route::native('/app/provider-dashboard', ProviderDashboard::class);
Route::native('/app/provider-request-detail/{id}', ProviderRequestDetail::class);
Route::native('/app/provider-offers', ProviderOffers::class);

// Shared
Route::native('/app/tracking/{id}', Tracking::class);
Route::native('/app/profile', Profile::class);
