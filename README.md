# Nepal Public Procurement Management System (NPPMS)

A comprehensive full-stack web application digitizing public procurement processes for Provincial and Local governments of Nepal according to Nepali public procurement laws.

## System Architecture

### Tech Stack
- **Backend**: Laravel 11 (PHP 8.2) with API Resources
- **Frontend**: Next.js 14 with TypeScript and App Router
- **Database**: PostgreSQL 15
- **Cache & Queue**: Redis 7
- **Search**: Meilisearch v1.5
- **Object Storage**: MinIO (S3 compatible)
- **Containerization**: Docker & Docker Compose

### Core Modules
1. Federal Structure (Province, District, Local Body, Ward)
2. Budget Planning & Cost Estimates
3. Procurement Plans & Methods
4. Bidding Process Management
5. Contract Management
6. Work Execution & Monitoring
7. Billing & Payment Processing
8. Time Extension Management
9. Completion & Handover
10. Consumer Committee Management
11. Timeline Rules & Validation
12. Document Templates
13. Blacklist Management
14. User & Role Management

## Prerequisites

- Docker & Docker Compose
- Node.js 18+ (for local development)
- PHP 8.2+ (for local development)
- Composer (for local development)

## Quick Start with Docker

1. **Clone and navigate to the project directory**
   ```bash
   cd nppms
   ```

2. **Start all services**
   ```bash
   docker-compose up -d
   ```

3. **Access the applications**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000
   - PostgreSQL: localhost:5432
   - Redis: localhost:6379
   - Meilisearch: http://localhost:7700
   - MinIO Console: http://localhost:9001
   - MailHog: http://localhost:8025

4. **Initialize the database** (if not auto-initialized)
   ```bash
   docker-compose exec backend php artisan migrate --seed
   ```

## Manual Setup (Without Docker)

### Backend Setup
1. Navigate to backend directory
   ```bash
   cd backend
   ```

2. Install PHP dependencies
   ```bash
   composer install
   ```

3. Configure environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Update `.env` with database credentials
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=nppms
   DB_USERNAME=nppms_user
   DB_PASSWORD=nppms_password
   ```

5. Run migrations and seeders
   ```bash
   php artisan migrate --seed
   ```

6. Start development server
   ```bash
   php artisan serve
   ```

### Frontend Setup
1. Navigate to frontend directory
   ```bash
   cd frontend
   ```

2. Install dependencies
   ```bash
   npm install
   ```

3. Start development server
   ```bash
   npm run dev
   ```

## Key Features Implemented

### 1. Nepali Date Handling
- BS to AD date conversion service
- Nepali numeral conversion
- Date formatting for Nepali calendar

### 2. Timeline Validation
- Validates procurement process timelines against legal rules
- Checks minimum/maximum days between steps
- Throws TimelineViolationException with Nepali error messages

### 3. Process Flow Management
- Manages procurement process workflows
- Handles process instances, steps, and transitions
- Tracks progress and deadlines

### 4. Document Generation
- Generates procurement documents (bid notices, contracts, certificates)
- Supports PDF and DOCX formats
- Uses template rendering with data substitution

### 5. Notification System
- Handles notifications via multiple channels (web, email, SMS, push)
- Supports bulk notifications and user-specific notifications
- Includes default templates for common notification types

### 6. Role-Based Access Control (RBAC)
- Multi-level permissions system
- Role and permission management
- User role assignment

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Refresh token

### Projects
- `GET /api/projects` - List all projects
- `POST /api/projects` - Create new project
- `GET /api/projects/{id}` - Get project details
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project

### Procurement
- `GET /api/procurement-plans` - List procurement plans
- `POST /api/procurement-plans` - Create procurement plan
- `GET /api/tenders` - List tender notices
- `POST /api/tenders` - Create tender notice

## Database Schema

The system includes 14 modules with comprehensive relationships:

- **Core Entities**: Province, District, LocalBody, Ward, FiscalYear
- **User Management**: User, Role, Permission, UserRole, RolePermission
- **Procurement**: Project, ProcurementPlan, CostEstimate, TenderNotice
- **Contract Management**: Contract, WorkExecution, Bill, Payment
- **Monitoring**: TimelineRule, DocumentTemplate, BlacklistEntry

See `database_schema.sql` for complete schema definition.

## Development

### Running Tests
```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd frontend
npm test
```

### Code Style
```bash
# Backend
php artisan pint

# Frontend
npm run lint
```

## Deployment

### Production Docker Setup
1. Update environment variables in `.env.production`
2. Build and deploy:
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

### Environment Variables
Key environment variables for production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- Database credentials
- Redis configuration
- MinIO/S3 credentials
- Mail configuration

## Project Structure

```
nppms/
├── backend/                 # Laravel backend
│   ├── app/
│   │   ├── Models/         # Eloquent models
│   │   ├── Services/       # Business logic services
│   │   ├── Http/           # Controllers & Middleware
│   │   └── ...
│   ├── database/           # Migrations & Seeders
│   └── ...
├── frontend/               # Next.js frontend
│   ├── app/                # App Router pages
│   ├── components/         # React components
│   │   ├── dashboard/      # Dashboard components
│   │   ├── layout/         # Layout components
│   │   └── ui/             # UI components
│   └── ...
├── database_schema.sql     # Complete database schema
├── docker-compose.yml      # Docker Compose configuration
└── README.md              # This file
```

## License

This project is developed for the Government of Nepal and follows public procurement regulations.

## Support

For technical support or issues, please contact:
- Email: support@nppms.gov.np
- Ministry of Federal Affairs and General Administration
- Singha Durbar, Kathmandu, Nepal