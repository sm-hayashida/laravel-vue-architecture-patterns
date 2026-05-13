# Design

## Day 10: Pattern 1 MVC Foundation

### Application Boundary
- `pattern1-mvc/` is an independent Laravel application.
- Pattern 1 uses Laravel's standard MVC conventions without introducing repository, use case, or domain layers.

### Schema

#### products
- `id`
- `sku`: unique stock keeping unit.
- `name`: product display name.
- `stock_quantity`: current stock amount.
- `price`: product price in the smallest practical decimal unit for the sample.
- timestamps.

#### stock_movements
- `id`
- `product_id`: foreign key to `products`.
- `type`: movement kind such as `in`, `out`, or `adjustment`.
- `quantity`: moved quantity.
- `reason`: nullable text for audit context.
- timestamps.

### Rationale
- Pattern 1 deliberately starts with a simple database-centered model because later days will expose the limits of placing validation and calculation logic in models/controllers.
- The same inventory concepts will be reused in Pattern 2 and Pattern 3 so architecture differences stay visible.

### Verification
- Install dependencies with Composer.
- Run Laravel tests or migration commands using SQLite when possible.

## Day 11: Pattern 1 MVC Stock Logic

### Application Boundary
- Keep stock behavior inside the Laravel MVC application under `pattern1-mvc/`.
- Use Eloquent Model methods and a Controller action rather than adding Repository, UseCase, Domain Entity, Value Object, or Domain Service layers.

### Responsibility Placement
- `Product` Eloquent Model owns primitive stock mutations:
  - increase stock.
  - decrease stock.
  - adjust stock.
  - create `stock_movements` audit rows.
- `ProductController` owns request validation, operator role checks, transaction handling, and API responses.
- This is intentionally mixed: the Controller knows the permission rule and the Model knows persistence and stock arithmetic.

### MVC / Onion / Clean Comparison Note
- MVC baseline: the rule "only managers can adjust stock" is placed in the Controller, while stock arithmetic is placed in the Eloquent Model.
- Onion later: stock arithmetic should move into domain objects and policies/services protected from Infrastructure.
- Clean later: stock operations should be expressed as use cases with input/output boundaries.

### Verification
- Run Laravel feature tests with SQLite in-memory configured through `phpunit.xml`.

## Day 12: Pattern 1 Vue Inline Screen

### Application Boundary
- Keep frontend code inside the Laravel MVC app under `pattern1-mvc/resources`.
- Mount a single Vue app from `welcome.blade.php`.

### Responsibility Placement
- `InventoryApp.vue` owns:
  - product creation form state.
  - stock update form state.
  - API calls with axios.
  - product table state.
  - request messages and error extraction.
- This deliberately avoids `useProducts`, `useStockManagement`, API clients, DTO mapping, and shared TypeScript types.

### MVC / Onion / Clean Comparison Note
- MVC baseline: a single component can be fast to build but mixes UI state, HTTP details, validation response handling, and workflow rules.
- Onion and Clean later should make the frontend comparison clearer by moving repeated API/state behavior into composables and stricter types where useful.

### Verification
- Build frontend assets with Vite.
- Run existing Laravel tests.

## Day 13: Pattern 1 Retrospective

### Observation Targets
- Backend MVC:
  - `ProductController` owns validation, permission checks, transaction boundaries, and response shape.
  - `Product` Eloquent Model owns stock arithmetic and creates audit records through the relationship.
- Frontend MVC baseline:
  - `InventoryApp.vue` owns form state, API calls, error extraction, table state, and workflow messages.

### Findings
- Implementation speed is high because Laravel defaults make routing, validation, Eloquent persistence, and JSON responses straightforward.
- Changeability starts to degrade once a business rule crosses concerns. For example, direct adjustment permission lives in the Controller, while stock quantity validity lives in the Model.
- Test coverage is mostly feature-test oriented because the important behavior depends on Eloquent persistence, HTTP validation, and database transactions.
- The single Vue component is easy to follow at this size, but API details and screen state are already coupled.

