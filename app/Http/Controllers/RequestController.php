<?php

namespace App\Http\Controllers;

use App\Models\Api;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    /**
     * Process and execute an API endpoint call
     *
     * @param Request $request The incoming request
     * @param Api $api The API model instance
     * @return JsonResponse
     */
    public function callEndpoint(Request $request, Api $api): JsonResponse
    {
        Log::debug('Starting API endpoint call', [
            'api_id' => $api->_id,
            'request_data' => $request->all()
        ]);

        $request->validate([
            'endpoint_id' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $endpoint = $this->getAndValidateEndpoint($api, $request->input('endpoint_id'));
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
        Log::debug('Prepared API URL', ['url' => $url]);

        return $this->executeRequest($endpoint, $url, $data);
    }

    /**
     * Get and validate the endpoint exists
     *
     * @param Api $api The API model instance
     * @param string $endpointId The endpoint ID to find
     * @return mixed The endpoint or null if not found
     */
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

    /**
     * Process path parameters and replace placeholders
     *
     * @param mixed $endpoint The endpoint model instance
     * @param array $data The request data
     * @return string|array The processed path or error array
     */
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

    /**
     * Prepare request options based on endpoint type and parameters
     *
     * @param mixed $endpoint The endpoint model instance
     * @param array $data The request data
     * @return array The prepared request options
     */
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

    /**
     * Prepare multipart form data for file uploads
     *
     * @param mixed $fileParams Collection of file parameters
     * @param array $data The request data
     * @return array The prepared multipart data
     */
    private function prepareMultipartData($fileParams, array $data): array
    {
        $multipart = [];
        $this->fileStreams = [];

        // Add non-file fields
        foreach ($data as $key => $value) {
            if (!$fileParams->where('name', $key)->count()) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => is_array($value) ? json_encode($value) : $value
                ];
            }
        }

        // Process file fields
        foreach ($fileParams as $param) {
            $fileData = $data[$param->name] ?? null;
            if ($fileData) {
                $this->processFileData($multipart, $param, $fileData);
            }
        }

        return $multipart;
    }

    /**
     * Process file data and add to multipart array
     *
     * @param array $multipart Reference to multipart array
     * @param mixed $param Parameter model instance
     * @param mixed $fileData File data to process
     */
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

    /**
     * Add a file to the multipart array
     *
     * @param array $multipart Reference to multipart array
     * @param string $name Parameter name
     * @param mixed $file File data
     */
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

    /**
     * Add a file stream to the multipart array
     *
     * @param array $multipart Reference to multipart array
     * @param string $name Parameter name
     * @param resource $stream File stream
     * @param string $filename Original filename
     */
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

    /**
     * Handle blob data from request
     *
     * @param array $multipart Reference to multipart array
     * @param string $name Parameter name
     * @param array $fileData File data containing blob
     */
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

    /**
     * Execute the HTTP request to the external API
     *
     * @param mixed $endpoint The endpoint model instance
     * @param string $url The full URL to call
     * @param array $data The request data
     * @return JsonResponse
     */
    private function executeRequest($endpoint, string $url, array $data): JsonResponse
    {
        $client = new Client();
        try {
            $options = $this->prepareRequestOptions($endpoint, $data);

            Log::debug('Making API request', [
                'method' => $endpoint->method,
                'url' => $url,
                'options' => $options
            ]);

            $response = $client->request($endpoint->method, $url, $options);
            $content = $response->getBody()->getContents();
            $decoded = json_decode($content, true);

            Log::debug('API request successful', [
                'status_code' => $response->getStatusCode()
            ]);

            return response()->json([
                'status' => $response->getStatusCode(),
                'data' => $decoded ?? $content,
            ], 200);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return $this->handleRequestException($e);
        } catch (\Exception $e) {
            return $this->handleGeneralException($e);
        } finally {
            $this->closeFileStreams();
        }
    }

    /**
     * Handle Guzzle request exceptions
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     * @return JsonResponse
     */
    private function handleRequestException(\GuzzleHttp\Exception\RequestException $e): JsonResponse
    {
        Log::error('Guzzle request exception', [
            'message' => $e->getMessage(),
            'has_response' => $e->hasResponse()
        ]);

        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $statusCode = $e->getResponse()->getStatusCode();
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
    }

    /**
     * Handle general exceptions
     *
     * @param \Exception $e
     * @return JsonResponse
     */
    private function handleGeneralException(\Exception $e): JsonResponse
    {
        Log::error('Unexpected error in API call', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 500,
            'error' => $e->getMessage()
        ], 200);
    }

    /**
     * Close all opened file streams
     */
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
