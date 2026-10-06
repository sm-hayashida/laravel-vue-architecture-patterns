# Pattern 3: Clean Architecture + DDD

## 概要

クリーンアーキテクチャを採用し、在庫管理の操作を UseCase / Interactor 単位で表現する実装例です。

Pattern 2 の Onion Architecture では、`ProductInventoryService` に商品作成・在庫増加・在庫減少・直接調整の手順をまとめました。Pattern 3 では「ユーザーが何をしたいか」をクラス名で表し、操作ごとに Interactor を分けます。

## Day 20: UseCase（Interactor）の定義

Day 20 では、Laravel や Eloquent に依存しない Entity と UseCase 層を追加しました。

```text
app/
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

### 責務

- `Product`: 在庫増加、減少、直接調整を持つ Entity。
- Value Object: SKU、商品名、在庫数、移動数量、金額、ID の妥当性を保証する。
- `CreateProductInteractor`: SKU 重複を確認して Product を作成する。
- `IncreaseStockInteractor`: 商品を取得し、入庫数量を Entity に適用して保存する。
- `DecreaseStockInteractor`: 商品を取得し、出庫数量を Entity に適用して保存する。
- `AdjustStockInteractor`: manager だけ直接調整できることを確認して保存する。
- `ProductRepositoryInterface`: UseCase が必要とする永続化の約束。

### Pattern 2 との違い

Pattern 2 では `ProductInventoryService` が複数の在庫操作をメソッドとして持ちます。これは Onion Architecture の Application Service として自然です。

Pattern 3 では、`CreateProductInteractor`、`IncreaseStockInteractor`、`DecreaseStockInteractor`、`AdjustStockInteractor` のように操作単位で分けます。これにより、フォルダを見るだけで「このシステムで何ができるか」が分かりやすくなります。

この段階ではまだ InputPort / OutputPort は追加していません。Day 21 で Controller から呼ぶ入口と Presenter へ返す出口を Interface として明示します。

## Day 21: Input / Output Port（Interface）の定義

Day 21 では、UseCase の入口と出口を Interface として明示しました。

```text
app/UseCases/Products/
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

### Input Port

Input Port は、Controller が UseCase を呼び出すための約束です。

```text
Controller
  -> CreateProductInputPort
      <- CreateProductInteractor
```

Controller は具体的な `CreateProductInteractor` ではなく、`CreateProductInputPort` に依存できます。これにより、外側の HTTP 層は UseCase の具象クラスを直接知る必要がなくなります。

### Output Port

Output Port は、UseCase が Presenter に結果を渡すための約束です。

```text
CreateProductInteractor
  -> ProductOutputPort
      <- ProductPresenter
```

Day 21 時点では Presenter の具象クラスはまだ作っていません。代わりに `ProductOutputPort` と `ProductOutputData` を追加し、Interactor が Entity を直接返さず、Presenter 向けのデータを出力境界に渡す形にしました。

### Pattern 2 との違い

Pattern 2 では、Controller が `ProductInventoryService` を呼び、返ってきた `Product` を JSON に変換していました。

Pattern 3 では、Controller 側は Input Port に入力を渡し、出力整形は Output Port の先にある Presenter に任せます。これにより、Controller は入力変換、Presenter は出力変換、Interactor はユースケースの実行、Entity は在庫ルールという境界がより明確になります。

## Day 22: Controller / Presenter の実装

Day 22 では、Day 21 で定義した Input Port / Output Port の外側に Controller と Presenter を追加しました。

```text
app/
├── Http/
│   └── Controllers/
│       └── ProductController.php
└── InterfaceAdapters/
    └── Presenters/
        └── ProductPresenter.php
```

### Controller

`ProductController` は HTTP リクエストを受け取り、Laravel の validation を行ったあと、プリミティブ値を Value Object と UseCase input DTO に変換します。

```text
HTTP Request
  -> ProductController
      -> CreateProductInputPort
      -> IncreaseStockInputPort
      -> DecreaseStockInputPort
      -> AdjustStockInputPort
```

ここで重要なのは、Controller が具体的な `CreateProductInteractor` ではなく `CreateProductInputPort` に依存している点です。Controller は「どの UseCase を呼ぶか」は知っていますが、UseCase の実装詳細や Entity の内部構造は知りません。

### Presenter

`ProductPresenter` は `ProductOutputPort` を実装し、UseCase から渡された `ProductOutputData` を API レスポンス用のキーに変換します。

```text
Interactor
  -> ProductOutputPort
      <- ProductPresenter
          -> JSON response data
```

Pattern 2 では Controller が `Product` を受け取り、`stock_quantity` や `price_amount_in_cents` のような JSON キーへ直接変換していました。Pattern 3 ではこの出力整形を Presenter に移し、Controller は入力変換と HTTP status の返却に集中します。

### Pattern 2 との違い

Pattern 2 の Controller は薄くなっていましたが、Application Service から返った `Product` を JSON に変換する責務はまだ Controller に残っていました。

Pattern 3 では、UseCase は `ProductOutputData` を `ProductOutputPort` に渡し、Presenter が API response shape を決めます。これにより、API の表示形式を変えたい場合も、UseCase や Entity ではなく Presenter を中心に変更できます。

