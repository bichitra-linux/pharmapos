<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = DB::table('platform_settings')
            ->get()
            ->map(fn ($setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'group' => $setting->group,
            ])
            ->groupBy('group');

        return $this->success($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('settings') as $setting) {
                DB::table('platform_settings')
                    ->updateOrInsert(
                        ['key' => $setting['key']],
                        [
                            'value' => $setting['value'],
                            'updated_at' => now(),
                        ]
                    );
            }
        });

        return $this->success(null, 'Settings updated successfully.');
    }
}
