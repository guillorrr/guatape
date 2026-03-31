# Guatapé — Laravel 12 + Vue 3 Scaffold

## Fork Detection
Check `APP_NAME` in `.env` to determine if this is the base scaffold or a fork. Never reference "Guatapé" in forked projects — use the actual `APP_NAME`.

## Tech Stack
- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Vue 3 + Vite + TypeScript
- **Database**: MySQL 8.4
- **Cache/Queue**: Redis 7
- **ORM**: Eloquent
- **Auth**: Laravel Sanctum (token-based)
- **Design System**: Storybook 8
- **CSS**: SCSS with Atomic Design
- **Monorepo**: npm workspaces

## Project Structure

```
laravel-vue/
├── src/
│   ├── api/                          # Laravel 12
│   │   ├── app/
│   │   │   ├── Models/               # Eloquent models
│   │   │   ├── Http/Controllers/Api/ # API controllers
│   │   │   ├── Services/             # Business logic
│   │   │   ├── Integrations/         # External APIs (AFIP, MercadoLibre)
│   │   │   ├── DTOs/                 # Data Transfer Objects
│   │   │   ├── Enums/                # PHP enums
│   │   │   ├── Actions/              # Single-action classes
│   │   │   ├── Jobs/                 # Queue jobs
│   │   │   ├── Events/               # Domain events
│   │   │   ├── Listeners/            # Event listeners
│   │   │   ├── Observers/            # Model observers
│   │   │   └── Policies/             # Authorization policies
│   │   ├── database/migrations/      # DB migrations
│   │   ├── routes/api.php            # API routes
│   │   └── tests/                    # PHPUnit + Pest
│   └── frontend/                     # Vue 3 SPA
│       └── src/
│           ├── atoms/                # Basic UI (buttons, inputs)
│           ├── molecules/            # Composed components
│           ├── organisms/            # Complex sections
│           ├── templates/            # Page layouts
│           ├── pages/                # Route-level views
│           ├── core/                 # App infrastructure
│           │   ├── services/         # API service, utilities
│           │   ├── stores/           # Pinia stores
│           │   ├── models/           # TypeScript interfaces
│           │   └── styles/           # SCSS variables, globals
│           ├── composables/          # Vue composables
│           ├── shared/               # Shared directives, pipes
│           └── router/               # Vue Router config
├── docker/                           # Docker configs
├── scripts/                          # Setup & utility scripts
└── docs/                             # Documentation
```

## Architecture Conventions

### Backend (Laravel)
- **Controllers**: Thin, delegate to Services. One resource per controller.
- **Services**: Business logic lives here. One service per domain concept.
- **Integrations**: External API wrappers (AFIP, MercadoLibre). Isolated, testable.
- **Actions**: Single-responsibility classes for complex operations.
- **DTOs**: Typed data transfer between layers. Use `readonly` classes.
- **API routes**: Versioned under `/api/v1/`. Always return JSON.
- **Validation**: Form Request classes, never validate in controllers directly.
- **Queue**: Redis driver. Heavy tasks (API sync, invoicing) go to jobs.

### Frontend (Vue 3)
- **Atomic Design**: atoms → molecules → organisms → templates → pages
- **Composition API only**: No Options API. Use `<script setup lang="ts">`.
- **Pinia stores**: One store per domain. Composition store syntax (`setup()`).
- **Composables**: Reusable logic in `composables/` directory.
- **Storybook**: Every atom and molecule must have a `.stories.ts` file.

## Naming Conventions

### Backend
- **Models**: `PascalCase` singular (`Product`, `StlFile`, `Order`)
- **Controllers**: `PascalCase` + `Controller` (`ProductController`)
- **Services**: `PascalCase` + `Service` (`OrderService`)
- **Migrations**: Laravel default (`create_products_table`)
- **Routes**: `kebab-case` plural (`/api/v1/stl-files`)

### Frontend
- **Components**: `PascalCase` with prefix (`AppButton.vue`, `OrderCard.vue`)
- **Stores**: `camelCase` + `.store.ts` (`order.store.ts`)
- **Services**: `camelCase` + `.service.ts` (`api.service.ts`)
- **Composables**: `use` prefix (`useOrders.ts`)

## Key Commands

```bash
# Development
npm start                   # Start core services (api, frontend, nginx, db, redis)
npm run start:all           # Start all services including tools
npm run dev                 # Start with attached logs

# Laravel
npm run artisan             # Run artisan commands
npm run composer            # Run composer commands
npm run tinker              # Laravel Tinker REPL
npm run migrate             # Run migrations
npm run migrate:fresh       # Fresh migrate + seed
npm run seed                # Run seeders
npm run test:api            # Run API tests
npm run queue:work          # Process queue jobs

# Frontend
npm run storybook           # Start Storybook
npm run lint                # Lint frontend
npm run format              # Format frontend
npm run test:frontend       # Run frontend tests

# Infrastructure
npm run setup               # Full project setup
npm run setup:certs         # Generate SSL certs
npm run docker:rebuild      # Rebuild containers
npm run docker:clean        # Remove containers + volumes
npm run backup:db           # Backup database
```

## Docker Services

| Service    | Image          | Port(s)    | Profile   |
|------------|----------------|------------|-----------|
| db         | MySQL 8.4      | 3306       | default   |
| api        | PHP 8.4-FPM    | 9000       | default   |
| frontend   | Node 22        | 5173       | default   |
| nginx      | Nginx Alpine   | 80, 443    | default   |
| redis      | Redis 7        | 6379       | default   |
| storybook  | Node 22        | 6006       | storybook |
| phpmyadmin | phpMyAdmin     | 8082       | tools     |
| mailhog    | Mailhog        | 1025, 8025 | tools     |
| mkcert     | docker-mkcert  | —          | setup     |

## Adding a New Module (Backend)

1. Create Model: `php artisan make:model ModuleName -m` (with migration)
2. Create Controller: `app/Http/Controllers/Api/ModuleNameController.php`
3. Create Service: `app/Services/ModuleNameService.php`
4. Create Form Requests: `app/Http/Requests/ModuleName/`
5. Create Resource: `php artisan make:resource ModuleNameResource`
6. Add routes to `routes/api.php`
7. Create Policy if needed: `php artisan make:policy ModuleNamePolicy`

## Adding a New Page (Frontend)

1. Create page component in `src/pages/NewPage.vue`
2. Add route in `src/router/index.ts`
3. Create any needed atoms/molecules/organisms
4. Create Pinia store if the page manages state
5. Add stories for new components

## Code Style
- PHP: PSR-12 (Laravel Pint)
- TypeScript/Vue: ESLint + Prettier (see `.prettierrc`)
- Commits: Conventional Commits (`feat:`, `fix:`, `docs:`, etc.)
- Husky + commitlint enforced
