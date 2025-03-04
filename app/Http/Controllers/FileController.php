<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    /**
     * Store a new file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240' // 10MB max file size
        ]);

        if ($request->file('file')->isValid()) {
            try {
                $path = $request->file('file')->store('uploads', 'public');
                
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'path' => $path
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to store file: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to upload file'
        ], 400);
    }
}
