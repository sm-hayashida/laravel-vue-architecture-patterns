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

## 次のステップ

- Day 21: Input / Output Port（Interface）の定義。
