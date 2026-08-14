<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

final class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('company_id', $request->user()->company_id)
            ->select('id', 'name', 'email', 'phone', 'role', 'outlet_id', 'is_active', 'created_at')
            ->with('outlet:id,name')
            ->orderBy('name')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:owner,admin,pharmacist,cashier,inventory_staff',
            'outlet_id' => 'nullable|exists:outlets,id',
            'permissions' => 'nullable|array',
        ]);

        $actor = $request->user();

        if ($request->role === 'owner' && ! $actor->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the company owner can assign the owner role.',
            ], 403);
        }

        $companyId = $actor->company_id;

        // Validate outlet belongs to company
        if ($request->outlet_id) {
            $outletExists = Outlet::where('id', $request->outlet_id)
                ->where('company_id', $companyId)
                ->exists();
            if (! $outletExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected outlet does not belong to your company.',
                ], 422);
            }
        }

        $existing = User::where('email', $request->email)
            ->where('company_id', $companyId)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A user with this email already exists in your company.',
            ], 422);
        }

        $user = User::create([
            'company_id' => $companyId,
            'outlet_id' => $request->outlet_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => $request->role,
            'permissions' => $request->permissions,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user->makeHidden(['password']),
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        if ($user->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $user->load('outlet:id,name');

        return response()->json([
            'success' => true,
            'data' => $user->makeHidden(['password']),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->where(fn ($q) => $q->where('company_id', $user->company_id))->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|in:owner,admin,pharmacist,cashier,inventory_staff',
            'outlet_id' => ['nullable', 'exists:outlets,id', function ($attribute, $value, $fail) use ($request) {
                $outletExists = Outlet::where('id', $value)
                    ->where('company_id', $request->user()->company_id)
                    ->exists();
                if (! $outletExists) {
                    $fail('The selected outlet does not belong to your company.');
                }
            }],
            'permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $actor = $request->user();

        $targetIsOwner = $user->role->value === 'owner' || $request->role === 'owner';

        if ($targetIsOwner && ! $actor->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the company owner can assign or modify the owner role.',
            ], 403);
        }

        $user->update($request->only(['name', 'email', 'phone', 'role', 'outlet_id', 'permissions', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user->fresh()->makeHidden(['password']),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
