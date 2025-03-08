<?php

namespace App\Http\Controllers;

use App\Models\ApiUsage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiStatsController extends Controller
{
    public function getStats(Request $request, string $apiId): JsonResponse
    {
        $timeframe = $request->input('timeframe', 3600); // Default to last hour

        // Get overall API stats
        $overallStats = [
            'successRate' => ApiUsage::getSuccessRate($apiId, $timeframe),
            'avgResponseTime' => ApiUsage::getAverageResponseTime($apiId, $timeframe),
            'totalRequests' => ApiUsage::getTotalRequests($apiId, $timeframe),
        ];

        // Get endpoint-specific stats
        $endpointStats = ApiUsage::getEndpointStats($apiId, $timeframe);

        return response()->json([
            'overall' => $overallStats,
            'endpoints' => $endpointStats
        ]);
    }
}