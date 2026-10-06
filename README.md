# Laravel + Vue アーキテクチャ・パターン比較

在庫管理を題材に、Laravel と Vue で3通りの設計を比較した学習用リポジトリです。

商品を登録する、入庫する、出庫する。同じ処理でも、Laravel の標準的な構成で書く場合と、業務ルールを独立させて書く場合では、コードの置き場所やテストの書き方が変わります。その違いを、実装を並べて確認できるようにしています。

| 実装 | 比較したかったこと |
| --- | --- |
| [Laravel Standard MVC](pattern1-mvc/) | Laravel の Model と Controller を使うと、どこまでシンプルに書けるか |
| [Onion Architecture + DDD](pattern2-onion/) | 在庫のルールを Laravel や DB の処理から切り離すと、何が変わるか |
| [Clean Architecture + DDD](pattern3-clean/) | 操作ごとに処理を分け、入力と出力にもインターフェースを設けると、何が変わるか |

## コードを比べてわかる違い

### MVC：処理を少ないファイルで追える

MVC では、Eloquent の `Product` に在庫数のチェック、更新、履歴の保存をまとめています。Controller は入力や権限の確認、トランザクション、レスポンスを受け持ちます。Vue 側も、画面表示と API 呼び出し、状態の管理を近くに置いています。

Laravel の仕組みをそのまま使えるので、小さな機能を作るときには扱いやすい構成です。一方、在庫数を減らすメソッドを呼ぶと DB への保存まで進みます。「在庫が足りないときに出庫できないか」だけを確かめたい場合にも、保存処理とのつながりを考える必要があります。

### Onion：在庫のルールと保存処理を分ける

Onion では、在庫数を減らす処理を Domain の `Product` に置き、DB への保存は Repository に任せています。`ProductInventoryService` を読むと、「商品を取得する → 在庫数を変える → 保存する」という手順が追えます。権限による在庫調整の可否は `StockAdjustmentPolicy`、金額や数量の値のチェックは Value Object が担当します。

Domain は Eloquent を知らず、Eloquent を使う Infrastructure 側が Domain のインターフェースに合わせます。この向きに依存を揃えることで、在庫のルールを DB から切り離して扱えます。テストでも保存先をメモリ上の実装に差し替えられます。

ただし、MVC に比べるとファイルが増え、Eloquent のデータと Domain のオブジェクトを変換する処理も必要になります。在庫のルールを独立して変更・テストしたいときには役立ちますが、単純な CRUD でもこの構成にするべきかは考えどころです。

### Clean：操作の入口と結果の渡し先も分ける

今回の Clean では、商品登録や入庫、出庫をそれぞれ別の Interactor に分けました。Controller は Input Port を通して処理を呼び出し、Interactor は結果を Output Port に渡します。JSON の形に整えるのは Presenter の仕事です。

Onion の実装では `ProductInventoryService` が `Product` を返し、Controller が JSON に変換していました。Clean では、その間に `ProductOutputData` と Output Port を挟んでいます。Interactor が Presenter の具体的な実装を知らずに済むので、テストでは Repository と出力先の両方をモックに置き換えられます。

出庫処理だけを見たいときに、対応する Interactor を開けばよいのはわかりやすいところです。その分、処理全体を追うには複数のファイルを見る必要があり、インターフェースと実装を結びつける設定も増えます。操作ごとの変更や、結果の返し方を分けて扱いたい場面で、この手間をかける意味が出てきます。

## 振り返り

今回の比較で軸にしたいのは、「在庫のルールを変えたいときに、どのコードまで読む必要があるか」です。MVC は少ないファイルに処理がまとまっています。Onion と Clean はファイルが増える代わりに、在庫のルール、保存、HTTP の処理を分けて追えます。

小さな機能なら、まず MVC の構成で十分かを考えます。在庫のルールが増え、DB や画面から切り離してテストしたくなったら、Onion のような分け方を検討します。さらに操作ごとの入口や出力先を明確にしたいなら、今回の Clean の構成が参考になります。

Onion と Clean の分け方は、このリポジトリで採った一例です。Onion でも操作ごとにクラスを分けたり、入力と出力のインターフェースを用意したりできます。クラス名や数だけで分類するより、何を分けるためにその構成を選んだのかを説明できるようにしたいです。

## まだできていないこと

Pattern 3 は、Entity、UseCase、Input/Output Port、Controller、Presenter と、TypeScript 側のデータ型まで用意しています。単体テストでは Repository と Output Port をモックに置き換えて、Interactor の処理を確認する構成です。

ただし、Eloquent を使う Repository の実装、Laravel の DI 設定、API ルートはまだありません。HTTP リクエストを受けて DB に保存するまでを通した結合テストも未実装です。現時点では、3つとも同じように動かせる完成品としては比較できません。Pattern 3 について比較できるのは主にコードの構造と UseCase の単体テストです。

## プロジェクト構成

```
├── docs/                # アーキテクチャやDDDの学習ログ
├── pattern1-mvc/        # 実装①: Laravel標準
├── pattern2-onion/      # 実装②: オニオン + DDD
└── pattern3-clean/      # 実装③: クリーン + DDD
```

## 学習ログ

- [Onion と Clean の比較](docs/08_onion-vs-clean.md)：Day 25。実際のクラスをたどりながら、リクエストからレスポンスまでの流れを比べています。
- Day 26：3つの実装を振り返り、この README にまとめました（2026/09/14完了）。

## 開発環境

- PHP 8.x / Laravel 10.x
- Node.js / Vue.js 3 / TypeScript
- Docker (Laravel Sail)

---
*本プロジェクトは社内能力行動評価用ポートフォリオとして作成されています。*
