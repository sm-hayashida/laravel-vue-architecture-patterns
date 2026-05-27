# MVCパターンの限界とドメインモデル貧血症

## LaravelにおけるMVCの基本
Laravelは強力なMVC（Model-View-Controller）フレームワークであり、小〜中規模のアプリケーションを素早く構築するのに非常に適している。

- **Model**：Eloquent ORM（Active Recordパターン）。データとデータベース操作を担当。
- **View**：Bladeテンプレートや、Vue／Reactなどのフロントエンド。
- **Controller**：リクエストを受け取り、Modelを操作し、レスポンスを返す。

しかし、ビジネスロジックが複雑になるにつれて、標準的なMVCだけでは「変更に弱い」コードになりやすい。

---

## 1. Fat Controller（太ったコントローラ）
### 起こりがちな現象
「なんでもコントローラに書く」状態。
バリデーション、DB操作、計算ロジック、メール送信、ログ出力が全て`store`メソッドの中に書かれる。

### 問題点
- **再利用性がゼロ**：他のコントローラやバッチ処理から同じロジックを使えない。
- **テストが困難**：HTTPリクエストを擬似的に送らないとロジックのテストができない。
- **読みづらい**：数百行のメソッドになり、何が「重要なルール」なのかわからなくなる。

---

## 2. Fat Model（太ったモデル）
### 起こりがちな現象
「コントローラが太ったらモデルに移動させよう」と言う方針。
ロジックをEloquent Modelのメソッドに移動させる。

### 問題点
- **責務の過剰**：Modelが「DB構造」と「ビジネスルール」の両方を知りすぎている。
- **密結合**：ユニットテストをしようとしていても、必ずDB接続が必要になる（スローテストの原因）。
- **Active Recordの弊害**：保存（`save()`）メソッドがどこからでも呼べてしまうため、意図しないデータ更新を防ぎにくい。

---

## 3. ドメインモデル貧血症（Anemic Domein Model）
これが本プロジェクトにおける**最も重要な概念**の一つ。

### 一言で言うと
**データ（プロパティ）だけを持っていて、振る舞い（ロジック）を持っていないモデルのこと。**
MVCパターンの多くでは、Modelはただの「データの入れ物（DTO）」になり、ロジックは外側のServiceやControllerに漏れ出している。

### 貧血症の例（在庫管理）
```php
//Modelはただの入れ物
class Product extends Model {
    protected $fillable = ['name','stock_quantity'];
}

//ロジックが外側のコントローラにある
class StockController extends Controller {
    puvlic function update(Request $request, $id) {
        $product = Protect::find($id);

        //ロジックが漏れ出している
        $newQuantity = $product->stock_quantity - $request->amount;
        if ($newQuantity < 0) {
            throw new Exception("在庫不足です");
        }

        $product->stock_quantitiy = $newQuantity;
        $product->save();
    }
}
```

### なぜ貧血症が悪いのか？
1. **ドメイン知識の分散**：「在庫がマイナスになってはいけない」と言う重要なルールが、コードのあちこちに散らばる。
2. **データの不整合**：ルールを知らない別の開発者が、`$product->decrement('stock_quantity')`を直接読んでしまい、チェックをバイパスするリスクがある。
3. **モデルが「語らない」**：コードを読んでも、そのアプリケーションがどんなビジネスルールを持っているのかがモデルから読み取れない。

---

## 4. 本プロジェクトでの「Pattern 1」の位置付け

次から実装する** Pattern1（MVC）**では、あえてこの「貧血症」や「密結合」な状態を再現する。

- Controllerにロジックを詰め込む、あるいはServiceに手続き型のコードを書く。
- Eloquent Modelを直接操作する。
- DBとロジックが密結合で、テストが書きにくい状態。

これを基準点とすることで、のちのOnionやClean Architectureが　**「いかにしてビジネスルールを保護し、変更に強くしているか」**を対比させていく。

---

