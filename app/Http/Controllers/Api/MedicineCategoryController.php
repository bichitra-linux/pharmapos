<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MedicineCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = MedicineCategory::where('company_id', $request->user()->company_id)
            ->withCount('medicines')
            ->with('parent:id,name')
            ->orderBy('name')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:medicine_categories,id',
            'description' => 'nullable|string',
        ]);

        $category = MedicineCategory::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function show(Request $request, MedicineCategory $category): JsonResponse
    {
        if ($category->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $category->load(['parent', 'children', 'medicines']);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request, MedicineCategory $category): JsonResponse
    {
        if ($category->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:medicine_categories,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($request->only(['name', 'parent_id', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(Request $request, MedicineCategory $category): JsonResponse
    {
        if ($category->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($category->medicines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated medicines.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}
