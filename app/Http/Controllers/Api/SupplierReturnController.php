<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ProcessReturn;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SupplierReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('supplier_returns')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_returns.supplier_id')
            ->where('supplier_returns.company_id', $request->user()->company_id)
            ->select('supplier_returns.*', 'suppliers.name as supplier_name');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_returns.supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('supplier_returns.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('supplier_returns.created_at', '<=', $request->date_to);
        }

        $returns = $query->orderByDesc('supplier_returns.created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json(['success' => true, 'data' => $returns]);
    }

    public function store(Request $request, ProcessReturn $processReturn): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $return = $processReturn->execute('supplier', $request);

        return response()->json([
            'success' => true,
            'message' => 'Supplier return processed successfully.',
            'data' => $return,
        ], 201);
    }
}
