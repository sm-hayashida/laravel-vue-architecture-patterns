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

## Day 16: Pattern 2 Application Service

### Application Boundary
- Add `pattern2-onion/app/Application/` as the layer that coordinates use of Domain objects.
- Application code may depend on Domain Entity, Value Object, Domain Service, and Repository Interface.
- Application code must not depend on Eloquent, Laravel Request / Response, DB facade, Controller, or Vue.

### Directory Additions
```text
pattern2-onion/app/Application/
├── Exceptions/
│   └── InventoryApplicationException.php
└── Services/
    └── ProductInventoryService.php
```

### Responsibility Placement
- `ProductInventoryService`:
  - creates Product aggregates after checking SKU uniqueness through `ProductRepositoryInterface`.
  - loads Product aggregates by `ProductId`.
  - calls `Product::increaseStock()`, `Product::decreaseStock()`, or `Product::adjustStock()`.
  - calls `StockAdjustmentPolicy::assertCanAdjust()` before direct adjustment.
  - saves changed Product aggregates through `ProductRepositoryInterface`.
- `InventoryApplicationException`:
  - represents application workflow failures that are not pure domain invariants, such as product not found or duplicate SKU.

### MVC / Onion / Clean Comparison Note
- Pattern 1 MVC placed orchestration, validation, permission branching, transaction control, and response shaping in `ProductController`.
- Pattern 2 Application Service owns only the application flow: fetch aggregate, ask Domain to apply rules, save aggregate.
- Clean later should split this further into use-case-specific Interactors and input/output ports.

### Verification
- Run PHP syntax checks for Domain and Application classes.
- Search Pattern 2 for Laravel / Eloquent imports to confirm Onion dependency direction is still preserved.

## Day 17: Pattern 2 Infrastructure Eloquent Repository

### Application Boundary
- Add `pattern2-onion/app/Infrastructure/` as the outer layer.
- Infrastructure may depend on Domain interfaces and Value Objects.
- Domain and Application must not import Infrastructure classes.

### Directory Additions
```text
pattern2-onion/app/Infrastructure/
├── Persistence/
│   └── Eloquent/
│       └── Models/
│           ├── ProductRecord.php
│           └── StockMovementRecord.php
└── Repositories/
    └── EloquentProductRepository.php
```

### Responsibility Placement
- `ProductRecord` and `StockMovementRecord`:
  - represent database tables through Eloquent.
  - contain persistence mapping details only.
- `EloquentProductRepository`:
  - implements `ProductRepositoryInterface`.
  - fetches and saves database rows.
  - maps Eloquent records to `Product`, `ProductId`, `Sku`, `ProductName`, `StockQuantity`, and `Money`.
  - converts `Money` cents to database decimal strings without using floats.

### MVC / Onion / Clean Comparison Note
- Pattern 1 MVC used the Eloquent `Product` model directly as both persistence model and business logic holder.
- Pattern 2 Infrastructure keeps Eloquent at the outer layer and translates it into Domain objects.
- Clean later should treat a similar persistence implementation as a gateway adapter behind use-case ports.

### Verification
- Run PHP syntax checks for Pattern 2.
- Search Domain and Application for Infrastructure / Eloquent imports.
- Search Infrastructure to confirm it depends inward on Domain interfaces rather than the reverse.

## Day 18a: Pattern 2 Controller / DI / API Connection

### Application Boundary
- Add HTTP delivery code as an outer layer.
- HTTP Controller may depend on Application Service, Domain Value Objects, and Domain enums for input conversion.
- HTTP Controller must not call Eloquent records or repositories directly.
- Service provider may bind Domain repository interfaces to Infrastructure implementations.

### Directory Additions
```text
pattern2-onion/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ProductController.php
│   └── Providers/
│       └── AppServiceProvider.php
└── routes/
    └── api.php
```

### API Contract
- `GET /products`
  - returns `data: ProductResponse[]`.
- `POST /products`
  - request: `sku`, `name`, `stock_quantity`, `price_amount_in_cents`.
  - response: `201` with `data: ProductResponse`.
- `POST /products/{productId}/stock`
  - request: `type`, `quantity`, `operator_role`.
  - response: `200` with `data: ProductResponse`.

`ProductResponse`:
```json
{
  "id": 1,
  "sku": "SKU-001",
  "name": "Sample Product",
  "stock_quantity": 10,
  "price_amount_in_cents": 1200
}
```

