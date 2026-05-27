# 06. オニオンアーキテクチャ (Onion Architecture)

## 概要
オニオンアーキテクチャは、「ビジネスロジック」をシステムの中心に据え、データベースやUIといった「技術的詳細」をその外側に配置する設計パターンです。
中心にある大切なルール（ドメイン）を、外側の変化から守るために、依存関係は常に **「外から内へ」** の一方向に限定します。

## レイヤー構造と責務（役割の例え）

システムを「演劇」に例えると、各レイヤーの責任が明確になります。

### 1. Domain Model (核：役者)
- **技術用語:** **エンティティ、値オブジェクト**
- **役割:** ビジネスルールそのもの。
- **例え:** **「役者」**。舞台上で「どう振る舞うべきか」というルール（在庫はマイナスにならない、増減計算など）を自分自身の中に持っている。
- **制約:** 舞台の外（データベースやWeb）のことは一切知らない。

### 2. Domain Services (核：舞台装置の指示)
- **技術用語:** **ドメインサービス**
- **役割:** 複数の役者にまたがるルール。
- **例え:** **「特殊効果や舞台装置のルール」**。役者一人（単一のエンティティ）では完結しない演出（倉庫間の在庫移動など）を扱う。

### 3. Application Services (内側：脚本家・演出家)
- **技術用語:** **アプリケーションサービス（ユースケース）**
- **役割:** 役者たちを動かして、一つの物語（機能）を完結させる。
- **例え:** **「脚本家・演出家」**。役者に「いつ、誰が、何をするか」の指示を出す。
- **重要な考え方（薄いサービス）:**
    - 脚本家自身は「演技（計算や判断）」をしない。
    - 「リポジトリから**役者(Domain)**を呼ぶ」→「**役者**に演技（計算）をさせる」→「結果をリポジトリに記録する」という **手順（脚本）** を書くだけに徹する。
    - 脚本家が演技までし始めると、サービスが太る（責務過多）原因になる。

### 4. UI / Infrastructure (最外周：劇場・窓口)
- **技術用語:** **コントローラー、リポジトリ実装（Eloquent等）**
- **役割:** 外界との接続や、具体的な道具の提供。
- **例え:** **「劇場の入り口（コントローラー）」や「小道具の倉庫（リポジトリ実装）」**。
- **コントローラーの立ち位置:**
    - ここは最外周。HTTPリクエスト（Web）という特定の技術を扱う「玄関口」。
    - 役割は「お客さんの要望を聞き、適切な**脚本家(Application Service)**を呼ぶ」ことだけ。

---

## 核心：依存性の逆転原則 (DIP)

**「脚本家（Application Service）」** は、特定の **「MySQL倉庫（Infrastructure）」** を直接見に行きません。
代わりに「こういう道具が出せる倉庫なら何でもいい（インターフェース）」という **「道具出しの約束（Domain/Application層に置く）」** を提示します。

- **従来:** `脚本家` → `MySQL倉庫`（MySQLが壊れると脚本が書けない）
- **DIP:** `脚本家` → `[道具出しの約束(Interface)]` ← `MySQL倉庫`
    - これにより、中のロジックを保ったまま、外側の技術（MySQL, PostgreSQL, Mockなど）を自由に入れ替えられるようになります。

---
**本プロジェクトの方針:**
Pattern 2 では、コントローラーを「薄い玄関口」にし、ビジネスロジックを「役者（ドメインモデル）」に、手順を「脚本（アプリケーションサービス）」に徹底して分離する。

---

## Day 14: Domain層から始める理由

Pattern 2 では、最初に `pattern2-onion/app/Domain/` を作成し、Laravel や Eloquent から独立した PHP クラスとして在庫ルールを表現した。

Pattern 1 では、在庫を直接調整できるかどうかは Controller、在庫数がマイナスにならないことは Eloquent Model に置いていた。これは小さく作るには速いが、業務ルールの場所が分散する。

Onion Architecture では、まず中心に次のようなドメイン概念を置く。

- `Product`: 在庫操作を持つ集約ルート。
- `StockQuantity`: 0 未満を許可しない現在庫数。
- `MovementQuantity`: 0 以下を許可しない入出庫数量。
- `Sku` / `ProductName` / `Money`: プリミティブ値をそのまま渡さず、意味と制約を持たせる値オブジェクト。

この段階では DB 保存や HTTP レスポンスは扱わない。外側の Application / Infrastructure は後から追加し、依存方向を「外から内へ」に保つ。

---

## Day 15: Domain Service と Repository Interface

Day 15 では、Domain 層に `StockAdjustmentPolicy` と `ProductRepositoryInterface` を追加した。

`StockAdjustmentPolicy` は Domain Service として、「直接在庫調整は manager だけができる」というルールを持つ。このルールは `Product` 自身の状態だけでは判断できない。操作する人の役割が必要になるため、Entity に押し込むより Domain Service として独立させた方が責務が明確になる。