## まとめ
- MVCはシンプルだが、複雑なビジネスロジックを扱うには **「置き場所が足りない」**。
- **ドメインモデル貧血症**を避け、データとロジックを一致させることが、DDDやクリーンアーキテクチャへの第一歩となる。

---

## Day 13: Pattern 1 実装後の振り返り

`pattern1-mvc/` では、Laravel 標準 MVC のまま在庫管理を実装した。小さな機能であれば、ルーティング、バリデーション、Eloquent、JSON レスポンスを素直につなげるだけで動くため、実装速度は高い。

一方で、在庫管理のルールはすでに複数の場所へ分散している。

- `ProductController`: リクエストバリデーション、簡易的な権限チェック、トランザクション、レスポンス整形。
- `Product` Eloquent Model: 在庫の増加、減少、直接調整、在庫変動履歴の作成。
- `InventoryApp.vue`: 画面状態、フォーム状態、API 呼び出し、エラー表示、一覧更新。

### 変更の難しさ

例えば「在庫を直接調整できる条件」を `manager` だけでなく「棚卸し期間中の担当者」にも広げる場合、現在の実装では Controller の条件分岐を変更する必要がある。しかし、このルールは本来 HTTP の都合ではなく在庫操作の業務ルールである。

また、在庫数を減らせない条件は `Product` Model にある。つまり、在庫操作という1つの業務概念の中で、権限ルールは Controller、数量ルールは Model に分かれている。この分散が、機能追加時に「どこを見れば正しいルールが分かるのか」を曖昧にする。

### テストの書きにくさ

現在のテストは Feature Test として書いている。HTTP リクエスト、Laravel のバリデーション、Eloquent、DB トランザクションまで含めて確認できるため、MVC の動作確認としては分かりやすい。

ただし、純粋に「在庫数の計算だけ」「直接調整の可否だけ」を速くテストするには向いていない。重要なルールが Eloquent と Controller に結びついているため、DB や HTTP から切り離した単体テストを書きにくい。

### Vue 側の責務集中

Day 12 の `InventoryApp.vue` は、商品作成、在庫更新、一覧取得、エラー整形を1つのコンポーネントにまとめている。画面が小さい間は読みやすいが、入力項目や操作が増えると、UI の見た目と API 通信の詳細が混ざっていく。

後続の Pattern 2 / Pattern 3 では、Composable や型定義を使って、画面の責務とデータ取得の責務を分けた場合の差を比較できる。

### 次の Pattern 2 / Pattern 3 で確認したいこと

- Onion Architecture では、在庫数や数量のルールを Entity / Value Object に移すことで、業務ルールを Laravel から保護できるか。
- Repository Interface によって、Domain 層が Eloquent に依存しない形を作れるか。
- Clean Architecture では、在庫操作を UseCase として表現することで、Controller の責務を入力変換とレスポンス返却に絞れるか。
- Vue 側では、Composable に API 呼び出しと状態管理を分けることで、コンポーネントを画面表示に集中させられるか。

Pattern 1 は「悪い実装」ではなく、Laravel で小さく素早く作るための自然な出発点である。ただし、業務ルールが増えるほど、責務の境界が曖昧になり、変更箇所とテスト範囲が広がりやすい。この課題を基準点として、次の Onion + DDD 実装に進む。

## Day 19 で見えた Pattern 2 との差

Pattern 2 では、同じ在庫ルールを Domain / Application の単体テストとして確認できるようになった。

Pattern 1 の Feature Test は「API と DB を含めた全体が動くか」を見るには向いている。一方、在庫計算、直接調整権限、SKU 重複のようなルールだけを確認したいときも Controller / Eloquent / DB を通る。

Pattern 2 では、在庫計算は `Product`、権限判断は `StockAdjustmentPolicy`、操作手順は `ProductInventoryService` に分かれている。そのため、DB を使わずに小さな単位でルールを検証できる。MVC よりファイル数は増えるが、「何を壊したか」「どこを直せばよいか」がテストから追いやすくなる。