### Responsibility Placement
- `ProductController`:
  - validates HTTP request shape.
  - converts primitives into `Sku`, `ProductName`, `StockQuantity`, `MovementQuantity`, `Money`, `ProductId`, and enums.
  - delegates use of Domain rules to `ProductInventoryService`.
  - converts Domain `Product` to JSON.
- `AppServiceProvider`:
  - binds `ProductRepositoryInterface` to `EloquentProductRepository`.
- `ProductInventoryService::listProducts()`:
  - provides a frontend-friendly listing flow without exposing Infrastructure to Controller.

### MVC / Onion / Clean Comparison Note
- Pattern 1 Controller owned permission branching and called Eloquent Model behavior directly.
- Pattern 2 Controller converts HTTP input and delegates; permission and stock rules stay in Domain/Application.
- Clean later should make input/output ports and presenters more explicit than this Onion-style controller.

### Verification
- Run PHP syntax checks for Pattern 2.
- Search Domain and Application for Laravel HTTP / Infrastructure imports.
- Search Controller for direct `ProductRecord` / Eloquent Repository usage.

## Day 18b: Pattern 2 Vue Composable Separation

### Frontend Boundary
- Add `pattern2-onion/resources/js/` as the frontend comparison layer.
- Keep API details in `resources/js/api/`.
- Keep reusable stateful workflow logic in `resources/js/composables/`.
- Keep the Vue component focused on rendering and event wiring.

### Directory Additions
```text
pattern2-onion/resources/js/
├── api/
│   └── productApi.ts
├── components/
│   └── InventoryApp.vue
├── composables/
│   └── useInventoryProducts.ts
├── types/
│   └── product.ts
└── app.ts
```

### Responsibility Placement
- `types/product.ts`:
  - owns API-facing TypeScript contracts for Product, create payload, update payload, movement type, and operator role.
- `api/productApi.ts`:
  - owns axios calls and endpoint paths.
- `useInventoryProducts.ts`:
  - owns reactive state, form state, loading state, message state, API orchestration, and error extraction.
- `InventoryApp.vue`:
  - renders the form, summary, and table.
  - calls composable commands from UI events.
  - does not import axios.

### MVC / Onion / Clean Comparison Note
- Pattern 1 kept HTTP calls, form state, message handling, and rendering in one component.
- Pattern 2 separates API/state workflow from UI so the frontend mirrors the backend responsibility split.
- Pattern 3 later can push this further with stricter input/output types shared around use-case-oriented endpoints.

### Verification
- Run text checks to confirm `InventoryApp.vue` does not import axios.
- Run syntax-oriented checks available without installing Pattern 2 frontend dependencies.
- Document that frontend build is not run until Pattern 2 has a full npm scaffold.

## Day 19: Pattern 2 Unit Tests

### Test Boundary
- Add a small PHPUnit setup inside `pattern2-onion/`.
- Keep tests focused on pure Domain and Application behavior.
- Avoid Laravel TestCase, RefreshDatabase, HTTP requests, Eloquent records, and migrations.

### Directory Additions
```text
pattern2-onion/
├── composer.json
├── phpunit.xml
└── tests/
    ├── bootstrap.php
    ├── Support/
    │   └── InMemoryProductRepository.php
    └── Unit/
        ├── Application/
        │   └── ProductInventoryServiceTest.php
        └── Domain/
            └── ProductTest.php
```

### Responsibility Placement
- `ProductTest`:
  - verifies `Product` stock operations and Domain exceptions.
  - verifies `StockAdjustmentPolicy` without HTTP role strings or Controller branching.
- `ProductInventoryServiceTest`:
  - verifies application flow using `InMemoryProductRepository`.
  - checks SKU duplication before save, missing product handling, and stock operation persistence calls.
- `InMemoryProductRepository`:
  - implements the Domain repository contract only for tests.
  - lets Application Service tests replace Infrastructure without changing production code.

### MVC / Onion / Clean Comparison Note
- Pattern 1 proves behavior through Feature Tests because rules are attached to Controller, Eloquent, transactions, and database rows.
- Pattern 2 can test stock rules and orchestration directly because Domain/Application depend on contracts and pure PHP objects.
- Pattern 3 later should make test boundaries even more use-case-specific with Input/Output Ports and Presenter-facing output contracts.

### Verification
- Run Pattern 2 PHPUnit tests when dependencies are available.
- Run PHP syntax checks for added test and support files.
- Confirm no production code changed for Day 19.

## Day 20: Pattern 3 UseCase Interactors

