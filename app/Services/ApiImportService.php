<?php

namespace App\Services;

use App\Models\Api;
use App\Models\Endpoint;
use App\Models\Parameter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiImportService
{
    /**
     * Validate OpenAPI structure
     */
    public function validateOpenAPI(array $data): bool
    {
        $validator = Validator::make($data, [
            'openapi' => 'required|string',
            'info' => 'required|array',
            'info.title' => 'required|string',
            'info.version' => 'required|string',
            'paths' => 'required|array'
        ]);

        return !$validator->fails();
    }

    /**
     * Import APIs from OpenAPI specification
     */
    public function importFromOpenAPI(array $openApiData, string|int $userId): array
    {
        $imported = ['success' => [], 'failed' => []];

        try {
            // Create API record
            $api = Api::create([
                'name' => $openApiData['info']['title'],
                'description' => $openApiData['info']['description'] ?? 'Imported from OpenAPI specification',
                'type' => 'FREE', // Default type
                'rateLimit' => 60, // Default rate limit
                'created_by' => $userId,
                'is_active' => true,
                'baseUrl' => $openApiData['servers'][0]['url'] ?? null,
            ]);

            // Process paths
            foreach ($openApiData['paths'] as $path => $methods) {
                foreach ($methods as $method => $details) {
                    if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'])) {
                        continue; // Skip non-HTTP method properties
                    }
                    
                    try {
                        $endpoint = new Endpoint([
                            'name' => $details['summary'] ?? Str::title(str_replace(['/', '-', '_'], ' ', $path)),
                            'method' => strtoupper($method),
                            'path' => $path,
                            'description' => $details['description'] ?? 'No description provided'
                        ]);
                        
                        // Associate endpoint with API and save
                        $api->endpoints()->save($endpoint);

                        // Process parameters
                        if (!empty($details['parameters'])) {
                            foreach ($details['parameters'] as $param) {
                                $parameter = new Parameter([
                                    'name' => $param['name'],
                                    'location' => $param['in'],
                                    'type' => $param['schema']['type'] ?? 'string',
                                    'required' => $param['required'] ?? false,
                                    'description' => $param['description'] ?? $this->generateParameterDescription($param['schema']['type'] ?? 'string')
                                ]);
                                
                                // Associate parameter with endpoint and save
                                $endpoint->parameters()->save($parameter);
                            }
                        }

                        // Process request body parameters if they exist
                        if (isset($details['requestBody']['content']['application/json']['schema']['properties'])) {
                            foreach ($details['requestBody']['content']['application/json']['schema']['properties'] as $name => $schema) {
                                $parameter = new Parameter([
                                    'name' => $name,
                                    'location' => 'body',
                                    'type' => $schema['type'] ?? 'string',
                                    'required' => $details['requestBody']['required'] ?? false,
                                    'description' => $schema['description'] ?? $this->generateParameterDescription($schema['type'] ?? 'string')
                                ]);
                                
                                // Associate parameter with endpoint and save
                                $endpoint->parameters()->save($parameter);
                            }
                        }

                        $imported['success'][] = "$method $path";
                    } catch (\Exception $e) {
                        $imported['failed'][] = [
                            'path' => "$method $path",
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to import API: ' . $e->getMessage());
        }

        return $imported;
    }

    /**
     * Generate parameter description based on type
     */
    private function generateParameterDescription(string $type): string
    {
        $descriptions = [
            'string' => 'Text input field for string values',
            'integer' => 'Numeric input field for whole numbers',
            'number' => 'Numeric input field for decimal numbers',
            'boolean' => 'Toggle field for true/false values',
            'array' => 'List of values',
            'object' => 'Complex data structure'
        ];

        return $descriptions[$type] ?? 'Input parameter';
    }
}