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

## 学習のポイント
- **DIP (Dependency Inversion Principle):**
  `Domain/Repositories` にインターフェースを置き、`Infrastructure` でそれを実装することで、ドメインが Eloquent に依存しないようにします。
- **ドメインロジックの集約:**
  Model や Controller に書いていた計算ロジックやバリデーションを、Entity や Value Object に閉じ込めます。

## 比較のヒント
- Pattern 1 (MVC) と比べて、コード量（クラス数）がどう増えたか。
- ビジネスロジックの「場所」が明確になったか。
- 単体テストの書きやすさはどう変わったか。

## 次のステップ
- Day 16: Application Service で在庫操作の手順を組み立てる。
- Day 17: Infrastructure 層で Eloquent Repository を実装する。
