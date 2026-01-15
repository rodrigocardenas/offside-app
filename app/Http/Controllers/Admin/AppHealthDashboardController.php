<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AppHealthMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppHealthDashboardController extends Controller
{
    public function __construct(private readonly AppHealthMetricsService $metrics)
    {
    }

    public function index(Request $request): View
    {
        [$hours, $trendDays] = $this->parseParams($request);

        return view('admin.app-health-dashboard', [
            'hours' => $hours,
            'trendDays' => $trendDays,
            'dashboard' => $this->metrics->getDashboardData($hours, $trendDays),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$hours, $trendDays] = $this->parseParams($request);

        return response()->json([
            'hours' => $hours,
            'trend_days' => $trendDays,
            'data' => $this->metrics->getDashboardData($hours, $trendDays),
        ]);
    }

    private function parseParams(Request $request): array
    {
        $hours = $this->sanitize($request->integer('hours'), 24, 1, 168);
        $trendDays = $this->sanitize($request->integer('trend_days'), 7, 1, 30);

        return [$hours, $trendDays];
    }

    private function sanitize(?int $value, int $default, int $min, int $max): int
    {
        $value = $value ?? $default;
        $value = max($min, $value);
        return min($max, $value);
    }
}
