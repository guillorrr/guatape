<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\SetPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\SyncRolesRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ListQuery;
use App\Support\UserSessions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = ListQuery::for(User::query()->with('roles'), $request)
            ->search(['name', 'email'])
            ->sortable(['name', 'email', 'created_at'], default: 'name')
            ->filter('role', fn ($q, string $role) => $q->role($role))
            ->paginate();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return (new UserResource($user))->withPermissions();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create($data);
        $user->syncRoles($data['roles'] ?? []);

        return (new UserResource($user))->withPermissions()->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return (new UserResource($user))->withPermissions();
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => __('You cannot delete your own user.')]);
        }

        $this->ensureNotLastAdmin($user);

        UserSessions::revoke($user);
        $user->delete();

        return response()->json(['message' => __('User deleted.')]);
    }

    public function syncRoles(SyncRolesRequest $request, User $user): UserResource
    {
        $roles = $request->validated('roles');

        if (! in_array(UserRole::Admin->value, $roles, true)) {
            $this->ensureNotLastAdmin($user);
        }

        $user->syncRoles($roles);

        return (new UserResource($user))->withPermissions();
    }

    public function setPassword(SetPasswordRequest $request, User $user): JsonResponse
    {
        $user->update(['password' => $request->validated('password')]);

        // Admin-initiated reset: sign the user out everywhere.
        UserSessions::revoke($user);

        return response()->json(['message' => __('Password updated. The user was signed out of every session.')]);
    }

    private function ensureNotLastAdmin(User $user): void
    {
        $admin = UserRole::Admin->value;

        if ($user->hasRole($admin) && User::role($admin)->count() <= 1) {
            throw ValidationException::withMessages(['roles' => __('The system must keep at least one admin.')]);
        }
    }
}
