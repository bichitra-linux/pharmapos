<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ManufacturerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $manufacturers = Manufacturer::where('company_id', $request->user()->company_id)
            ->withCount('medicines')
            ->orderBy('name')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $manufacturers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
        ]);

        $manufacturer = Manufacturer::create([
            'company_id' => $request->user()->company_id,
            'name' => $request->name,
            'country' => $request->country,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer created successfully.',
            'data' => $manufacturer,
        ], 201);
    }

    public function show(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $manufacturer->load('medicines');

        return response()->json([
            'success' => true,
            'data' => $manufacturer,
        ]);
    }

    public function update(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'country' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $manufacturer->update($request->only(['name', 'country', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer updated successfully.',
            'data' => $manufacturer->fresh(),
        ]);
    }

    public function destroy(Request $request, Manufacturer $manufacturer): JsonResponse
    {
        if ($manufacturer->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($manufacturer->medicines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete manufacturer with associated medicines.',
            ], 422);
        }

        $manufacturer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Manufacturer deleted successfully.',
        ]);
    }
}
