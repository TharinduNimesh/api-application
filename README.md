# API Management Platform

A robust API management solution built with Laravel and Vue.js, offering comprehensive API lifecycle management, rate limiting, and multi-tier access control.

## 📸 Application Screenshots

<div align="center">
  <img src="https://raw.githubusercontent.com/TharinduNimesh/api-application/refs/heads/main/docs/images/dashboard.png" alt="Dashboard" width="800"/>
  <p><em>Dashboard - API Management Overview</em></p>
  
  <img src="https://raw.githubusercontent.com/TharinduNimesh/api-application/refs/heads/main/docs/images/stats.png" alt="API Creation" width="800"/>
  <p><em>API Info & Usage Analytics</em></p>
  
  <img src="https://raw.githubusercontent.com/TharinduNimesh/api-application/refs/heads/main/docs/images/usage.png" alt="Analytics" width="800"/>
  <p><em>API Response Preview</em></p>
</div>

## 🚀 Features

- API Creation and Management
- Role-based Access Control (Admin, Paid, Free users)
- Rate Limiting and Usage Tracking
- API Key Management
- Real-time Monitoring
- Comprehensive Documentation

## 📚 Documentation

- [Project Overview](docs/OVERVIEW.md)
- [Features Documentation](docs/FEATURES.md)
- [Deployment Guide](docs/DEPLOYMENT.md)

## 🛠️ Tech Stack

- Backend: Laravel (PHP Framework)
- Frontend: Vue.js with PrimeVue
- Database: MongoDB
- Authentication: Laravel Sanctum
- Rate Limiting: Laravel built-in rate limiter

## 🔧 Requirements

- PHP >= 8.2
- Node.js >= 20
- MongoDB >= 8.0
- Composer
- npm or yarn

## 🔑 Quick Start

```bash
# Clone the repository
git clone https://github.com/TharinduNimesh/api-application.git

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start the development server
php artisan serve
npm run dev
```

## ⚙️ Environment Setup Reminders

1. Configure MongoDB connection in `.env`:
   ```
   MONGODB_URI=mongodb://localhost:27017
   MONGODB_DATABASE=api_management
   ```

2. Configure mail settings for notifications:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=your-smtp-host
   MAIL_PORT=587
   MAIL_USERNAME=your-username
   MAIL_PASSWORD=your-password
   ```

## ⚠️ Important Notice

This is a proprietary client project. All rights reserved. Unauthorized copying, modification, distribution, or use of this software is strictly prohibited. The codebase and its documentation are confidential and intended solely for authorized users.

© 2025 **Eversoft**. All Rights Reserved.