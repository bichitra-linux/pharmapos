<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
            ->where('company_id', '!=', 0)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('Failed login attempt', ['email' => $request->email, 'reason' => 'invalid_credentials']);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (! $user->is_active) {
            Log::warning('Failed login attempt', ['email' => $request->email, 'reason' => 'account_deactivated']);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $company = $user->company;
        if ($company && (! $company->is_active || ($company->subscription_expires_at && $company->subscription_expires_at->isPast()))) {
            Log::warning('Failed login attempt', ['email' => $request->email, 'reason' => 'company_inactive_or_expired']);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('pharmapos')->plainTextToken;

        $user->load(['company', 'outlet']);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $company = Company::create([
            'name' => $request->company_name,
            'slug' => Str::slug($request->company_name).'-'.Str::random(5),
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'address' => $request->company_address,
            'pan_number' => $request->pan_number,
            'is_active' => true,
        ]);

        $outlet = Outlet::create([
            'company_id' => $company->id,
            'name' => 'Main Outlet',
            'address' => $request->company_address,
            'phone' => $request->company_phone,
            'is_main_outlet' => true,
            'is_active' => true,
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'outlet_id' => $outlet->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('pharmapos')->plainTextToken;

        $user->load(['company', 'outlet']);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['company', 'outlet']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function outlets(Request $request): JsonResponse
    {
        $outlets = Outlet::where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'address', 'is_main_outlet']);

        return response()->json([
            'success' => true,
            'data' => $outlets,
        ]);
    }

    public function switchOutlet(Request $request): JsonResponse
    {
        $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        $user = $request->user();
        $outlet = Outlet::where('id', $request->outlet_id)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$outlet) {
            return response()->json([
                'success' => false,
                'message' => 'Outlet not found.',
            ], 404);
        }

        $user->update(['outlet_id' => $outlet->id]);

        return response()->json([
            'success' => true,
            'message' => "Switched to {$outlet->name}.",
            'data' => $user->fresh()->load('outlet'),
        ]);
    }
}
