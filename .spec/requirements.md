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

## Day 16: Pattern 2 Application Service

### Purpose
- Add the Application layer for the Onion implementation.
- Orchestrate product creation and stock operations without putting business rules back into Controller or Infrastructure.
- Show how Pattern 2 differs from Pattern 1 by making the Application Service coordinate Domain objects instead of owning stock rules itself.

### Scope
- Add an Application Service under `pattern2-onion/app/Application/Services/`.
- Support product creation, stock increase, stock decrease, and direct stock adjustment.
- Use `ProductRepositoryInterface` for persistence access.
- Use `StockAdjustmentPolicy` for direct adjustment permission.
- Add a small Application exception for application-level failures such as missing products or duplicate SKU.
- Document the Day 16 responsibility placement.

### Out Of Scope
- Eloquent repository implementation.
- Laravel service provider binding.
- HTTP Controller, API routes, FormRequest, Resource, or Presenter.
- Vue screen or composables.
- Database transaction handling.

### Acceptance Criteria
- Application Service depends on Domain interfaces and Domain services, not Infrastructure implementations.
- Stock increase / decrease / adjustment delegate stock calculation to `Product`.
- Direct adjustment delegates permission checking to `StockAdjustmentPolicy`.
- Missing product and duplicate SKU are explicit application-level failures.
- Domain layer remains free of Laravel / Eloquent dependencies.

## Day 17: Pattern 2 Infrastructure Eloquent Repository

### Purpose
- Add the Infrastructure layer implementation for the Onion repository contract.
- Show DIP in code by making an outer Eloquent repository implement the Domain repository interface.
- Keep Domain and Application independent from Eloquent while allowing Laravel persistence to be connected later.

### Scope
- Add Eloquent record models under `pattern2-onion/app/Infrastructure/`.
- Add `EloquentProductRepository` implementing `ProductRepositoryInterface`.
- Map primitive database values to Domain Entity / Value Object classes.
- Convert `Money` cents to the existing decimal price shape without float arithmetic.
- Document Infrastructure responsibility and its difference from Pattern 1 MVC.

### Out Of Scope
- Laravel app scaffolding for Pattern 2.
- Service provider binding.
- Controller, API routes, FormRequest, Resource, or Presenter.
- Vue screen or composables.
- Stock movement audit writing. The current repository contract saves Product state only and does not carry movement type or reason.

### Acceptance Criteria
- `EloquentProductRepository` implements `ProductRepositoryInterface`.
- Infrastructure depends inward on Domain classes.
- Domain and Application do not depend on Infrastructure.
- Product database rows can be mapped to and from the Domain `Product` aggregate.
- Price mapping avoids float conversion.

## Day 18a: Pattern 2 Controller / DI / API Connection

### Purpose
- Connect the Onion backend layers before introducing Vue Composables.
- Make the API contract concrete so the frontend can call a stable Pattern 2 endpoint shape.
- Demonstrate that the Controller is thinner than Pattern 1 because it delegates application flow and business rules.

### Scope
- Add a Laravel API Controller for Pattern 2 products.
- Add API routes for product listing, product creation, and stock update.
- Bind `ProductRepositoryInterface` to `EloquentProductRepository` through a service provider.
- Add list support to the repository contract and Application Service for frontend use.
- Keep the Controller responsible for HTTP validation, primitive-to-Value-Object conversion, and JSON response formatting.

### Out Of Scope
- Vue Composable implementation.
- Full Laravel scaffold for Pattern 2.
- FormRequest, Resource, Presenter, authentication, or authorization middleware.
- Stock movement audit writing.
- Transaction and row-locking behavior.

### Acceptance Criteria
- `ProductRepositoryInterface` resolves to `EloquentProductRepository` through DI.
- Controller does not call Eloquent records directly.
- Controller delegates product creation and stock updates to `ProductInventoryService`.
- API response shape for products is explicit and frontend-ready.
- Domain and Application remain independent from Laravel HTTP and Infrastructure classes.

