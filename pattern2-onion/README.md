# Pattern 2: Onion Architecture + DDD

## 概要
オニオンアーキテクチャを採用し、DDD（ドメイン駆動設計）の戦術的設計パターンを適用した実装例です。

## ディレクトリ構成（予定）
```
pattern2-onion/
├── app/
│   ├── Domain/              # ドメイン層 (Core)
│   │   ├── Entities/        # エンティティ
│   │   ├── ValueObjects/    # 値オブジェクト
│   │   ├── Services/        # ドメインサービス
│   │   └── Repositories/    # リポジトリ・インターフェース
│   ├── Application/         # アプリケーション層
│   │   └── Services/        # アプリケーションサービス
│   └── Infrastructure/      # インフラストラクチャ層
│       └── Repositories/    # Eloquent による実装
└── ...
```

## Day 14: Domain層の最小実装

最初に、Laravel や Eloquent に依存しない Domain 層だけを追加しました。

```text
app/Domain/
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

### 責務

- `Product`: 在庫の増加、減少、直接調整を持つ集約ルート。
- `StockQuantity`: 現在庫数。0 未満を許可しない。
- `MovementQuantity`: 入庫・出庫の移動数量。0 以下を許可しない。
- `Sku` / `ProductName` / `Money` / `ProductId`: プリミティブ値の妥当性を生成時に保証する。
- `InventoryDomainException`: HTTP や Laravel Validation に依存しない業務ルール違反。

Pattern 1 では、在庫ルールが `ProductController` と Eloquent `Product` Model に分散していました。Pattern 2 ではまず Domain 層に不変条件を集め、後続の Application / Infrastructure 層がこの内側へ依存する形にします。

## Day 15: Domain Service と Repository Interface

Day 15 では、Entity / Value Object だけでは置き場所が曖昧になるルールと、永続化の抽象を追加しました。

```text
app/Domain/
├── Enums/
│   └── OperatorRole.php
├── Repositories/
│   └── ProductRepositoryInterface.php
└── Services/
    └── StockAdjustmentPolicy.php
```

### Domain Service

`StockAdjustmentPolicy` は「直接在庫調整は manager だけができる」というルールを持ちます。

このルールは `Product` の在庫数そのものではなく、操作する人の役割に依存します。そのため `Product` Entity に無理に入れず、Domain Service として分けています。

Pattern 1 ではこのチェックを `ProductController` に置いていました。Pattern 2 では HTTP から切り離したため、Controller、CLI、ジョブ、テストのどこからでも同じルールを使える形になります。

### Repository Interface

`ProductRepositoryInterface` は Product 集約を保存・取得するための約束です。

```php
public function findById(ProductId $id): ?Product;
public function existsBySku(Sku $sku): bool;
public function save(Product $product): Product;
```

ここでは Eloquent を使いません。Domain 層は「Product を保存できるものがある」ことだけを知り、実際に MySQL や Eloquent で保存する処理は Day 17 の Infrastructure 層で実装します。

## Day 16: Application Service

Day 16 では、Domain 層を使って在庫操作の手順を組み立てる Application 層を追加しました。

```text
app/Application/
├── Exceptions/
│   └── InventoryApplicationException.php
└── Services/
    └── ProductInventoryService.php
```

### Application Service の責務

`ProductInventoryService` は、次のような「アプリケーション操作の流れ」を担当します。

- SKU 重複を `ProductRepositoryInterface` で確認してから Product を作成する。
- `ProductId` で Product を取得する。
- 在庫増加・減少・直接調整の計算は `Product` Entity に任せる。
- 直接調整の権限チェックは `StockAdjustmentPolicy` に任せる。
- 変更後の Product を `ProductRepositoryInterface` で保存する。

ここでも Eloquent や Controller は登場しません。Application Service は「どの順番で Domain を使うか」を知っていますが、在庫数の計算ルールそのものは持ちません。

Pattern 1 では `ProductController` が HTTP 入力、権限分岐、トランザクション、Model 呼び出し、レスポンスをまとめて扱っていました。Pattern 2 では Controller が後から追加されても、この Application Service を呼ぶだけにできます。

## Day 17: Infrastructure Eloquent Repository

Day 17 では、Domain 層の `ProductRepositoryInterface` を Eloquent で実装する Infrastructure 層を追加しました。

```text
app/Infrastructure/
├── Persistence/
│   └── Eloquent/
│       └── Models/
│           ├── ProductRecord.php
│           └── StockMovementRecord.php
└── Repositories/
    └── EloquentProductRepository.php