`ProductRepositoryInterface` は、Product 集約を保存・取得するための約束である。ここで重要なのは、Interface は Domain 側に置き、Eloquent の実装は外側の Infrastructure 側に置くこと。

```text
Application Service -> ProductRepositoryInterface <- EloquentProductRepository
```

この形にすると、Application Service は「Product を保存できるもの」に依存するだけで、MySQL や Eloquent の詳細を知らずに済む。これが DIP によって Domain を外側の技術詳細から守る、という意味になる。

## Day 16: Application Service で手順を組み立てる

Day 16 では `pattern2-onion/app/Application/Services/ProductInventoryService.php` を追加した。

Application Service は、在庫操作の「手順」を担当する。

```text
ProductInventoryService
  -> ProductRepositoryInterface から Product を取得
  -> Product Entity に在庫計算を依頼
  -> StockAdjustmentPolicy に権限判断を依頼
  -> ProductRepositoryInterface で保存
```

ここで重要なのは、Application Service が在庫計算や権限ルールそのものを抱え込まないこと。

Pattern 1 の `ProductController` は、HTTP 入力、権限分岐、DB トランザクション、Eloquent Model 呼び出し、レスポンス生成をまとめて持っていた。Pattern 2 では、Controller を後で追加しても、Controller は Application Service を呼び出すだけでよい。

Application Service は Domain の使い方を知っているが、Eloquent や Laravel の Request / Response は知らない。この境界によって、Domain と Application のテストを HTTP や DB から切り離しやすくなる。

## Day 17: Infrastructure が Domain Interface を実装する

Day 17 では `EloquentProductRepository` を追加し、Domain 層に置いた `ProductRepositoryInterface` を Infrastructure 層で実装した。

依存方向は次の形になる。

```text
Application Service
  -> ProductRepositoryInterface  (Domain)
       <- EloquentProductRepository  (Infrastructure)
```

`EloquentProductRepository` は、Eloquent の `ProductRecord` を Domain の `Product` に変換する。逆に保存時は、Domain `Product` の Value Object から primitive な DB 値へ戻す。

ここで Pattern 1 との違いがはっきりする。Pattern 1 の Eloquent `Product` は、DB の行でありながら在庫計算メソッドも持っていた。Pattern 2 の `ProductRecord` は DB の行を表すだけで、在庫計算は Domain `Product` に残る。

つまり Infrastructure は「DB と Domain の翻訳係」であり、業務ルールの置き場所ではない。

## Day 18a: Controller は HTTP の翻訳係にする

Vue Composable に進む前に、Pattern 2 の API 経路を追加した。

```text
HTTP Request
  -> ProductController
  -> ProductInventoryService
  -> ProductRepositoryInterface
  <- EloquentProductRepository
```

`ProductController` は、HTTP request の値を `Sku`、`ProductName`、`StockQuantity`、`MovementQuantity`、`Money`、`ProductId`、enum に変換する。変換後は `ProductInventoryService` に渡すだけで、在庫計算や直接調整の権限判断は行わない。

Pattern 1 では Controller の中に「直接調整は manager だけ」という分岐があった。Pattern 2 ではその判断は `StockAdjustmentPolicy` に残る。Controller は例外を HTTP status と JSON に変換するだけでよい。

DI は `AppServiceProvider` で行う。

```text
ProductRepositoryInterface -> EloquentProductRepository
```

この bind によって、Application Service は Eloquent の具象クラスを知らず、Interface だけに依存する。

## Day 19: Domain / Application を単体テストする

Pattern 2 の最後に、Domain 層と Application 層へ単体テストを追加した。

Pattern 1 では在庫ルールの確認に Feature Test を使った。HTTP request を投げ、Controller の validation と Eloquent の保存まで通るため、画面や API に近い安心感はある。ただし「在庫を 3 から 4 減らすと失敗する」「staff は直接調整できない」という小さなルールだけを確認したい場合でも、Laravel の HTTP / DB 文脈を通る。

Pattern 2 では、ルールの置き場所が分かれているためテストの粒度も分けられる。

- `ProductTest`: 在庫増減と 0 未満在庫の拒否を Entity 単体で確認する。
- `StockAdjustmentPolicy`: manager / staff の権限判断を Domain Service 単体で確認する。
- `ProductInventoryServiceTest`: `ProductRepositoryInterface` を in-memory 実装に差し替え、Application Service の手順だけを確認する。

ここで重要なのは、Repository Interface がただの抽象化ではなく「Application Service を DB から切り離してテストできる境界」になっていること。Pattern 1 よりクラス数は増えるが、変更したルールに近いテストだけを実行しやすくなる。

Clean Architecture では、次にこの考え方をさらに進めて、操作単位を UseCase / Interactor として明示し、Input Port / Output Port で入出力境界も揃えていく。
