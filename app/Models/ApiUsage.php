<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ApiUsage extends Model
{
    protected $collection = 'api_usage';

    protected $fillable = [
        'api_id',
        'endpoint_id',
        'user_id',
        'timestamp',
        'response_time', // in milliseconds
        'status_code',
        'is_success',
        'error_message',
        'request_method',
        'request_path'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'response_time' => 'integer',
        'is_success' => 'boolean'
    ];

    public function api(): BelongsTo
    {
        return $this->belongsTo(Api::class);
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getSuccessRate(string $apiId, ?int $timeframe = null): float
    {
        $query = self::where('api_id', $apiId);
        
        if ($timeframe) {
            $query->where('timestamp', '>=', now()->subSeconds($timeframe));
        }

        $total = $query->count();
        if ($total === 0) return 0;

        $successful = $query->where('is_success', true)->count();
        return ($successful / $total) * 100;
    }

    public static function getAverageResponseTime(string $apiId, ?int $timeframe = null): float
    {
        $query = self::where('api_id', $apiId)
            ->where('is_success', true);
        
        if ($timeframe) {
            $query->where('timestamp', '>=', now()->subSeconds($timeframe));
        }

        return (float) $query->avg('response_time') ?? 0;
    }

    public static function getTotalRequests(string $apiId, ?int $timeframe = null): int
    {
        $query = self::where('api_id', $apiId);
        
        if ($timeframe) {
            $query->where('timestamp', '>=', now()->subSeconds($timeframe));
        }

        return $query->count();
    }

    public static function getEndpointStats(string $apiId, ?int $timeframe = null): array
    {
        $matchStage = [
            'api_id' => $apiId
        ];

        if ($timeframe) {
            $matchStage['timestamp'] = [
                '$gte' => now()->subSeconds($timeframe)->toDateTime()
            ];
        }

        $pipeline = [
            [
                '$match' => $matchStage
            ],
            [
                '$group' => [
                    '_id' => [
                        'endpoint_id' => '$endpoint_id',
                        'path' => '$request_path',
                        'method' => '$request_method'
                    ],
                    'avgResponseTime' => ['$avg' => '$response_time'],
                    'totalRequests' => ['$sum' => 1],
                    'successfulRequests' => [
                        '$sum' => ['$cond' => ['if' => '$is_success', 'then' => 1, 'else' => 0]]
                    ]
                ]
            ],
            [
                '$addFields' => [
                    'endpoint_id' => '$_id.endpoint_id',
                    'path' => '$_id.path',
                    'method' => '$_id.method',
                    'successRate' => [
                        '$multiply' => [
                            [
                                '$cond' => [
                                    'if' => ['$eq' => ['$totalRequests', 0]],
                                    'then' => 0,
                                    'else' => [
                                        '$divide' => ['$successfulRequests', '$totalRequests']
                                    ]
                                ]
                            ],
                            100
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    'endpoint_id' => 1,
                    'path' => 1,
                    'method' => 1,
                    'totalRequests' => 1,
                    'avgResponseTime' => ['$round' => ['$avgResponseTime', 2]],
                    'successRate' => ['$round' => ['$successRate', 2]]
                ]
            ]
        ];

        return self::raw(function($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });
    }
}
