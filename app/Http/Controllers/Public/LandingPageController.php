<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
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
        $isPreview = $previewToken && hash_equals((string) $page->preview_token, (string) $previewToken);

        if (!$page->isPublished() && !$isPreview) {
            return redirect('/login');
        }

        $plans = Cache::remember('landing.plans', 300, function () {
            return SubscriptionPlan::where('is_active', true)
                ->orderBy('price_monthly')
                ->get(['id', 'name', 'price_monthly', 'price_yearly', 'max_outlets', 'max_users', 'max_medicines']);
        });

        return view('landing', ['page' => $page, 'plans' => $plans]);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'price_monthly', 'price_yearly', 'max_outlets', 'max_users', 'max_medicines']);

        return response()->json(['success' => true, 'data' => $plans]);
    }
}
