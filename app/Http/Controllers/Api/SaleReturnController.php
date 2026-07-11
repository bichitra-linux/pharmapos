<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ProcessReturn;
use App\Http\Controllers\Controller;
use App\Models\CustomerReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaleReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerReturn::where('outlet_id', $request->user()->outlet_id)
            ->with(['sale:id,invoice_number,customer_id', 'sale.customer:id,name,phone', 'processedBy:id,name']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $returns = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json(['success' => true, 'data' => $returns]);
    }

    public function store(Request $request, ProcessReturn $processReturn): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_id' => 'required|exists:medicine_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'nullable|string|max:255',
            'refund_amount' => 'required|numeric|min:0',
            'refund_method' => 'nullable|string|max:50',
        ]);

        $return = $processReturn->execute('sale', $request);

        return response()->json([
            'success' => true,
            'message' => 'Return processed successfully.',
            'data' => $return,
        ], 201);
    }
}
