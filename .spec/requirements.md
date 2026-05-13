# Requirements

## Day 10: Laravel Initial Setup And Migration

### Purpose
- Start Phase 2 implementation with Pattern 1: Laravel standard MVC.
- Prepare a runnable Laravel 10 application under `pattern1-mvc/`.
- Add the first inventory-management database schema used as the baseline for later Onion and Clean comparisons.

### Scope
- Scaffold Laravel 10 in `pattern1-mvc/`.
- Keep Pattern 1 intentionally close to Laravel defaults.
- Add inventory-related migrations for products and stock movements.
- Update Pattern 1 documentation with setup and schema intent.

### Out Of Scope
- MVC business logic implementation.
- Vue screen implementation.
- Onion and Clean implementation.
- Production deployment settings.

### Acceptance Criteria
- `pattern1-mvc/` contains a Laravel 10 application.
- `composer install` can resolve dependencies for Pattern 1.
- Inventory schema exists for products and stock movements.
- Laravel migration status or tests can run successfully in the local environment.
- Documentation explains that Pattern 1 is the baseline MVC implementation.

## Day 11: Pattern 1 MVC Stock Logic

### Purpose
- Implement the first inventory-management behavior in the MVC baseline.
- Deliberately place stock calculation, permission checks, validation, persistence, and HTTP response handling close to Eloquent Model and Controller so later Onion and Clean implementations can be compared against it.

### Scope
- Add Eloquent models for products and stock movements.
- Add product creation, product listing, and stock update API endpoints.
- Support stock increase, stock decrease, and direct adjustment.
- Add a simple operator role check: only managers can directly adjust stock.
- Add focused feature tests for the MVC behavior.

### Out Of Scope
- Repository interfaces, UseCases, Domain Entities, Value Objects, or Domain Services.
- Vue screen implementation.
- Authentication scaffolding.
- Onion and Clean implementations.

### Acceptance Criteria
- Product creation records an initial adjustment movement when initial stock is provided.
- Stock increase and decrease update `products.stock_quantity` and create `stock_movements` records.
- Decrease cannot make stock negative.
- Direct adjustment is rejected for non-manager operators.
- The implementation remains Laravel standard MVC.

## Day 12: Pattern 1 Vue Inline Screen

### Purpose
- Add a minimal browser-facing inventory screen for the MVC baseline.
- Keep API calls, form state, table state, and error handling in one Vue component to show how Pattern 1 can become frontend-fat before composables are introduced.

### Scope
- Install Vue and Vite Vue plugin.
- Replace the Laravel welcome page with a Vite-mounted inventory screen.
- Add product creation, product listing, and stock update controls.
- Keep the implementation intentionally simple and local to `resources/js/components/InventoryApp.vue`.

### Out Of Scope
- Vue Composables.
- TypeScript strict models.
- Frontend routing.
- Polished production UI.
- Onion and Clean frontend implementations.

### Acceptance Criteria
- `npm run build` can compile the Vue screen.
- The root page renders the inventory screen through Vite.
- The screen can call the existing product and stock APIs.
- Documentation notes the Day 12 MVC frontend tradeoff.

## Day 13: Pattern 1 Retrospective

### Purpose
- Record what became easy and what became harder after implementing the same inventory concept with Laravel standard MVC.
- Make the later Onion and Clean implementations easier to compare against the MVC baseline.

### Scope
- Summarize Pattern 1 responsibility placement across Model, Controller, and Vue component.
- Capture the main changeability and testability concerns observed from the implementation.
- Update progress documentation so the next phase can start from Pattern 2: Onion + DDD.

### Out Of Scope
- Refactoring Pattern 1 into Repository, UseCase, Domain Entity, Value Object, or Composable layers.
- Changing existing API behavior.
- Starting Pattern 2 implementation.

### Acceptance Criteria
- The learning log explains why the MVC implementation was fast to build.
- The learning log identifies concrete friction points for future changes.
- The notes explicitly connect the MVC pain points to the responsibilities planned for Onion and Clean.
- The progress log marks Day 13 as complete.

## Day 14: Pattern 2 Domain Layer Design

### Purpose
- Start Pattern 2 by defining the Onion Architecture core before Laravel, Eloquent, Controller, or Repository implementation.
- Move inventory invariants from MVC-style Controller / Eloquent placement into explicit Domain Entity and Value Object classes.

### Scope
- Add a pure PHP Domain layer under `pattern2-onion/app/Domain/`.
- Define `Product` as the aggregate root for stock operations.
- Define Value Objects for product identity, SKU, product name, stock quantity, movement quantity, and price.
- Define a stock movement type enum and a domain exception.
- Document how this differs from Pattern 1 MVC.

### Out Of Scope
- Laravel app scaffolding for Pattern 2.
- Eloquent models, migrations, infrastructure repositories, or DI bindings.
- Application Service / Domain Service implementation.
- HTTP API or Vue screen.

### Acceptance Criteria
- Domain classes do not depend on Laravel framework classes or Eloquent.
- Value Objects validate their own invariants.
- `Product` can increase, decrease, and directly adjust stock through domain methods.
- Decreasing stock below zero is rejected by the Domain layer.
- README documents the Domain layer responsibility and next steps.

## Day 15: Pattern 2 Domain Service And Repository Interface

### Purpose
- Add the next Onion layer contract around the Domain core without introducing Infrastructure.
- Move the direct stock adjustment permission rule out of the MVC Controller style and into an explicit Domain Service.
- Define the Repository Interface that Application / Infrastructure will use later through DIP.

### Scope
- Add a domain-level operator role concept.
- Add a Domain Service for stock adjustment permission.
- Add `ProductRepositoryInterface` under the Domain layer.
- Document why these are still inside the Onion core and how Infrastructure will implement them later.

### Out Of Scope
- Eloquent repository implementation.
- Laravel service provider binding.
- Application Service orchestration.
- Controller, API, Vue, migration, or database changes.

### Acceptance Criteria
- Direct stock adjustment permission can be checked without Laravel Controller or Request classes.
- Repository Interface depends only on Domain Entity and Value Object classes.
- Domain layer still has no Laravel / Eloquent dependency.
- README explains the difference between Domain Service and Repository Interface in Pattern 2.