## Day 18b: Pattern 2 Vue Composable Separation

### Purpose
- Implement the Pattern 2 frontend comparison point from Day 18.
- Move API calls, state management, form state, and error handling out of the Vue component.
- Make the UI component thinner than Pattern 1 while keeping the same inventory workflow.

### Scope
- Add TypeScript product API types.
- Add a product API module for HTTP calls.
- Add `useInventoryProducts` composable for state and workflow logic.
- Add a Vue inventory component that consumes the composable.
- Add a Pattern 2 Vue entrypoint.
- Document the frontend responsibility split.

### Out Of Scope
- Full Pattern 2 Laravel frontend scaffold and Blade mount point.
- Installing npm dependencies.
- Frontend build verification.
- Styling parity with Pattern 1.
- Authentication, routing, or global state management.

### Acceptance Criteria
- Vue component does not import axios directly.
- API request / response types are explicit.
- Product listing, product creation, and stock update flows are exposed by the composable.
- The component mostly handles rendering and user events.
- Documentation explains the contrast with Pattern 1's inline Vue implementation.

## Day 19: Pattern 2 Unit Tests And Pattern 1 Comparison

### Purpose
- Verify that Pattern 2's Domain and Application layers can be tested without HTTP, Eloquent, or database setup.
- Make the testability difference from Pattern 1's MVC Feature Test coverage explicit.

### Scope
- Add lightweight PHPUnit configuration for `pattern2-onion/`.
- Add Domain unit tests for stock calculation, negative-stock rejection, Value Object validation, and direct-adjustment permission.
- Add Application Service unit tests using an in-memory `ProductRepositoryInterface` test double.
- Document how Pattern 2's unit tests differ from Pattern 1's HTTP/DB-oriented tests.

### Out Of Scope
- Full Laravel scaffold for Pattern 2.
- Database-backed Infrastructure or Controller Feature Tests.
- Frontend component/composable tests.
- Changing production behavior in Domain, Application, Infrastructure, Controller, or Vue code.

### Acceptance Criteria
- Domain rules can be tested by instantiating Entity, Value Object, and Domain Service classes directly.
- Application Service can be tested by swapping `ProductRepositoryInterface` with an in-memory repository.
- Duplicate SKU, missing product, stock increase/decrease, manager adjustment, and staff adjustment failure are covered.
- Documentation explains why Pattern 2 has more files but narrower and faster test targets than Pattern 1.

## Day 20: Pattern 3 UseCase Interactors

### Purpose
- Start Pattern 3 Clean Architecture by expressing inventory behavior as explicit use cases.
- Show the first structural difference from Pattern 2: one Application Service is replaced by operation-specific Interactors.
- Keep the implementation framework-independent before adding Input / Output Ports, Controller, or Presenter.

### Scope
- Add minimal Clean Architecture Entities and Value Objects under `pattern3-clean/app/Entities/`.
- Add use-case input data classes for product creation and stock operations.
- Add Interactors for product creation, stock increase, stock decrease, and direct stock adjustment.
- Add a product repository gateway interface required by the Interactors.
- Document how the Day 20 UseCase layer differs from Pattern 2 Application Service.

### Out Of Scope
- InputPort / OutputPort interfaces.
- Presenter, Controller, Laravel service provider binding, routes, or API response shape.
- Infrastructure repository implementation.
- Vue frontend or TypeScript contracts.
- PHPUnit setup or automated unit tests for Pattern 3.

### Acceptance Criteria
- Each inventory operation has a dedicated Interactor class.
- Interactors depend on Entities, Value Objects, and repository gateway abstractions only.
- Stock calculation remains inside `Product`, not inside Interactors.
- Direct stock adjustment permission is checked before loading and saving product state.
- Pattern 3 code does not depend on Laravel, Eloquent, HTTP, or Pattern 2 namespaces.

## Day 21: Pattern 3 Input / Output Ports

