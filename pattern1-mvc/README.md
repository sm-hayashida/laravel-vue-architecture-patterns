# Pattern 1: Laravel Standard MVC

## 概要
Laravel 標準の MVC 構成を採用した、在庫管理アプリケーションのベースライン実装です。

このパターンでは、あえて Repository、UseCase、Domain Entity などの追加レイヤーを導入せず、Eloquent Model、Controller、Request、Vue を Laravel の標準的な責務分担で組み合わせます。

## 目的
- Pattern 2 / Pattern 3 と比較するための基準を作る。
- 小さく作る場合の実装速度とわかりやすさを確認する。
- 後続の Day 11 以降で、Model / Controller にロジックが寄りやすい構造を実際に観察する。

## セットアップ
```bash
cd pattern1-mvc
composer install
cp .env.example .env
php artisan key:generate
```

## Sailで軽く動作確認する

Docker Desktop を起動した状態で実行します。

```bash
cd pattern1-mvc
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

API の簡単な確認:

```bash
curl -X POST http://localhost/api/products \
  -H 'Content-Type: application/json' \
  -d '{"sku":"SKU-001","name":"Sample Product","stock_quantity":10,"price":1200}'

curl -X POST http://localhost/api/products/1/stock \
  -H 'Content-Type: application/json' \
  -d '{"type":"out","quantity":3,"reason":"Shipped","operator_role":"staff"}'

curl http://localhost/api/products
```

ブラウザでは `http://localhost` を開くと、Day 12 の簡易在庫画面を確認できます。Vite の開発サーバーを使う場合は別ターミナルで次を実行します。

```bash
./vendor/bin/sail npm run dev
```

終了する場合:

```bash
./vendor/bin/sail down
```

SQLite で migration を確認する場合:

```bash
touch database/database.sqlite
DB_CONNECTION=sqlite DB_DATABASE="$(pwd)/database/database.sqlite" php artisan migrate
```

## 初期スキーマ

### products
- `sku`: 商品管理番号。ユニーク制約を持つ。
- `name`: 商品名。
- `stock_quantity`: 現在庫数。
- `price`: 商品価格。

### stock_movements
- `product_id`: 対象商品。
- `type`: 入庫、出庫、調整などの在庫変動種別。
- `quantity`: 変動数量。
- `reason`: 変動理由。任意。

## 学習のポイント
- MVC では Controller / Model / Request のどこに在庫ルールを書くべきか迷いやすい。
- `stock_quantity` のようなプリミティブ値は扱いやすい一方、不正状態を防ぐ責務が分散しやすい。
- 後続の Onion / Clean では、この在庫ルールを Entity、Value Object、UseCase に移した時の差分を比較する。

## Day 11: MVCでの在庫ロジック実装

追加した API:

- `GET /api/products`: 商品一覧。
- `POST /api/products`: 商品作成。
- `POST /api/products/{product}/stock`: 在庫増加、在庫減少、直接調整。

Pattern 1 では、あえて次の責務を Laravel 標準 MVC の中に置いています。

- `Product` Model: 在庫数の増減、直接調整、在庫変動履歴の作成。
- `ProductController`: リクエストバリデーション、簡易的な権限チェック、トランザクション、HTTP レスポンス。

この時点で、在庫ルールは Model と Controller に分散しています。実装は速い一方、後続の Pattern 2 / Pattern 3 で比較する「ドメインルールをどこに置くべきか」が見えやすい状態です。

## Day 12: Vueベタ書き画面

`resources/js/components/InventoryApp.vue` に、商品作成、商品一覧、在庫更新の画面処理をまとめています。

Pattern 1 の比較用として、あえて次の責務を1コンポーネントに寄せています。

- フォーム入力状態。
- axios による API 呼び出し。
- エラーメッセージ整形。
- 商品一覧テーブルの表示状態。

後続の Pattern 2 / Pattern 3 では、このような処理を Composable や型定義に分けた場合の見通しやすさを比較します。

## Day 13: Pattern 1の振り返り

MVC baseline は、Laravel の標準機能だけで在庫管理を素早く作れることを確認するための実装です。

一方で、在庫操作の責務は次のように分散しています。

- `ProductController`: バリデーション、権限チェック、トランザクション、レスポンス。
- `Product`: 在庫数の計算、在庫変動履歴の作成。
- `InventoryApp.vue`: フォーム状態、API 呼び出し、エラー表示、一覧更新。

この構成は小さいうちは扱いやすいですが、業務ルールが増えると変更箇所が広がりやすくなります。Pattern 2 では Domain 層に在庫ルールを寄せ、Pattern 3 では UseCase を中心に操作単位を明確にすることで、この課題との差分を確認します。
