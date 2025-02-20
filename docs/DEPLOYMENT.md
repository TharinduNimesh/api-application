# API Management Platform - Deployment Guide

## Prerequisites
- Docker
- Docker Compose
- Git
- Text editor for configuration files

## Deployment Steps

### 1. Environment Configuration
Copy the environment template:
```sh
cp .env.example .env
```

Configure the following essential variables in `.env`:
```ini
# Application Settings
APP_NAME=APIForge
APP_ENV=production
APP_DEBUG=false
APP_URL=your-domain.com

# Database Configuration
DB_CONNECTION=mongodb
DB_DSN=mongodb://admin:password@db:27017/
DB_USERNAME=admin
DB_PASSWORD=your-secure-password
DB_DATABASE=api_db
```

### 2. Docker Configuration
The application uses Docker Compose for containerization with two main services:

#### Application Service (app)
- Built from the Laravel application
- Apache web server with PHP 8.3
- Handles web requests and API processing

#### Database Service (db)
- MongoDB latest version
- Persistent data storage
- Configurable credentials

#### Port Configuration
Default ports in `docker-compose.yml`:
```yaml
ports:
  - "8080:80"    # Application (customize if needed)
  - "27017:27017" # MongoDB
```
To change ports, modify the left number (host port) in the `docker-compose.yml` file.

### 3. Volume Management
The application uses several Docker volumes for data persistence and development:
```yaml
volumes:
  - ./app:/var/www/app                 # Application logic
  - ./config:/var/www/config           # Configuration files
  - ./resources:/var/www/resources     # Frontend assets
  - ./routes:/var/www/routes           # API routes
  - ./storage:/var/www/storage         # Application storage
  - ./tests:/var/www/tests             # Test files
  - ./data:/data/db                    # MongoDB data
```

#### Purpose of Each Volume:
- **app**: Core application code, allows real-time updates during development
- **config**: Application configuration files
- **resources**: Frontend assets and views
- **routes**: API route definitions
- **storage**: Application logs, cache, and uploaded files
- **tests**: Test files for continuous integration
- **data**: MongoDB data persistence

### 4. Deployment Commands
Build and start containers:
```sh
docker compose up --build -d
```

Monitor container logs:
```sh
docker compose logs -f
```

Stop containers:
```sh
docker compose down
```

### 5. Post-Deployment Tasks
Generate application key:
```sh
docker compose exec app php artisan key:generate
```

Clear configuration cache:
```sh
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

Set proper permissions:
```sh
docker compose exec app chown -R laravel:laravel storage bootstrap/cache
```

## Security Considerations
- Update `.env` file permissions:
  ```sh
  chmod 600 .env
  ```
- Ensure MongoDB authentication is enabled
- Use strong passwords for database credentials
- Configure proper firewall rules
- Enable SSL/TLS for production deployments

## Monitoring
Monitor your deployment using:
```sh
# Container status
docker compose ps

# Resource usage
docker stats

# Application logs
docker compose logs -f app

# Database logs
docker compose logs -f db
```

## Troubleshooting
### Container won't start:
- Check ports availability
- Verify environment variables
- Review Docker logs

### Database connection issues:
- Confirm MongoDB credentials
- Check network connectivity
- Verify volume permissions

### Performance issues:
- Monitor resource usage
- Check application logs
- Review MongoDB indexes

## Backup Procedures
### Database backup:
```sh
docker compose exec db mongodump --out /data/backup
```

### Application backup:
```sh
tar -czf backup.tar.gz .env app config storage
```

For detailed maintenance procedures and scaling options, refer to the **Maintenance Guide**.
