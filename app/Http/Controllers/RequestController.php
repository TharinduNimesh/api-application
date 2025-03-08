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
                ], 200);
            }

            $data = $request->input('data', []);
            $processedPath = $this->processPathParameters($endpoint, $data);
            
            if (is_array($processedPath) && isset($processedPath['error'])) {
                return response()->json([
                    'status' => 422,
                    'error' => $processedPath['error']
                ], 200);
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
                ], 200);
            }

            return response()->json([
                'status' => 500,
                'error' => $e->getMessage()
            ], 200);
        } finally {
            $this->closeFileStreams();
        }
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
