# 08. Onion vs Clean の構造的差異

## Day 25: このリポジトリでの比較

Pattern 2 Onion + DDD と Pattern 3 Clean + DDD は、どちらも在庫管理の業務ルールを Laravel / Eloquent から守るための実装です。

共通しているのは次の点です。

- 依存方向は内側へ向ける。
- DI によって具象実装を差し替えられる形にできる。
- 在庫数が 0 未満にならないことなどの業務ルールは Laravel / Eloquent から独立した `Product` に置く。
- 永続化は `ProductRepositoryInterface` の後ろに隠し、内側のコードが Eloquent を直接知らないようにする。

違いは「どちらが正しいか」ではなく、責務をどこまで明示するかにある。

| 観点 | Pattern 2 Onion + DDD | Pattern 3 Clean + DDD |
| --- | --- | --- |
| アプリケーション操作 | `ProductInventoryService` が商品作成、入庫、出庫、直接調整をメソッドとして持つ | `CreateProductInteractor`、`IncreaseStockInteractor`、`DecreaseStockInteractor`、`AdjustStockInteractor` に分ける |
| 入力境界 | Controller が Application Service を直接呼ぶ。入口は実装上は暗黙的 | Controller が `CreateProductInputPort` などの Input Port を呼ぶ |
| 出力境界 | `ProductInventoryService` が Domain `Product` を返し、Controller が JSON へ整形する | Interactor が `ProductOutputData` を `ProductOutputPort` へ渡し、`ProductPresenter` が JSON へ整形する |
| テスト境界 | `ProductInventoryServiceTest` が `InMemoryProductRepository` で永続化を差し替える | `ProductInteractorsTest` が Repository と Output Port を PHPUnit mock に差し替える |
| 見え方 | Domain を中心に、周囲の Application / Infrastructure が支える | UseCase の入力と出力を前面に出し、操作単位が見えやすい |

## リクエストからレスポンスまでの流れ

Pattern 2 は、HTTP の入口である Controller が Application Service を呼び、返ってきた Domain `Product` を Controller 自身が API response shape に変換する。

```text
HTTP Request
  -> ProductController
      -> ProductInventoryService
          -> ProductRepositoryInterface
              <- EloquentProductRepository
          -> Product / StockAdjustmentPolicy
      <- Domain Product
  <- ProductController が JSON response を作る
```

Pattern 3 は、Controller が操作ごとの Input Port に入力を渡す。Interactor は保存後の `Product` を `ProductOutputData` に変換して Output Port へ渡し、`ProductPresenter` がレスポンス用の形を持つ。

```text
HTTP Request
  -> ProductController
      -> CreateProductInputPort / IncreaseStockInputPort / DecreaseStockInputPort / AdjustStockInputPort
          <- CreateProductInteractor / IncreaseStockInteractor / DecreaseStockInteractor / AdjustStockInteractor
              -> ProductRepositoryInterface
              -> Product
              -> ProductOutputPort
                  <- ProductPresenter
  <- ProductController が ProductPresenter の JSON response を返す
```

## 誤解しやすい点

クラス名、Interactor の数、DI の有無、Interface があるかどうかだけでは、Onion か Clean かは決まりません。

このリポジトリの Pattern 2 は、Domain を中心に置き、`ProductInventoryService` が複数操作をまとめて扱います。Pattern 3 は、操作ごとの Interactor と Input / Output Port を明示します。ただし Onion 側でもユースケースごとの入力 DTO、Input Port、Output Port、Presenter を明示していけば、実装スタイルは Clean にかなり近づきます。

つまり重要なのは名前ではなく、依存方向、業務ルールの置き場所、入力と出力の所有者をコードでどう表しているかです。

## 現在の Pattern 3 のランタイム上の未接続部分

Pattern 3 は UseCase / Entity / Controller / Presenter / TypeScript contract / Interactor unit test までを追加していますが、現時点では HTTP から DB まで動く証明はまだありません。

未実装のものは次の通りです。

- Eloquent repository の具象実装。
- Laravel DI wiring。
- API routes。
- HTTP request から Eloquent DB 保存までを通す integration proof。

そのため Pattern 3 の現在の証明範囲は、Interactor を Repository / Output Port mock で直接動かす UseCase 境界までです。

## 使い分け

Onion は、明示する境界型が少なく、Domain を中心に関連操作をまとめやすい。今回の `ProductInventoryService` のように、商品在庫というまとまりでアプリケーション手順を追いたい場合に読みやすい。

Clean は、Input Port、Output Port、Interactor、Presenter という分だけ儀式が増える。一方で、誰が入力を受け、誰が出力を整形し、どの use case をテストしているのかが明確になる。操作単位の変更、出力形式の変更、UseCase 境界のテストを強く意識したい場合に向いている。
