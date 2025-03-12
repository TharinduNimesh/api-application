<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\ApiUsage;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    private array $fileStreams = [];

    public function callEndpoint(Request $request, Api $api): JsonResponse
    {
        // Check for middleware access errors
        if ($request->attributes->has('api_access_error')) {
            $error = $request->attributes->get('api_access_error');
            return response()->json([
                'status' => $error['status'],
                'error' => $error['message']
            ], $error['status']);
        }

        try {
            $request->validate([
                'endpoint_id' => 'required|string',
                'data' => 'nullable|array'
            ]);

            $endpoint = $api->endpoints()->where('_id', $request->input('endpoint_id'))->first();
            if (!$endpoint) {
                return response()->json([
                    'status' => 404,
                    'error' => 'Endpoint not found'
                ], 500);
            }

            $data = $request->input('data', []);
            $processedPath = $this->processPathParameters($endpoint, $data);
            
            if (is_array($processedPath) && isset($processedPath['error'])) {
                return response()->json([
                    'status' => 422,
                    'error' => $processedPath['error']
                ], 422);
            }

            $url = rtrim($api->baseUrl, '/') . '/' . ltrim($processedPath, '/');
            return $this->executeRequest($endpoint, $url, $data);

        } catch (\Exception $e) {
            Log::error('Error processing API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 200);
        }
    }


    /**
     * Test a draft endpoint that hasn't been saved to the database yet
     */
    public function testDraftEndpoint(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'endpoint' => 'required|array',
                'api' => 'required|array',
                'data' => 'nullable|array'
            ]);

            $endpoint = (object)$request->input('endpoint');
            $api = (object)$request->input('api');
            $data = $request->input('data', []);

            // Process the path parameters as we do for saved endpoints
            $processedPath = $this->processPathParametersFromObject($endpoint, $data);
            
            if (is_array($processedPath) && isset($processedPath['error'])) {
                return response()->json([
                    'status' => 422,
                    'error' => $processedPath['error']
                ], 200);
            }

            $url = rtrim($api->baseUrl, '/') . '/' . ltrim($processedPath, '/');
            
            // Use executeRequestDraft which doesn't log API usage
            return $this->executeRequestDraft($endpoint, $url, $data);

        } catch (\Exception $e) {
            Log::error('Error processing draft API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 200);
        }
    }

    /**
     * Execute a request for a draft endpoint without logging API usage
     */
    private function executeRequestDraft($endpoint, string $url, array $data): JsonResponse
    {
        $startTime = microtime(true);
        $urlParts = parse_url($url);
        $clientConfig = [];
        
        if (isset($urlParts['scheme']) && $urlParts['scheme'] === 'http') {
            Log::warning('Making insecure HTTP request', ['url' => $url]);
            $clientConfig['verify'] = false;
        }
    
        try {
            $client = new Client($clientConfig);
            $options = $this->prepareRequestOptionsFromObject($endpoint, $data);
            
            Log::debug('Making draft API request', [
                'method' => $endpoint->method,
                'url' => $url,
                'options' => $options
            ]);

            $response = $client->request($endpoint->method, $url, $options);
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);
            $responseTime = (microtime(true) - $startTime) * 1000;
            $statusCode = $response->getStatusCode();

            // No API usage tracking for draft endpoints

            return response()->json([
                'status' => $statusCode,
                'data' => $decoded ?? $content,
                'response_time_ms' => round($responseTime),
            ], 200);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            
            // No API usage tracking for draft endpoints

            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                return response()->json([
                    'status' => $statusCode,
                    'error' => 'Request failed',
                    'response' => json_decode($responseBody, true) ?? $responseBody,
                    'response_time_ms' => round($responseTime),
                ], 200);
            }

            return response()->json([
                'status' => 500,
                'error' => $e->getMessage(),
                'response_time_ms' => round($responseTime),
            ], 200);
        } finally {
            $this->closeFileStreams();
        }
    }
    
    /**
     * Execute request with API usage tracking for saved endpoints
     */
    private function executeRequest($endpoint, string $url, array $data): JsonResponse
    {
        $startTime = microtime(true);
        $urlParts = parse_url($url);
        $clientConfig = [];
        
        if (isset($urlParts['scheme']) && $urlParts['scheme'] === 'http') {
            Log::warning('Making insecure HTTP request', ['url' => $url]);
            $clientConfig['verify'] = false;
        }
    
        try {
            $client = new Client($clientConfig);
            $options = $this->prepareRequestOptions($endpoint, $data);
            
            Log::debug('Making API request', [
                'method' => $endpoint->method,
                'url' => $url,
                'options' => $options
            ]);

            $response = $client->request($endpoint->method, $url, $options);
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);
            $responseTime = (microtime(true) - $startTime) * 1000;
            $statusCode = $response->getStatusCode();

            // Track API usage - consider it successful only for 2xx status codes
            ApiUsage::create([
                'api_id' => $endpoint->api_id,
                'endpoint_id' => $endpoint->_id,
                'user_id' => Auth::id(),
                'timestamp' => now(),
                'response_time' => round($responseTime),
                'status_code' => $statusCode,
                'is_success' => $statusCode >= 200 && $statusCode < 300,
                'error_message' => $statusCode >= 400 ? 'HTTP ' . $statusCode : null,
                'request_method' => $endpoint->method,
                'request_path' => $endpoint->path
            ]);

            return response()->json([
                'status' => $statusCode,
                'data' => $decoded ?? $content,
                'response_time_ms' => round($responseTime),
            ], 200);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;
            
            // Track failed API usage
            ApiUsage::create([
                'api_id' => $endpoint->api_id,
                'endpoint_id' => $endpoint->_id,
                'user_id' => Auth::id(),
                'timestamp' => now(),
                'response_time' => round($responseTime),
                'status_code' => $statusCode,
                'is_success' => false,
                'error_message' => $e->getMessage(),
                'request_method' => $endpoint->method,
                'request_path' => $endpoint->path
            ]);

            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                return response()->json([
                    'status' => $statusCode,
                    'error' => 'Request failed',
                    'response' => json_decode($responseBody, true) ?? $responseBody,
                    'response_time_ms' => round($responseTime),
                ], 200);
            }

            return response()->json([
                'status' => 500,
                'error' => $e->getMessage(),
                'response_time_ms' => round($responseTime),
            ], 200);
        } finally {
            $this->closeFileStreams();
        }
    }
    
    /**
     * Process path parameters from a non-database endpoint object
     */
    /**
     * Process path parameters from a non-database endpoint object.
     *
     * @param object $endpoint The endpoint object containing path and parameters.
     * @param array $data The data array containing parameter values.
     * @return string|array The processed path or an error array if a parameter is missing.
     */
    private function processPathParametersFromObject($endpoint, array &$data)
    {
        $path = $endpoint->path;
        $parameters = $endpoint->parameters ?? [];
        
        foreach ($parameters as $param) {
            $param = (object)$param;
            if (isset($param->location) && $param->location === 'path') {
                if (!isset($data[$param->name])) {
                    Log::warning('Missing path parameter', ['parameter' => $param->name]);
                    return ['error' => "Missing path parameter: {$param->name}"];
                }
                $path = str_replace('{' . $param->name . '}', $data[$param->name], $path);
                unset($data[$param->name]);
            }
        }

        return $path;
    }

    /**
     * Prepare request options from a non-database endpoint object
     */
    private function prepareRequestOptionsFromObject($endpoint, array $data): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        // Convert parameters array to collection for filtering
        $parameters = collect($endpoint->parameters ?? []);
        $fileParams = $parameters->where('type', 'file');
        
        if (in_array($endpoint->method, ['POST', 'PUT', 'PATCH'])) {
            if ($fileParams->count() > 0) {
                $options['multipart'] = $this->prepareMultipartDataFromObject($fileParams, $data);
            } else {
                $options['json'] = $data;
            }
        } elseif ($endpoint->method === 'GET') {
            $options['query'] = $data;
        }

        return $options;
    }

    /**
     * Prepare multipart data for file uploads from a non-database endpoint object
     */
    private function prepareMultipartDataFromObject($fileParams, array $data): array
    {
        $multipart = [];
        $this->fileStreams = [];

        foreach ($data as $key => $value) {
            if (!$fileParams->where('name', $key)->count()) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : $value
                ];
            }
        }

        foreach ($fileParams as $param) {
            $param = (object)$param;
            $fileData = $data[$param->name] ?? null;
            if ($fileData) {
                $this->processFileData($multipart, $param, $fileData);
            }
        }

        return $multipart;
    }

    private function getAndValidateEndpoint(Api $api, string $endpointId)
    {
        $endpoint = $api->endpoints()->where('_id', $endpointId)->first();
        if (!$endpoint) {
            Log::warning('Endpoint not found', ['endpoint_id' => $endpointId]);
            return null;
        }

        Log::debug('Found endpoint', [
            'endpoint_id' => $endpoint->_id,
            'method' => $endpoint->method,
            'path' => $endpoint->path
        ]);

        return $endpoint;
    }

    private function processPathParameters($endpoint, array &$data)
    {
        $path = $endpoint->path;
        $endpoint->load('parameters');
        
        foreach ($endpoint->parameters as $param) {
            if (isset($param->location) && $param->location === 'path') {
                if (!isset($data[$param->name])) {
                    Log::warning('Missing path parameter', ['parameter' => $param->name]);
                    return ['error' => "Missing path parameter: {$param->name}"];
                }
                $path = str_replace('{' . $param->name . '}', $data[$param->name], $path);
                unset($data[$param->name]);
            }
        }

        return $path;
    }

    private function prepareRequestOptions($endpoint, array $data): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ]
        ];

        $fileParams = $endpoint->parameters->where('type', 'file');
        
        if (in_array($endpoint->method, ['POST', 'PUT', 'PATCH'])) {
            if ($fileParams->count() > 0) {
                $options['multipart'] = $this->prepareMultipartData($fileParams, $data);
            } else {
                $options['json'] = $data;
            }
        } elseif ($endpoint->method === 'GET') {
            $options['query'] = $data;
        }

        return $options;
    }

    private function prepareMultipartData($fileParams, array $data): array
    {
        $multipart = [];
        $this->fileStreams = [];

        foreach ($data as $key => $value) {
            if (!$fileParams->where('name', $key)->count()) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : $value
                ];
            }
        }

        foreach ($fileParams as $param) {
            $fileData = $data[$param->name] ?? null;
            if ($fileData) {
                $this->processFileData($multipart, $param, $fileData);
            }
        }

        return $multipart;
    }

    private function processFileData(array &$multipart, $param, $fileData): void
    {
        if ($param->fileConfig['multiple'] && is_array($fileData)) {
            foreach ($fileData as $file) {
                $this->addFileToMultipart($multipart, $param->name . '[]', $file);
            }
        } else {
            $this->addFileToMultipart($multipart, $param->name, $fileData);
        }
    }

    private function addFileToMultipart(array &$multipart, string $name, $file): void
    {
        try {
            if (is_array($file) && isset($file['tmp_name'])) {
                $stream = fopen($file['tmp_name'], 'r');
                $this->addStreamToMultipart($multipart, $name, $stream, $file['name']);
            } elseif (is_array($file) && isset($file['objectURL'])) {
                $this->handleBlobData($multipart, $name, $file);
            } elseif (is_object($file) && method_exists($file, 'getPathname')) {
                $stream = fopen($file->getPathname(), 'r');
                $this->addStreamToMultipart($multipart, $name, $stream, $file->getClientOriginalName());
            }
        } catch (\Exception $e) {
            Log::error('Error processing file', [
                'error' => $e->getMessage(),
                'name' => $name
            ]);
        }
    }

    private function addStreamToMultipart(array &$multipart, string $name, $stream, string $filename): void
    {
        if ($stream === false) {
            throw new \Exception('Failed to open file: ' . $filename);
        }
        $this->fileStreams[] = $stream;
        $multipart[] = [
            'name' => $name,
            'contents' => $stream,
            'filename' => $filename
        ];
    }

    private function handleBlobData(array &$multipart, string $name, array $fileData): void
    {
        $blobContent = file_get_contents('php://input');
        $tempFile = tmpfile();
        if ($tempFile === false) {
            throw new \Exception('Failed to create temporary file');
        }
        fwrite($tempFile, $blobContent);
        $metaData = stream_get_meta_data($tempFile);
        $this->fileStreams[] = $tempFile;
        $multipart[] = [
            'name' => $name,
            'contents' => fopen($metaData['uri'], 'r'),
            'filename' => basename($fileData['name'] ?? 'blob')
        ];
    }

    private function closeFileStreams(): void
    {
        if (isset($this->fileStreams)) {
            foreach ($this->fileStreams as $stream) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            $this->fileStreams = [];
        }
    }
}
