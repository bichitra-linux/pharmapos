<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RoleController extends Controller
{
    private array $defaultRoles = [
        'owner' => ['full_access'],
        'admin' => ['manage_users', 'manage_settings', 'manage_inventory', 'manage_sales', 'manage_purchases', 'view_reports'],
        'pharmacist' => ['manage_sales', 'manage_prescriptions', 'manage_inventory', 'verify_prescriptions'],
        'cashier' => ['manage_sales', 'view_inventory'],
        'inventory_manager' => ['manage_inventory', 'manage_purchases', 'view_reports'],
    ];

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $customRoles = DB::table('roles')
            ->where('company_id', $companyId)
            ->get();

        $defaultRoles = collect($this->defaultRoles)->map(fn ($permissions, $name) => [
            'name' => $name,
            'permissions' => $permissions,
            'is_default' => true,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'default_roles' => $defaultRoles,
                'custom_roles' => $customRoles,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|max:100',
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'permissions' => json_encode($request->permissions),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = DB::table('roles')->where('id', $roleId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = DB::table('roles')
            ->where('id', $id)
            ->where('company_id', $request->user()->company_id)
            ->first();

        if (! $role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'permissions' => 'sometimes|array|min:1',
            'permissions.*' => 'string|max:100',
        ]);

        $update = [];
        if ($request->has('name')) {
            $update['name'] = $request->name;
        }
        if ($request->has('permissions')) {
            $update['permissions'] = json_encode($request->permissions);
        }
        $update['updated_at'] = now();

        DB::table('roles')->where('id', $id)->update($update);

        $updated = DB::table('roles')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $updated,
        ]);
    }
}
