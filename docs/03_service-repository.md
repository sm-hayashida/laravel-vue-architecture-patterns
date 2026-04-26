# Service / Repository パターンの役割と限界

  ## はじめに
  Laravel でアプリケーションを作っていると、Controller にビジネスロジックを書きすぎてしまうことがよくある。

  たとえば在庫管理であれば、

  - リクエストの受け取り
  - 入力チェック
  - 商品の取得
  - 在庫数の計算
  - 権限チェック
  - 保存処理
  - レスポンス生成

  といった処理が、ひとつの Controller メソッドに集まりやすい。

  この状態を少し改善するためによく使われるのが、**Service / Repository パターン**である。
  ただし、これはあくまで「責務を分けるための整理手法」であり、DDD そのものではない。
  今日は、Service / Repository パターンが何を解決し、どこで限界を迎えるのかを整理する。

  ---

  ## 1. Service パターンとは何か

  ### Service の役割
  Service は、**複数の処理をまとめてアプリケーションの操作手順を表現する層**として使われることが多い。

  Controller からロジックを追い出し、

  - 計算処理
  - 条件分岐
  - 複数のモデル操作
  - トランザクション
  - 外部サービス呼び出し

  などを Service に集めることで、Controller を薄くすることができる。

  ### 例
  ```php
  final class StockService
  {
      public function decrease(int $productId, int $amount): void
      {
          $product = Product::findOrFail($productId);

          if ($amount <= 0) {
              throw new InvalidArgumentException('減少数は1以上である必要があります。');
          }

          if ($product->stock_quantity < $amount) {
              throw new DomainException('在庫不足です。');
          }

          $product->stock_quantity -= $amount;
          $product->save();
      }
  }
  ```

  Controller はこのように薄くできる。

```php
  final class StockController extends Controller
  {
      public function update(Request $request, int $id, StockService $stockService)
      {
          $stockService->decrease($id, (int) $request->input('amount'));

          return response()->json(['message' => '在庫を更新しました。']);
      }
  }
```
  ### Service を導入するメリット

  - Controller が短くなり、HTTP の責務に集中できる
  - ロジックの再利用先を作りやすい
  - ある程度テストしやすくなる
  - 処理の流れを1か所に集めやすい

  MVC の最初の改善としては、非常に実用的な方法である。

---

  ## 2. Repository パターンとは何か

  ### Repository の役割

  Repository は、データ取得・保存の詳細を隠すための窓口である。

  Eloquent を直接 Controller や Service から呼ぶのではなく、Repository を経由することで、

  - どのようにデータを取得するか
  - どのように保存するか
  - どの条件で検索するか

  といった永続化の詳細をまとめることができる。

  ### 例
```php
  interface ProductRepository
  {
      public function findById(int $productId): ?Product;
      public function save(Product $product): void;
  }
```
  実装側では Eloquent を使う。
```php
  final class EloquentProductRepository implements ProductRepository
  {
      public function findById(int $productId): ?Product
      {
          return Product::find($productId);
      }

      public function save(Product $product): void
      {
          $product->save();
      }
  }
```
  Service からは Repository を使う。
```php
  final class StockService
  {
      public function __construct(
          private ProductRepository $productRepository
      ) {}

      public function decrease(int $productId, int $amount): void
      {
          $product = $this->productRepository->findById($productId);

          if ($product === null) {
              throw new RuntimeException('商品が見つかりません。');
          }

          if ($amount <= 0) {
              throw new InvalidArgumentException('減少数は1以上である必要があります。');
          }

          if ($product->stock_quantity < $amount) {
              throw new DomainException('在庫不足です。');
          }

          $product->stock_quantity -= $amount;

          $this->productRepository->save($product);
      }
  }
```
  ### Repository を導入するメリット

  - DBアクセス処理をまとめられる
  - 取得方法の変更に対応しやすい
  - Service が永続化の詳細を知らずに済む
  - テスト時に差し替えやすくなる

---

  ## 3. Service / Repository パターンで改善されること

  Service / Repository を導入すると、少なくとも次の改善が見込める。

  ### 1. Controller が薄くなる

  Controller は HTTP を受け取って Service に委譲するだけになるため、役割が明確になる。

  ### 2. 永続化処理が散らばりにくくなる

  find() や save() が各所に直接書かれるより、Repository にまとめた方が見通しがよい。

  ### 3. テストの粒度を分けやすくなる

  Controller テストと、Service のロジックテストを分離しやすくなる。

  ### 4. 「どこに何を書くか」が少し整理される

  MVC の素朴な状態よりは、責務の置き場所を考えやすくなる。

  つまり Service / Repository パターンは、密結合な MVC を一段階整理するための現実的な改善策と言える。

