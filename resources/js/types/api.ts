import { z } from 'zod';

export type ApiType = 'FREE' | 'PAID';
export type ApiStatus = 'ACTIVE' | 'INACTIVE';
export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH';
export type ParameterLocation = 'query' | 'path' | 'body' | 'header';
export type ParameterType = 'string' | 'number' | 'boolean' | 'object' | 'array';

export interface Parameter {
  id?: string;
  name: string;
  type: ParameterType;
  location: ParameterLocation;
  required: boolean;
  description: string;
  defaultValue?: string;
}

export interface Endpoint {
  id?: string;
  method: HttpMethod;
  name: string;
  path: string;
  description: string;
  parameters: Parameter[];
}

export interface User {
  name: string;
  email: string;
}

export interface Api {
  id: string;
  name: string;
  description: string;
  type: ApiType;
  status: ApiStatus;
  baseUrl: string;
  rateLimit: number;
  createdAt: string;
  endpoints: Endpoint[];
  endpointCount: number;
  createdBy?: User;
}

export type CreateApi = Omit<Api, 'id' | 'status' | 'createdAt' | 'createdBy' | 'endpointCount'>;

// Zod schema for validation
export const CreateApiSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    description: z.string().min(1, 'Description is required'),
    type: z.enum(['FREE', 'PAID']),
    baseUrl: z.string().url('Must be a valid URL'),
    rateLimit: z.number().min(1).max(1000),
    endpoints: z.array(z.object({
        id: z.string().optional(),
        name: z.string().min(1),
        method: z.enum(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
        path: z.string().min(1),
        description: z.string(),
        parameters: z.array(z.object({
            id: z.string().optional(),
            name: z.string().min(1),
            type: z.enum(['string', 'number', 'boolean', 'object', 'array']),
            location: z.enum(['query', 'path', 'body', 'header']),
            required: z.boolean(),
            description: z.string(),
            defaultValue: z.string().optional()
        }))
    }))
});
