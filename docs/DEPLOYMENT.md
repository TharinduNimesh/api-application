# API Management Platform - Deployment Guide

## Prerequisites
- Docker & Docker Compose
- Git
- Text editor for configuration

## Deployment Steps

### 1. Clone Repository
1. Clone the application repository:
```sh
git clone https://github.com/TharinduNimesh/api-application.git
cd api-application
```

### 2. Environment Configuration
1. Copy the environment template:
```sh
cp .env.example .env
```

2. Configure the essential variables in `.env`:

```ini
APP_NAME=APIForge
APP_KEY=
APP_TIMEZONE=UTC
APP_URL=http://your-domain.com    # Production URL
APP_PORT=8080                     # Production port

# Database Configuration
DB_CONNECTION=mongodb
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
DB_DATABASE=apiforge_db
DB_PORT=27017
DB_DSN=mongodb://${DB_USERNAME}:${DB_PASSWORD}@mongodb:${DB_PORT}/${DB_DATABASE}?authSource=admin

# Mail Configuration
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=apiforge@your-domain.com
MAIL_FROM_NAME=APIForge
RESEND_API_KEY=your-resend-api-key
```

3. Environment Variable Guidelines:
   - **APP Settings**:
     - `APP_NAME`: Change only if you want to rebrand the application
     - `APP_KEY`: Leave empty, it will be auto-generated during deployment
     - `APP_PORT`: Change if port 3000 is already in use
     - `APP_URL`: Update to match your production domain in production

   - **Database Settings**:
     - `DB_USERNAME` and `DB_PASSWORD`: Set strong credentials
     - `DB_PORT`: Change only if port 27017 conflicts with existing MongoDB
     - Other DB settings should remain as configured

   - **Mail Settings**:
     - Create a Resend.com account
     - Set up and verify your custom domain
     - Update `MAIL_FROM_ADDRESS` with your verified domain
     - Copy `RESEND_API_KEY` from Resend dashboard

   - **Production Considerations**:
     - Always use HTTPS in production (update APP_URL accordingly)
     - Use strong, unique passwords for database
     - Keep API keys secure and never commit them to version control

### 3. Docker Configuration
The application uses Docker Compose with two main services:

#### Application Service (app)
- PHP 8.3 with Apache
- Optimized for production
- Built using multi-stage build
- Includes Node.js for frontend asset compilation

#### Database Service (mongodb)
- MongoDB latest version
- Persistent data storage
- Secured with authentication

### 4. Deployment Commands

1. Build and start production containers:
```sh
docker compose -f docker-compose.prod.yml up --build -d
```

2. Monitor deployment:
```sh
# View container status
docker compose -f docker-compose.prod.yml ps

# Check logs
docker compose -f docker-compose.prod.yml logs -f
```

3. Stop deployment:
```sh
docker compose -f docker-compose.prod.yml down
```

### 5. Volume Management
The production setup includes the following volumes:

```yaml
volumes:
  - ./storage/logs:/var/www/html/storage/logs     # Application logs
  - mongodb_data:/data/db                         # MongoDB data
  - ./docker/mongo/init-mongo.js:/docker-entrypoint-initdb.d/init-mongo.js:ro
```

### 6. Security Considerations (Optional)
1. Environment Security:
   - Set strong MongoDB credentials
   - Update APP_KEY using Laravel's key generation
   - Enable HTTPS in production
   - Secure the .env file:
   ```sh
   chmod 600 .env
   ```

2. Docker Security:
   - Use non-root user (www-data)
   - Implement resource limits
   - Regular security updates
   - Proper network isolation

### 7. Health Checks (Optional)
Monitor your deployment health:

1. Application Status:
```sh
curl http://your-domain.com/up
```

2. Container Health:
```sh
docker compose -f docker-compose.prod.yml ps
```

3. Log Monitoring:
```sh
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f mongodb
```

## Troubleshooting

### Common Issues

1. Container Startup Issues:
   - Verify port availability (APP_PORT and DB_PORT)
   - Check environment variables in .env
   - Review Docker logs for errors

2. Database Connection Issues:
   - Verify MongoDB credentials
   - Check if MongoDB container is running
   - Ensure network connectivity between containers

3. Permission Issues:
   - Verify storage directory permissions
   - Check log file permissions
   - Ensure proper container user permissions

## Backup Procedures

### Database Backup
```sh
docker compose -f docker-compose.prod.yml exec mongodb mongodump --out /data/backup
```

### Application Backup
```sh
tar -czf backup.tar.gz .env storage docker
```

---
Last Updated: March 17, 2025