### Purpose
- Make the Clean Architecture use-case boundary explicit.
- Separate how controllers call use cases from how presenters receive use-case output.
- Show the difference from Pattern 2 where controllers call an Application Service and format returned Product values directly.

### Scope
- Add Input Port interfaces for product creation, stock increase, stock decrease, and direct stock adjustment.
- Add a Product output data class for presenter-facing use-case results.
- Add an Output Port interface for presenting product results.
- Update Interactors to implement their Input Port and send output through the Output Port.
- Document how Input / Output Ports prepare for Controller / Presenter implementation in Day 22.

### Out Of Scope
- Concrete Controller implementation.
- Concrete Presenter implementation.
- Laravel service provider binding, routes, or HTTP response shape.
- Infrastructure repository implementation.
- Frontend changes.

### Acceptance Criteria
- Controllers can depend on Input Port interfaces instead of concrete Interactors.
- Interactors do not return `Product` directly to outer layers.
- Presenter-facing output uses a simple data structure instead of exposing Entity objects.
- Output shaping is delegated through Output Port, while Entity rules remain inside Entity classes.
- Pattern 3 code remains independent from Laravel, Eloquent, HTTP, and Pattern 2 namespaces.

## Day 22: Pattern 3 Controller / Presenter

### Purpose
- Add the first outer Interface Adapter layer for the Clean Architecture implementation.
- Make the Day 21 input and output ports concrete by showing how HTTP input reaches UseCases and how UseCase output becomes an API response.
- Contrast Pattern 3 with Pattern 2, where the Controller directly formatted returned Domain objects.

### Scope
- Add a Product HTTP Controller for product creation and stock updates.
- Add a Product Presenter that implements `ProductOutputPort`.
- Keep Controller responsibility limited to HTTP validation, primitive-to-input conversion, Input Port calls, and HTTP error status mapping.
- Keep Presenter responsibility limited to API response shape.
- Update Pattern 3 documentation and progress tracking.

### Out Of Scope
- Concrete Infrastructure repository implementation.
- Laravel service provider binding for repositories, input ports, and output ports.
- Runtime DI wiring that guarantees the same Presenter instance is shared between the Controller response and Interactor Output Port.
- Product listing endpoint.
- Vue frontend or TypeScript contracts.
- Automated Pattern 3 tests.

### Acceptance Criteria
- Controller depends on Input Port interfaces instead of concrete Interactors.
- Controller does not format `Product` Entity objects directly.
- Presenter implements `ProductOutputPort` and owns the API response data shape.
- Interactors, UseCases, and Entities remain independent from Laravel HTTP classes.
- Documentation explains why Presenter exists in Pattern 3 and how it differs from Pattern 2.

## Day 23: Pattern 3 TypeScript API Contracts

### Purpose
- Add an explicit TypeScript representation of the Day 22 Pattern 3 HTTP boundary.
- Make Pattern 3's frontend-facing contract reflect use-case-oriented Input boundary names and Presenter-owned Output shape.
- Contrast Pattern 3 with Pattern 2's smaller generic API DTOs around the Vue composable.

### Scope
- Add TypeScript product contract types under `pattern3-clean/resources/js/contracts/`.
- Add compile-time contract fixtures under `pattern3-clean/tests/types/`.
- Add minimal TypeScript tooling for strict `tsc --noEmit` verification.
- Update Pattern 3 README, Clean Architecture docs, Vue responsibility docs, and progress tracking.

### Out Of Scope
- Vue components, composables, or HTTP client modules.
- PHP runtime behavior, routes, dependency injection, repositories, or Presenter behavior changes.
- Error, retry, authorization, persistence, OpenAPI, schema generation, or PHP-to-TypeScript generation.

### Acceptance Criteria
- `POST /products` request keys are represented as `sku`, `name`, `stock_quantity`, and `price_amount_in_cents`.
- `POST /products/{productId}/stock` request keys are represented as `type`, `quantity`, and `operator_role` for every stock operation.
- Stock operations are represented as a discriminated union on `type` with `in`, `out`, and `adjustment`.
- Presenter success output is represented with a `data` envelope and nullable `id`.
- TypeScript rejects invalid literal combinations through compile-time fixtures.
- Documentation states that these contracts mirror the current JSON boundary and are not generated from PHP.