### Application Boundary
- Add `pattern3-clean/app/Entities/` for enterprise/domain rules.
- Add `pattern3-clean/app/UseCases/Products/` for application-specific rules.
- UseCase code must not depend on Laravel, Eloquent, Request / Response, Controller, Presenter, or Pattern 2 classes.

### Directory Additions
```text
pattern3-clean/app/
├── Entities/
│   ├── Product.php
│   ├── Enums/
│   │   └── OperatorRole.php
│   ├── Exceptions/
│   │   └── InventoryEntityException.php
│   └── ValueObjects/
│       ├── Money.php
│       ├── MovementQuantity.php
│       ├── ProductId.php
│       ├── ProductName.php
│       ├── Sku.php
│       └── StockQuantity.php
└── UseCases/
    └── Products/
        ├── CreateProductInput.php
        ├── CreateProductInteractor.php
        ├── IncreaseStockInput.php
        ├── IncreaseStockInteractor.php
        ├── DecreaseStockInput.php
        ├── DecreaseStockInteractor.php
        ├── AdjustStockInput.php
        ├── AdjustStockInteractor.php
        ├── Exceptions/
        │   └── InventoryUseCaseException.php
        └── Gateways/
            └── ProductRepositoryInterface.php
```

### Responsibility Placement
- `Product` Entity:
  - owns stock increase, decrease, and direct adjustment behavior.
  - preserves the invariant that stock cannot be negative.
- Value Objects:
  - validate primitive values before they reach Entity / UseCase behavior.
- `CreateProductInteractor`:
  - checks SKU duplication and creates a new Product.
- `IncreaseStockInteractor` / `DecreaseStockInteractor` / `AdjustStockInteractor`:
  - represent separate user intentions instead of a single product inventory service method group.
  - load Product through `ProductRepositoryInterface`, ask Entity to apply rules, and save.
- `AdjustStockInteractor`:
  - owns the application rule that only managers can directly adjust stock.

### MVC / Onion / Clean Comparison Note
- Pattern 2 groups inventory operations inside `ProductInventoryService`.
- Pattern 3 makes the application boundary scream the available use cases through class names.
- Day 21 should add explicit InputPort / OutputPort interfaces and move response shaping toward Presenter boundaries.

### Verification
- Run PHP syntax checks for Pattern 3 classes.
- Search Pattern 3 for Laravel / Eloquent / Pattern 2 imports.

## Day 21: Pattern 3 Input / Output Ports

### UseCase Boundary
- Add explicit Input Port interfaces under `UseCases/Products/Ports/Input/`.
- Add an Output Port interface under `UseCases/Products/Ports/Output/`.
- Add presenter-facing output data under `UseCases/Products/Outputs/`.
- Interactors implement Input Ports and depend on Output Ports.

### Directory Additions
```text
pattern3-clean/app/UseCases/Products/
├── Outputs/
│   └── ProductOutputData.php
└── Ports/
    ├── Input/
    │   ├── CreateProductInputPort.php
    │   ├── IncreaseStockInputPort.php
    │   ├── DecreaseStockInputPort.php
    │   └── AdjustStockInputPort.php
    └── Output/
        └── ProductOutputPort.php
```

### Responsibility Placement
- Input Port interfaces:
  - define how outer controllers call each use case.
  - allow controllers to depend on interfaces rather than concrete Interactors.
- `ProductOutputData`:
  - exposes presenter-facing primitive values.
  - prevents outer layers from receiving Entity objects directly.
- `ProductOutputPort`:
  - defines how Interactors hand successful results to a presenter boundary.
- Interactors:
  - keep use-case orchestration.
  - no longer return `Product` to the caller.
  - call `ProductOutputPort::present()` after saving the Product.

### MVC / Onion / Clean Comparison Note
- Pattern 2 Controller receives a Product from `ProductInventoryService` and formats JSON itself.
- Pattern 3 Interactor sends output to an Output Port, so Day 22 can introduce a Presenter that owns response shaping.
- This makes the Controller input side and Presenter output side explicit before adding Laravel HTTP code.

### Verification
- Run PHP syntax checks for Pattern 3 classes.
- Search Pattern 3 for Laravel / Eloquent / Pattern 2 imports.

## Day 22: Pattern 3 Controller / Presenter

### Interface Adapter Boundary
- Add HTTP and presentation code outside the UseCase / Entity layers.
- Controller may depend on Input Port interfaces, input DTOs, Value Objects, Laravel Request / JsonResponse, and the concrete Presenter.
- Presenter may depend on `ProductOutputData`, `ProductOutputPort`, and Laravel JsonResponse.
- UseCases and Entities must not depend on Controller, Presenter, Laravel Request / Response, or Pattern 2 namespaces.
- Full Laravel DI wiring remains outside Day 22. When wiring is added later, `ProductOutputPort` and the Controller-visible `ProductPresenter` must resolve to the same request-scoped instance.

