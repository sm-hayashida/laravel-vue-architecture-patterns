# Project Analysis: laravel-vue-architecture-patterns

## Overview
This project is a learning repository for comparing various architectural patterns (MVC, Onion, Clean) in a Laravel and Vue.js context, focusing on DDD tactical design.

## Directory Structure
- `docs/`: Learning logs and architecture documentation.
- `pattern1-mvc/`: Implementation using Laravel's standard MVC pattern.
- `pattern2-onion/`: Implementation using Onion Architecture + DDD.
- `pattern3-clean/`: Implementation using Clean Architecture + DDD.

## Frameworks & Versions
- PHP 8.x
- Laravel 10.x
- Node.js
- Vue.js 3
- TypeScript
- Docker (Laravel Sail)

## Architecture Patterns
1. **Laravel Standard MVC**: High coupling, logic in Models/Controllers. Used as a baseline.
2. **Onion Architecture + DDD**: Domain-centric. Layers: Domain (Core), Application, Infrastructure. Uses DIP to protect the domain.
3. **Clean Architecture + DDD**: Use-case-centric. Layers: Entities, Use Cases, Interface Adapters, Frameworks & Drivers. Strict boundary separation.

## Naming Conventions (Global)
- Variables / functions: camelCase
- Classes: PascalCase
- Constants: UPPER_SNAKE_CASE
- DB columns / API params: snake_case
- Filenames (PHP): PascalCase
- Filenames (TS/React): PascalCase (for components), camelCase (for hooks/utils)
- Filenames (Dart): N/A (Project is PHP/Vue)

## Tech Stack & Approach
- **Backend**: Laravel 10.x. Uses Repositories, Use Cases (Interactors), and DTOs in advanced patterns.
- **Frontend**: Vue 3 (Composition API) with TypeScript. Uses Vue Composables to separate logic from UI.
- **Testing**: PHPUnit for backend, Vitest/Jest for frontend (to be confirmed).
- **Environment**: Laravel Sail (Docker).

## Special Caveats
- The primary goal is internal promotion evaluation.
- Focus on "What are the differences?" between Onion and Clean.
- Avoid over-abstraction (KISS/YAGNI).
