<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\UserSessions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cookie-based SPA auth (Sanctum stateful): the SPA first GETs
 * /sanctum/csrf-cookie, then logs in here and the session cookie does the rest.
 * No bearer tokens are issued to the SPA.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): UserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        return $this->me($request);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        abort_unless(config('app.registration_enabled'), 404);

        $user = User::create($request->validated());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->me($request)->response()->setStatusCode(201);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('Logged out.')]);
    }

    public function me(Request $request): UserResource
    {
        return (new UserResource($request->user()))->withPermissions();
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return $this->me($request);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        // Keep this session, sign out every other device.
        UserSessions::revoke($user, $request->session()->getId());

        return response()->json(['message' => __('Password updated.')]);
    }
}