## Day 24: Pattern 3 Mocked Fast Interactor Tests

### Purpose
- Prove Pattern 3 Interactors can be tested directly without Laravel HTTP, Eloquent, or a database.
- Make the learning contrast explicit: Pattern 1 verifies an MVC slice through HTTP and DB, Pattern 2 verifies one Application Service with an in-memory repository, and Pattern 3 verifies operation-specific UseCases by mocking Repository and Output Ports.

### Scope
- Add a self-contained PHPUnit setup under `pattern3-clean/`.
- Add focused UseCase unit tests for product creation, stock increase, stock decrease, and direct stock adjustment.
- Use real `Product`, Value Objects, Input DTOs, and Interactors.
- Use PHPUnit mocks only for `ProductRepositoryInterface` and `ProductOutputPort`.
- Document why ports make these tests fast and how the test boundary differs across MVC, Onion, and Clean.

### Out Of Scope
- Pattern 3 application/runtime behavior changes.
- Changes to Pattern 3 Entity, UseCase, Controller, Presenter, Port, repository contract, HTTP contract, or TypeScript contract files.
- Laravel DI wiring, Controller/API tests, Presenter JSON tests, Eloquent repository implementation, database tests, Vue tests, coverage tooling, production refactors, new business rules, and Pattern 1 or Pattern 2 changes.
- Hand-written fake repositories or presenters.

### Acceptance Criteria
- Pattern 3 has a local PHPUnit manifest, bootstrap, and UseCase unit test file.
- Product creation presents `ProductOutputData` with the expected observable fields.
- Duplicate SKU throws `Product SKU already exists.` and performs no save or output.
- Stock increase and decrease save and present updated product output.
- Missing product throws `Product was not found.` and performs no save or output.
- Decrease below zero throws `Stock quantity cannot be negative.` and performs no save or output.
- Manager direct adjustment saves and presents updated product output.
- Staff direct adjustment throws `Only managers can adjust stock directly.` before repository or output work.
- Day 24 is marked complete only after the Pattern 3 PHPUnit suite passes.

## Day 25: Onion vs Clean Structural Comparison

### Purpose
- Complete the Day 25 learning comparison between this repository's Pattern 2 Onion + DDD implementation and Pattern 3 Clean + DDD implementation.
- Prevent terminology-based misunderstandings by grounding the comparison in the current classes and documented runtime gaps.

### Scope
- Add `docs/08_onion-vs-clean.md` as the canonical Day 25 comparison.
- Document the concrete request-to-response paths for Pattern 2 and Pattern 3.
- Update the root README and Pattern 3 README to point to the comparison without presenting the whole roadmap as complete.
- Update progress tracking for Day 25.

### Out Of Scope
- Runtime code changes in Pattern 1, Pattern 2, or Pattern 3.
- New DI providers, repositories, routes, migrations, dependencies, tests, or integration wiring.
- Completing Day 26 or rewriting the final README.

### Acceptance Criteria
- The comparison states the shared foundation: inward dependencies, DI compatibility, Laravel/Eloquent-independent business rules, and persistence behind an interface.
- The comparison states repository-specific differences: `ProductInventoryService` versus operation-specific Interactors, implicit Application Service entry versus explicit Input Ports, Domain `Product` returned to Controller versus `ProductOutputData` through Output Port to `ProductPresenter`, and in-memory repository tests versus mocked Repository/Output Port tests.
- The comparison states that class names, Interactor count, DI, and interfaces alone do not define Onion or Clean, and that Onion converges with Clean when explicit use-case input/output boundaries are added.
- The comparison accurately states that Pattern 3 still lacks Eloquent repository implementation, Laravel DI wiring, routes, and HTTP-to-DB integration proof.
- Day 25 is marked complete with date `2026/08/25`.