### MVC / Onion / Clean Comparison Note
- MVC baseline keeps the shortest path from request to database, but business intent is not protected by a domain boundary.
- Onion should move quantity and stock rules into domain objects, keeping Infrastructure details outside the domain.
- Clean should express inventory operations as use cases, making input validation, orchestration, and output formatting more explicit.

### Verification
- Documentation-only update.
- Confirm no Pattern 1 runtime behavior changes are introduced.

## Day 14: Pattern 2 Domain Layer

### Application Boundary
- `pattern2-onion/app/Domain/` is the center of the Onion implementation.
- Domain code must be pure PHP and must not import Laravel, Eloquent, Request, Response, DB, or framework facades.
- Persistence identifiers are represented as Value Objects so Infrastructure can map database values later without leaking Eloquent into Domain.

### Directory Layout
```text
pattern2-onion/app/Domain/
├── Entities/
│   └── Product.php
├── Enums/
│   └── StockMovementType.php
├── Exceptions/
│   └── InventoryDomainException.php
└── ValueObjects/
    ├── Money.php
    ├── MovementQuantity.php
    ├── ProductId.php
    ├── ProductName.php
    ├── Sku.php
    └── StockQuantity.php
```

### Responsibility Placement
- `Product` Entity:
  - owns stock increase, decrease, and direct adjustment behavior.
  - preserves the invariant that stock cannot be negative.
  - exposes state as Value Objects.
- Value Objects:
  - validate primitive values at construction time.
  - keep invalid SKU, name, quantity, and price values out of the domain model.
- `StockMovementType`:
  - replaces Pattern 1 string constants with an enum-like domain concept.
- `InventoryDomainException`:
  - represents business-rule violations without using HTTP or Laravel validation classes.

### MVC / Onion / Clean Comparison Note
- Pattern 1 MVC allowed Controller and Eloquent Model to share stock rules.
- Pattern 2 starts by making the Domain layer the owner of stock invariants before any persistence or delivery mechanism exists.
- Pattern 3 later should express the same operations through UseCase input/output boundaries, while Pattern 2 emphasizes protecting the Domain core.

### Verification
- Run PHP syntax checks for the new Domain classes.
- Review imports to confirm no Laravel dependency entered Domain.

## Day 15: Pattern 2 Domain Service And Repository Interface

### Application Boundary
- Continue working only inside `pattern2-onion/app/Domain/`.
- Do not add Infrastructure, Eloquent, service providers, or HTTP delivery code.
- The goal is to define domain concepts and contracts that the later Application layer can depend on.

### Directory Additions
```text
pattern2-onion/app/Domain/
├── Enums/
│   └── OperatorRole.php
├── Repositories/
│   └── ProductRepositoryInterface.php
└── Services/
    └── StockAdjustmentPolicy.php
```

### Responsibility Placement
- `OperatorRole`:
  - represents allowed inventory operator roles as a domain concept.
  - avoids passing loose strings such as `staff` and `manager` through Domain logic.
- `StockAdjustmentPolicy`:
  - owns the rule that only managers can directly adjust stock.
  - is a Domain Service because the rule depends on an actor role rather than only on the `Product` Entity state.
- `ProductRepositoryInterface`:
  - defines persistence operations for the Product aggregate.
  - depends on `Product`, `ProductId`, and `Sku`, not on Eloquent.
  - will be implemented by Infrastructure in Day 17.

### MVC / Onion / Clean Comparison Note
- Pattern 1 placed the adjustment permission check in `ProductController`.
- Pattern 2 makes that business rule callable without HTTP, so an Application Service, CLI, job, or test can reuse the same rule.
- The Repository Interface protects Domain / Application from knowing which database technology stores products.

### Verification
- Run PHP syntax checks.
- Search the Domain layer for Laravel / Eloquent imports.
- Perform a small behavior check for the stock adjustment policy.
