<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrfToken(Request $request): JsonResponse
    {
        return response()->json(['token' => $request->session()->token()]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'account_type' => ['required', 'in:buyer,seller'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $roleNames = $data['account_type'] === 'seller' ? ['buyer', 'seller'] : ['buyer'];
            $roles = Role::query()->whereIn('name', $roleNames)->pluck('id');
            $user->roles()->attach($roles->mapWithKeys(fn ($id) => [$id => ['created_at' => now()]]));

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $this->userPayload($user)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['The supplied credentials could not be verified.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->roles()->orderBy('name')->pluck('name')->all(),
        ];
    }
}
