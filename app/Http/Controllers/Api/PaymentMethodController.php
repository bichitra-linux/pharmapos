<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }
}