### Directory Additions
```text
pattern3-clean/app/
├── Http/
│   └── Controllers/
│       └── ProductController.php
└── InterfaceAdapters/
    └── Presenters/
        └── ProductPresenter.php
```

### API Contract
- `POST /products`
  - request: `sku`, `name`, `stock_quantity`, `price_amount_in_cents`.
  - response: `201` with `data: ProductResponse`.
- `POST /products/{productId}/stock`
  - request: `type`, `quantity`, `operator_role`.
  - `type` is one of `in`, `out`, `adjustment`.
  - response: `200` with `data: ProductResponse`.

`ProductResponse`:
```json
{
  "id": 1,
  "sku": "SKU-001",
  "name": "Sample Product",
  "stock_quantity": 10,
  "price_amount_in_cents": 1200
}
```

### Responsibility Placement
- `ProductController`:
  - validates HTTP request shape.
  - converts request primitives into Value Objects and use-case input DTOs.
  - calls Input Port interfaces such as `CreateProductInputPort`.
  - maps domain/use-case failures to HTTP status codes.
  - does not receive or format `Product` Entity objects.
- `ProductPresenter`:
  - implements `ProductOutputPort`.
  - converts `ProductOutputData` into API response keys.
  - owns the JSON-facing field names such as `stock_quantity` and `price_amount_in_cents`.
- Interactors:
  - still execute use-case flow and call `ProductOutputPort`.
  - remain unaware of JSON, HTTP status, Controller, and Presenter implementation details.

### MVC / Onion / Clean Comparison Note
- Pattern 2 Controller called `ProductInventoryService`, received a `Product`, and formatted JSON inside the Controller.
- Pattern 3 Controller sends input through Input Ports and receives output indirectly through Presenter.
- This makes the Controller input side and Presenter output side separate, so response-shape changes can be localized to the Presenter.

### Verification
- Run PHP syntax checks for Pattern 3 classes.
- Search Pattern 3 UseCases and Entities for Laravel HTTP / Controller / Presenter imports.
- Confirm Controller does not import `App\Entities\Product`.
- Confirm the remaining runtime DI wiring gap is documented as non-scope.

## Day 23: Pattern 3 TypeScript API Contracts

### TypeScript Boundary
- Keep TypeScript under the Clean pattern only: `pattern3-clean/resources/js/contracts/`.
- Model the existing Day 22 HTTP boundary without changing PHP runtime behavior.
- Preserve snake_case at the HTTP boundary.
- Treat the file as a consumer-side mirror, not as generated PHP metadata.

### Contract Shape
- `CreateProductRequest`:
  - `sku`
  - `name`
  - `stock_quantity`
  - `price_amount_in_cents`
- `IncreaseStockRequest`:
  - `type: 'in'`
  - `quantity`
  - `operator_role`
- `DecreaseStockRequest`:
  - `type: 'out'`
  - `quantity`
  - `operator_role`
- `AdjustStockRequest`:
  - `type: 'adjustment'`
  - `quantity`
  - `operator_role`
- `StockOperationRequest`:
  - union of the three stock operation requests, discriminated by `type`.
- `ProductPresenterResponse`:
  - `data.id` is `number | null`.
  - `data.sku`, `data.name`, `data.stock_quantity`, and `data.price_amount_in_cents` match `ProductPresenter`.

### MVC / Onion / Clean Comparison Note
- Pattern 1 MVC keeps frontend API usage inline and does not introduce shared TypeScript contracts.
- Pattern 2 Onion uses concise API DTOs around its API module and composable, such as create and update payloads.
- Pattern 3 Clean makes the operation names visible in the TypeScript boundary, mirroring Input Port and Presenter responsibilities.

### Verification
- Run the pinned local TypeScript install for `pattern3-clean`.
- Run `npm --prefix pattern3-clean run typecheck`.
- Run `git diff --check`.
- Confirm changed paths are within Day 23 scope and no PHP files changed.

## Day 24: Pattern 3 Mocked Fast Interactor Tests

### Test Boundary
- Test type: Unit.
- Boundary path: direct test call -> Interactor/Input DTO -> real Entity -> mocked Repository/Output Ports.
- The PHPUnit mocks are outer test adapters. The UseCase remains dependent only on `ProductRepositoryInterface` and `ProductOutputPort`.
- Do not cross HTTP, Laravel DI, Eloquent, database state, jobs, broadcasting, or external APIs.

