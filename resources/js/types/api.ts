import { z } from 'zod';

export const ParameterLocation = z.enum(['query', 'path', 'body', 'header']);
export type ParameterLocation = z.infer<typeof ParameterLocation>;

export const ApiParameterSchema = z.object({
    name: z.string().min(1, 'Parameter name is required'),
    type: z.enum(['string', 'number', 'boolean', 'object', 'array']),
    location: ParameterLocation,
    required: z.boolean(),
    description: z.string(),
    defaultValue: z.string().optional()
});

export const ApiEndpointSchema = z.object({
    id: z.string().optional(),
    name: z.string().min(1, 'Endpoint name is required'),
    method: z.enum(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
    path: z.string().min(1, 'Path is required'),
    description: z.string().min(1, 'Description is required'),
    parameters: z.array(ApiParameterSchema),
    isEditing: z.boolean().optional(),
    isSaved: z.boolean().optional()
});

export const CreateApiSchema = z.object({
    name: z.string().min(3, 'API name must be at least 3 characters'),
    description: z.string().min(10, 'Please provide a detailed description'),
    type: z.enum(['FREE', 'PAID']),
    baseUrl: z.string().url('Please enter a valid URL'),
    rateLimit: z.number().min(1, 'Rate limit must be at least 1'),
    endpoints: z.array(ApiEndpointSchema).min(1, 'At least one endpoint is required')
});

export type ApiEndpoint = z.infer<typeof ApiEndpointSchema>;
export type CreateApi = z.infer<typeof CreateApiSchema>;
