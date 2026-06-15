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

## 次のステップ

- Day 22: Controller / Presenter の実装。