```

### Infrastructure の責務

`EloquentProductRepository` は、Eloquent の `ProductRecord` と Domain の `Product` を相互に変換します。

- DB から取得した primitive な値を Value Object に詰め替える。
- Domain `Product` の状態を `products` テーブルへ保存する。
- `Money` は cents として扱い、DB の decimal 文字列へ変換する。
- `ProductRepositoryInterface` を実装し、Application Service からは具象クラスを見せない。

Pattern 1 では Eloquent Model 自体に在庫計算メソッドを持たせました。Pattern 2 では Eloquent は DB の都合を表すだけにし、在庫計算は `Product` Entity に残します。

現在の Repository Interface は Product 集約の保存だけを扱うため、`stock_movements` の監査記録はまだ書き込みません。操作種別や理由を保存するには、Application Service の入力契約か別の永続化契約として明示する必要があります。

## Day 18a: Controller / DI / API 接続

Vue Composable に進む前に、Pattern 2 の backend を外から呼べる形にしました。

```text
app/
├── Http/
│   └── Controllers/
│       └── ProductController.php
├── Providers/
│   └── AppServiceProvider.php
└── ...
routes/
└── api.php
```

### API

```text
GET  /products
POST /products
POST /products/{productId}/stock
```

`ProductController` は HTTP の入力を受け取り、プリミティブ値を Value Object や enum に変換してから `ProductInventoryService` に渡します。

Pattern 1 の `ProductController` は、権限チェックや Eloquent Model の在庫更新呼び出しまで持っていました。Pattern 2 の Controller は、在庫計算や権限判断を直接行わず、Domain / Application 層へ委譲します。

`AppServiceProvider` では、Domain 側の `ProductRepositoryInterface` を Infrastructure 側の `EloquentProductRepository` に bind します。これにより Application Service は具象の Eloquent 実装を知らないまま動けます。

Product API の response は、Vue 側で扱いやすいように `price_amount_in_cents` を明示します。

## Day 18b: Vue Composable への分離

Pattern 2 の frontend では、Pattern 1 のように Vue component へ API 通信と状態管理をまとめず、Composable に分離しました。

```text
resources/js/
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

### 責務

- `types/product.ts`: API の request / response 型。
- `api/productApi.ts`: axios による API 通信。
- `useInventoryProducts.ts`: 商品一覧、作成、在庫更新、loading、message、form state。
- `InventoryApp.vue`: 表示とユーザーイベントの接続。

Pattern 1 の `InventoryApp.vue` は、axios 呼び出し、form state、error extraction、table state、message state をすべて持っていました。Pattern 2 では、UI component が composable を呼ぶだけに近づくため、API の変更や状態管理の変更が component に直接広がりにくくなります。

## Day 19: 単体テスト作成・Pattern 1 との比較

Day 19 では、Pattern 2 の Domain / Application 層に対して単体テストを追加しました。

```text
tests/
├── bootstrap.php
├── Support/
│   └── InMemoryProductRepository.php
└── Unit/
    ├── Application/
    │   └── ProductInventoryServiceTest.php
    └── Domain/
        └── ProductTest.php
```

### テスト対象

- `Product`: 在庫増加、減少、直接調整、0 未満在庫の拒否。
- `StockAdjustmentPolicy`: manager だけが直接調整できるルール。
- `ProductInventoryService`: SKU 重複、商品未存在、在庫操作の流れ。
- `InMemoryProductRepository`: `ProductRepositoryInterface` の差し替え用テストダブル。

Pattern 1 のテストは Feature Test として HTTP、Validation、Eloquent、DB をまとめて通していました。これは MVC 全体の動作確認として分かりやすい一方で、在庫計算や権限ルールだけを小さく検証しにくい構造でした。

Pattern 2 では、在庫計算は Domain、操作手順は Application Service、永続化は Repository Interface に分かれています。そのため DB を使わずに `Product` や `StockAdjustmentPolicy` を直接テストでき、Application Service も in-memory repository に差し替えて確認できます。

### 実行

Pattern 2 の依存関係を入れた後に、次で実行できます。

```bash
cd pattern2-onion
composer install
composer test
```

## 学習のポイント
- **DIP (Dependency Inversion Principle):**
  `Domain/Repositories` にインターフェースを置き、`Infrastructure` でそれを実装することで、ドメインが Eloquent に依存しないようにします。
- **ドメインロジックの集約:**
  Model や Controller に書いていた計算ロジックやバリデーションを、Entity や Value Object に閉じ込めます。
- **Application Service の薄さ:**
  手順の組み立てに集中し、在庫計算や権限ルールを自分では判断しないようにします。

## 比較のヒント
- Pattern 1 (MVC) と比べて、コード量（クラス数）がどう増えたか。
- ビジネスロジックの「場所」が明確になったか。
- 単体テストの書きやすさはどう変わったか。

## 次のステップ
- Day 20: Pattern 3 の UseCase（Interactor）定義。
