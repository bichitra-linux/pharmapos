<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SaltComposition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaltCompositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SaltComposition::withCount('medicines')->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $salts = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $salts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:salt_compositions,name',
            'description' => 'nullable|string',
        ]);

        $salt = SaltComposition::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Salt composition created successfully.',
            'data' => $salt,
        ], 201);
    }

    public function show(SaltComposition $saltComposition): JsonResponse
    {
        $saltComposition->load('medicines');

        return response()->json([
            'success' => true,
            'data' => $saltComposition,
        ]);
    }

    public function update(Request $request, SaltComposition $saltComposition): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:salt_compositions,name,'.$saltComposition->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $saltComposition->update($request->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Salt composition updated successfully.',
            'data' => $saltComposition->fresh(),
        ]);
    }

    public function destroy(SaltComposition $saltComposition): JsonResponse
    {
        if ($saltComposition->medicines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete salt composition with associated medicines.',
            ], 422);
        }

        $saltComposition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salt composition deleted successfully.',
        ]);
    }
}
