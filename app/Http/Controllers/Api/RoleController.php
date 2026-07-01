<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = collect(UserRole::cases())->map(fn (UserRole $role) => [
            'name' => $role->value,
            'label' => $role->label(),
            'permissions' => $role->permissions(),
            'is_default' => true,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}