Day 22 時点では Laravel の DI binding はまだ追加していません。実際に HTTP 経由で動かす段階では、Interactor が使う `ProductOutputPort` と Controller が response を取り出す `ProductPresenter` が同じ request scope のインスタンスになるように wiring する必要があります。

## Day 23: TypeScript API Contract

Day 23 では、Day 22 で作った HTTP 境界を TypeScript の型として表現しました。

```text
resources/js/contracts/
└── product.ts

tests/types/
└── product-contract.test.ts
```

`CreateProductRequest` は `POST /products` の request shape を表します。`IncreaseStockRequest`、`DecreaseStockRequest`、`AdjustStockRequest` は `POST /products/{productId}/stock` の use case ごとの request shape を表し、`StockOperationRequest` は `type` を discriminant にした union です。

`ProductPresenterResponse` は単品の成功レスポンス、`ProductListPresenterResponse` は商品一覧の成功レスポンスの `data` envelope を表します。`id` は `ProductOutputData` と Presenter の current shape に合わせて `number | null` としています。

### Pattern 2 との違い

Pattern 2 の TypeScript type は、Composable と API module が共有する小さな DTO として `Product`、`CreateProductPayload`、`UpdateStockPayload` を定義しました。

Pattern 3 では、同じ HTTP boundary をそのまま緩い payload として置くのではなく、Clean Architecture の Input / Output boundary に合わせて `IncreaseStockRequest` や `ProductPresenterResponse` のように操作と Presenter を名前に出します。これは PHP から TypeScript を自動生成する仕組みではなく、現在の JSON contract を consumer 側で厳格に写したものです。

## Day 24: Mocked Fast Interactor Tests

Day 24 では、Pattern 3 の Interactor を Laravel HTTP、Eloquent、database から切り離して直接テストしました。

```text
tests/
└── Unit/
    └── UseCases/
        └── ProductInteractorsTest.php
```

テストは `CreateProductInteractor`、`IncreaseStockInteractor`、`DecreaseStockInteractor`、`AdjustStockInteractor` を直接呼びます。入力には real Input DTO と Value Object を使い、在庫ルールには real `Product` Entity を使います。差し替えるのは外側の境界である `ProductRepositoryInterface` と `ProductOutputPort` だけです。

```text
Test
  -> Interactor
      -> Product Entity
      -> ProductRepositoryInterface mock
      -> ProductOutputPort mock
```

これにより、成功時は `ProductOutputData` の observable fields と save/present の side effect を確認できます。失敗時は例外メッセージと、save や output が行われないことを確認できます。

### Pattern 1 / Pattern 2 との違い

Pattern 1 の Feature Test は HTTP request から入り、Eloquent と database assertions まで含めて MVC slice を確認します。これは利用者に近い一方で、実行境界は広くなります。

Pattern 2 の Unit Test は `ProductInventoryService` を in-memory repository に差し替えて確認します。Application Service と Domain を DB なしで守れますが、Service は複数操作をまとめて持ちます。

Pattern 3 の Unit Test は操作ごとの Interactor を直接呼び、Repository Gateway だけでなく Output Port も PHPUnit mock に差し替えます。UseCase が内側の interface にだけ依存しているため、Controller、Presenter、Eloquent、Laravel DI wiring を用意しなくても、高速に application flow と output boundary を保護できます。

## Day 25: Onion vs Clean の構造比較

Day 25 では、Pattern 2 Onion + DDD と Pattern 3 Clean + DDD の違いを `docs/08_onion-vs-clean.md` にまとめました。

この Pattern 3 では、Pattern 2 の `ProductInventoryService` に相当する操作を `CreateProductInteractor`、`IncreaseStockInteractor`、`DecreaseStockInteractor`、`AdjustStockInteractor` に分けています。Controller は `CreateProductInputPort` などの Input Port を呼び、Interactor は `ProductOutputData` を `ProductOutputPort` に渡し、`ProductPresenter` が JSON response shape を持ちます。

Day 25 時点では Eloquent repository 実装、Laravel DI wiring、routes、HTTP から DB までを通す integration proof はありませんでした。これらは後述の API 実行構成で追加しました。

## 商品 API を動かす

PHP 8.2 以上、Composer、PHP の SQLite 拡張が必要です。`pattern3-clean/` で次を実行します。

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

別のターミナルから `GET /api/products`、`POST /api/products`、`POST /api/products/{productId}/stock` を呼べます。登録時の JSON は `sku`、`name`、`stock_quantity`、`price_amount_in_cents` です。在庫更新時の JSON は `type`（`in` / `out` / `adjustment`）、`quantity`、`operator_role`（`staff` / `manager`）です。直接調整は `manager` のみ可能です。

```bash
php artisan route:list --path=api
./vendor/bin/phpunit
```

`routes/api.php` を `RouteServiceProvider` が `/api` プレフィックス付きで読み込みます。Controller が Input Port を呼ぶと、`AppServiceProvider` の binding により Interactor が選ばれます。Interactor は Repository Gateway を通して Eloquent Repository に保存し、結果を Output Port に渡します。Output Port と Controller が受け取る `ProductPresenter` は request scope で同じインスタンスです。商品一覧には一覧用の Input / Output Port を用います。

HTTP の Feature Test は SQLite のインメモリ DB を使い、一覧、登録、在庫の入出庫・直接調整、失敗時の非更新を確認します。Vue 画面は接続していません。

## 次のステップ

- Day 26: 全体振り返り・最終READMEの完成。
