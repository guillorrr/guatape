<?php

use Illuminate\Support\Facades\Route;

// The UI is the Vue SPA (src/frontend); this app only serves the API.
Route::get('/', fn () => response()->json(['name' => config('app.name')]));
