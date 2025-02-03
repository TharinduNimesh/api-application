# API Management Application Guidelines

## Project Overview
This is an API management platform where administrators can create and manage APIs with rate limiting capabilities. Users can consume APIs based on their subscription plans.

## Technical Stack
- Backend: Laravel (PHP Framework)
- Frontend: Vue.js with PrimeVue components
- Database: MongoDB
- Authentication: Laravel Sanctum
- Rate Limiting: Laravel built-in rate limiter

## User Roles
1. ADMIN
   - Can create/edit/delete APIs
   - Can manage rate limits
   - Can access all APIs (FREE & PAID)
   - No usage limitations
   
2. PAID USER
   - Cannot create/edit/delete APIs
   - Can access both FREE & PAID APIs
   - No usage limitations
   
3. FREE USER
   - Cannot create/edit/delete APIs
   - Can only access FREE APIs
   - Subject to rate limitations

## API Types
1. FREE APIs
   - Accessible to all users
   - Rate limited for FREE users
   
2. PAID APIs
   - Only accessible to PAID users and ADMINs
   - No rate limits for authorized users

## Database Structure
Collections:
- users
  - name
  - email
  - password
  - role (ADMIN|PAID|FREE)
  - api_key
  - created_at
  - updated_at

- apis
  - name
  - endpoint
  - type (FREE|PAID)
  - rate_limit
  - created_by
  - created_at
  - updated_at

- api_usage
  - user_id
  - api_id 
  - usage_count
  - last_used_at

## Coding Standards
1. Backend (Laravel)
   - Use Repository Pattern
   - Implement Service Layer
   - Follow PSR-12 coding standard
   - Use Laravel Gates for authorization
   - Implement proper rate limiting middleware

2. Frontend (Vue.js)
   - Use Composition API
   - Implement TypeScript
   - Follow Vue.js Style Guide
   - Use PrimeVue components for UI
   - Implement proper state management

## Security Guidelines
1. Authentication
   - Use Laravel Sanctum for API authentication
   - Implement JWT tokens
   - Store API keys securely

2. Authorization
   - Implement role-based access control
   - Use Laravel Gates and Policies
   - Validate all requests

3. Rate Limiting
   - Implement per-user rate limiting
   - Track API usage in real-time
   - Log all API requests

## Testing Requirements
- Unit tests for all services
- Feature tests for API endpoints
- Frontend component testing
- E2E testing for critical flows

## Git Workflow
- Use feature branches
- Follow conventional commits
- Require PR reviews
- Run tests before merge

## Monitoring
- Log all API usage
- Track rate limit violations
- Monitor system performance
- Alert on system failures