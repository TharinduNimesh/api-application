<?php

namespace App\Http\Controllers;

use App\Services\ApiImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApiImportController extends Controller
{
    public function __construct(
        private ApiImportService $apiImportService
    ) {}

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:1024', // 1MB max
        ]);

        try {
            $jsonContent = json_decode(file_get_contents($request->file('file')->path()), true);
            
            if (!$jsonContent) {
                return response()->json([
                    'message' => 'Invalid JSON file content',
                    'errors' => ['file' => ['The file must contain valid JSON']]
                ], 422);
            }

            if (!$this->apiImportService->validateOpenAPI($jsonContent)) {
                return response()->json([
                    'message' => 'Invalid OpenAPI specification',
                    'errors' => ['file' => ['The file must be a valid OpenAPI 3.0 specification']]
                ], 422);
            }

            $result = $this->apiImportService->importFromOpenAPI($jsonContent, Auth::id());

            Log::info('API import completed', [
                'user_id' => Auth::id(),
                'success_count' => count($result['success']),
                'failed_count' => count($result['failed'])
            ]);

            return response()->json([
                'message' => 'API import completed',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('API import failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to import API',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}