---

  ## 4. それでも残る限界

  ここが重要である。
  Service / Repository パターンは便利だが、これだけでは設計上の問題がまだ残る。

  ### 1. Service が太りやすい

  Controller からロジックを移しただけでは、今度は Service が巨大になりやすい。

  たとえば、

  - 在庫減少
  - 在庫補充
  - 権限チェック
  - 履歴記録
  - 通知送信
  - 監査ログ

  まで全部ひとつの Service に集まると、結局「Fat Service」になる。

  ### 2. ロジックがドメインモデルに乗らない

  たとえ Repository を使っていても、在庫不足チェックや数量の妥当性確認がすべて Service に書かれているなら、モデルはただのデータの器のままである。

  これは前回見た ドメインモデル貧血症 に近い状態である。

  ### 3. Eloquent Model をそのままドメインとして扱いがち

  Repository を用意していても、戻り値がそのまま Eloquent Model であれば、ドメイン知識と DB モデルの境界はあいまいなままである。

  結果として、

  - DB のカラム構造に引っ張られる
  - save() をどこからでも呼べる
  - フレームワーク依存が深い

  という問題が残る。

  ### 4. 「業務ルールの意味」が型に出てこない

  Service / Repository パターンだけでは、値の意味を int や string のまま扱いがちである。

  たとえば、

  - 商品ID
  - 在庫数
  - 権限
  - 金額

  をすべて基本型で扱うと、「その値が何で、どんな制約を持つのか」がコードから読み取りにくい。

---

  ## 5. Service / Repository は DDD とどう違うのか

  ここを混同しやすい。

  ### Service / Repository パターン

  - 処理を整理するための実装パターン
  - Controller を薄くしたい
  - DBアクセスをまとめたい
  - 実務上の見通しをよくしたい

  ### DDD 戦術的設計

  - ドメイン知識をコードでどう表現するかが中心
  - Entity に振る舞いを持たせる
  - Value Object で値に意味を持たせる
  - Repository はドメインの永続化境界として扱う

  つまり、Service / Repository は 責務分離の入り口 ではあるが、DDD の本質はそれより一歩深い。
  DDD では「どこに置くか」だけでなく、業務ルールそのものをどうモデル化するかを重視する。

---

  ## 6. 在庫管理の例で見る違い

  ### Service / Repository だけの状態
```php
  public function decrease(int $productId, int $amount): void
  {
      $product = $this->productRepository->findById($productId);

      if ($product === null) {
          throw new RuntimeException('商品が見つかりません。');
      }

      if ($amount <= 0) {
          throw new InvalidArgumentException('減少数は1以上である必要があります。');
      }

      if ($product->stock_quantity < $amount) {
          throw new DomainException('在庫不足です。');
      }

      $product->stock_quantity -= $amount;
      $this->productRepository->save($product);
  }
```
  この形でも MVC よりは良いが、重要なルールはまだ Service にある。

  ### DDD に近づけた状態
```php
  $product = $this->productRepository->findById($productId);

  $product->decreaseStock($amount);

  $this->productRepository->save($product);
```
  この形では、在庫を減らすルールは Product 自身が持つ。
  Service は「処理の流れ」を担当し、Entity は「業務ルール」を担当する。
  この役割分担が、DDD の重要な考え方である。

  ———

  ## 7. 本プロジェクトでの位置づけ

  本プロジェクトでは、Service / Repository パターンは次のような中間地点として考えると理解しやすい。

  ### Pattern 1: MVC

  - Controller や Model にロジックが集まりやすい
  - 密結合で、変更に弱い
  - 比較のための基準点

  ### Service / Repository

  - 責務を少し整理する
  - Controller を薄くする
  - DBアクセスを分離する
  - ただし、まだドメインの表現としては不十分なことが多い

  ### Onion / Clean + DDD

  - Entity や Value Object に業務ルールを閉じ込める
  - Repository を永続化境界として扱う
  - フレームワーク依存を外側へ追い出す
  - テストしやすく、変更に強い構造を目指す

  つまり、Service / Repository はゴールではなく、よりよい設計へ向かう途中の整理段階として理解するのがよい。

---

  ## まとめ

  - Service は、処理の流れやアプリケーション操作をまとめるための整理手法である。
  - Repository は、データ取得・保存の詳細を隠すための窓口である。
  - このパターンによって、Controller の肥大化や DBアクセスの散在はある程度改善できる。
  - しかし、それだけでは業務ルールが Service に集中し、Fat Service や ドメインモデル貧血症 が残りやすい。
  - DDD ではさらに一歩進んで、Entity や Value Object にドメイン知識を持たせることが重要になる。

  次のステップでは、Entity・Value Object・Repository をどう使い分けるかという DDD 戦術的設計 を整理していきたい。