### Test Setup
- Add `pattern3-clean/composer.json` with the same minimal PHPUnit and PSR-4 style used by Pattern 2.
- Add `pattern3-clean/phpunit.xml` with `tests/bootstrap.php` and `tests/Unit`.
- Add `pattern3-clean/tests/bootstrap.php` to autoload `App\\` and `Tests\\`.
- Add `pattern3-clean/.gitignore` for local dependency and PHPUnit cache artifacts.

### UseCase Unit Cases
- `CreateProductInteractor`:
  - success: duplicate check is false, a real `Product` is saved, and `ProductOutputData` is presented.
  - duplicate SKU: exception is thrown before save or output.
- `IncreaseStockInteractor`:
  - success: existing product stock is increased, saved, and presented.
  - missing product: exception is thrown before save or output.
- `DecreaseStockInteractor`:
  - success: existing product stock is decreased, saved, and presented.
  - below zero: Entity exception is thrown before save or output.
- `AdjustStockInteractor`:
  - manager: stock is adjusted, saved, and presented.
  - staff: use-case exception is thrown before any repository or output work.

### MVC / Onion / Clean Comparison Note
- Pattern 1 MVC protects the caller-visible slice through HTTP requests, Eloquent persistence, and database assertions.
- Pattern 2 Onion protects Domain and Application behavior without HTTP or DB by using an in-memory implementation of the repository interface.
- Pattern 3 Clean protects operation-specific UseCases by replacing both the Repository Gateway and Output Port with PHPUnit mocks, so the test can assert persistence/output side effects without building Controller, Presenter, Eloquent, or Laravel wiring.

### Verification
- Run `/Users/hayashida/Documents/learning/laravel-vue-architecture-patterns/pattern1-mvc/vendor/bin/phpunit -c pattern3-clean/phpunit.xml --do-not-cache-result`.
- Run `php -l pattern3-clean/tests/bootstrap.php`.
- Run `php -l pattern3-clean/tests/Unit/UseCases/ProductInteractorsTest.php`.
- Run `git diff --check`.
- Confirm no files under `pattern3-clean/app/`, Pattern 1, or Pattern 2 changed, and no staged changes exist.

## Day 25: Onion vs Clean Structural Comparison

### Documentation Boundary
- Add a documentation-only Day 25 comparison under `docs/08_onion-vs-clean.md`.
- Keep the comparison grounded in existing Pattern 2 and Pattern 3 classes.
- Do not change PHP, TypeScript, test, HTTP, JSON, or business behavior.

### Responsibility Placement
- Pattern 2:
  - `ProductController` receives HTTP input, converts primitives to Value Objects and enums, calls `ProductInventoryService`, and formats returned Domain `Product` objects into JSON.
  - `ProductInventoryService` groups product creation, stock increase, stock decrease, direct adjustment, and listing operations.
  - Persistence is accessed through Domain `ProductRepositoryInterface`; the runtime binding to `EloquentProductRepository` already exists in Pattern 2.
- Pattern 3:
  - `ProductController` receives HTTP input, converts primitives to input DTOs, and calls Input Port interfaces.
  - `CreateProductInteractor`, `IncreaseStockInteractor`, `DecreaseStockInteractor`, and `AdjustStockInteractor` own operation-specific use-case flow.
  - Interactors send `ProductOutputData` through `ProductOutputPort`; `ProductPresenter` owns JSON-facing output shape.
  - Pattern 3 currently has no Eloquent repository implementation, Laravel DI wiring, routes, or HTTP-to-DB integration proof.

### Comparison Shape
- Include one concise table.
- Include small textual request-to-response diagrams for Pattern 2 and Pattern 3.
- Include a terminology caveat: class names, Interactor count, DI, and interfaces alone are insufficient to define the architecture.
- Include a trade-off summary: Onion has fewer explicit boundary types and suits domain-centered grouping; Clean adds ceremony but makes use-case input/output ownership and test seams explicit.

### Verification
- Run `git diff --check`.
- Run `git status --short --branch` and confirm no staged changes.
- Inspect `git diff --name-only` and confirm all changed files are in the allowed Day 25 documentation list.
- Search updated documentation for the required comparison terms and Pattern 3 runtime gap.
- Confirm no files under `pattern1-mvc/`, `pattern2-onion/`, `pattern3-clean/app/`, `pattern3-clean/resources/`, or `pattern3-clean/tests/` changed.
