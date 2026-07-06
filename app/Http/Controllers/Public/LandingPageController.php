<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LandingPageController extends Controller
{
    public function show(string $slug = 'default')
    {
        $page = LandingPage::where('slug', $slug)
            ->whereNotNull('published_at')
            ->firstOr(function () {
                return LandingPage::where('slug', 'default')->first();
            });

        if (!$page) {
            return redirect('/login');
        }

        $previewToken = request()->query('preview_token');
        $isPreview = $previewToken && $previewToken === sha1('preview-' . $page->updated_at->timestamp);

        if (!$page->isPublished() && !$isPreview) {
            return redirect('/login');
        }

        return view('landing', ['page' => $page]);
    }

    public function stats(): JsonResponse
    {
        $stats = Cache::remember('landing.stats', 3600, function () {
            return [
                'prescriptions_dispensed' => \App\Models\Sale::count(),
                'active_pharmacies' => \App\Models\Company::whereNull('suspended_at')->where('is_active', true)->count(),
                'medicines_tracked' => \DB::table('medicines')->count(),
                'batches_in_stock' => \DB::table('medicine_batches')->where('quantity_in_stock', '>', 0)->count(),
            ];
        });

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function plans(): JsonResponse
    {
        $plans = \App\Models\SubscriptionPlan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'price_monthly', 'price_yearly', 'max_outlets', 'max_users', 'max_medicines']);

        return response()->json(['success' => true, 'data' => $plans]);
    }
}
