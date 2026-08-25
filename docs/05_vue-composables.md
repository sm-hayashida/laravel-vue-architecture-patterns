# 05. Vue Composables と フロントエンドの責務

## 概要
Vue 3 の Composition API によって導入された **Composables** は、コンポーネントからステートフルなロジックを切り出し、再利用可能にするための仕組みです。
本プロジェクトでは、フロントエンドにおいても「関心の分離（SoC）」を徹底するため、UI（見た目）とロジック（振る舞い）の境界線として Composable を活用します。

## フロントエンドにおける責務の分離

現代のフロントエンド開発では、コンポーネントが肥大化しがちです。Composable を導入することで、以下のように責務を明確に分けます。

### 1. Vue Component (UI レイヤー)
- **役割:** 見た目の構造（HTML）、スタイル（CSS）、ユーザーイベントの検知。
- **原則:** 「どう表示するか」に専念する。ビジネスロジックや API 通信の詳細は知らず、Composable から提供されるデータや関数を利用するだけに留めます（Thin Component）。

### 2. Vue Composable (ロジックレイヤー)
- **役割:** 状態（ref, reactive）の管理、API 通信、データの加工、バリデーション。
- **原則:** 「何をするか」を記述する。特定の UI 要素（DOM）に依存せず、純粋なデータと関数のセットとして実装します。

---

## なぜ Composable を使うのか

### 1. 再利用性 (Reusability)
在庫管理の「在庫を増やす」というロジックが、一覧画面と詳細画面の両方で必要な場合、Composable に切り出しておけば、両方のコンポーネントで同じロジックを共有できます。

### 2. テスト容易性 (Testability)
UI コンポーネントのテストはレンダリングが伴うため重くなりがちですが、Composable は純粋な JavaScript/TypeScript の関数としてテストできるため、ロジックの検証を高速かつ網羅的に行えます。

### 3. 可読性の向上 (Readability)
コンポーネント内の `setup`（または `<script setup>`）が数百行に及ぶのを防ぎ、「何のためのロジックか」という意図ごとにファイルを分割できるため、コードの全容が把握しやすくなります。

---

## 本プロジェクトにおけるアーキテクチャ別の扱い

| 特徴 | Pattern 1 (MVC) | Pattern 2/3 (Onion/Clean) |
| :--- | :--- | :--- |
| **ロジックの場所** | コンポーネント内の `script` | 専用の **Composable** |
| **API 通信** | `onMounted` 等で直接 axios を呼ぶ | Composable 内でカプセル化 |
| **状態管理** | `data` や `ref` をコンポーネントで定義 | Composable が状態を保持・公開 |
| **疎結合度** | 密結合（UI とロジックが一体） | 疎結合（ロジックを差し替え可能） |

### Pattern 2/3 での Composable の命名
「何をするか」に基づいた命名を行います。
- `useStockManagement.ts` (在庫管理全般)
- `useProductSearch.ts` (商品検索)
- `useAuth.ts` (認証)

---

## プレゼンテーション層としての Composable

クリーンアーキテクチャの文脈では、フロントエンド（Vue アプリ全体）は「最外周（Frameworks & Drivers）」に位置しますが、その中でもさらに **Interface Adapters** としての役割を Composable に持たせることが可能です。

- **Input:** ユーザーの操作を受け取り、API リクエスト（ユースケースの呼び出しに相当）へ変換する。
- **Output:** API からのレスポンスを受け取り、UI が表示しやすい形式（View Model）にリアクティブな状態として変換する。

このように、Composable を「UI とバックエンドの仲介役」として定義することで、バックエンドの変更が UI に直接波及するのを防ぐ「防衛層」としての役割も果たします。

---

**本プロジェクトの方針:**
Day 12 以降の実装フェーズでは、まず Pattern 1 で「コンポーネントにロジックが混在する不透明さ」を体験し、その後の Pattern 2/3 で Composable を用いて「UI を純粋なプレゼンテーションに保つ」美しさを実装を通じて検証する。

## Day 18b: Pattern 2 での Composable 実装

Pattern 2 では、`resources/js/composables/useInventoryProducts.ts` を追加し、在庫画面の状態管理と API 呼び出しを Vue component から分離した。

```text
InventoryApp.vue
  -> useInventoryProducts
      -> productApi.ts
          -> /api/products
```

Pattern 1 の `InventoryApp.vue` は、画面表示、form state、axios 呼び出し、error extraction、loading、message を全部持っていた。これは小さい画面では速いが、API 仕様変更や別画面での再利用が必要になると component が肥大化しやすい。

Pattern 2 では、`InventoryApp.vue` は composable から返された `products`、`productForm`、`stockForm`、`submitProduct`、`submitStockUpdate` などを使うだけにした。API endpoint や error response の扱いは component から隠れる。

この分離によって、frontend でも backend と同じように「UI は UI」「操作手順は Composable」「HTTP 詳細は API module」という責務分離を確認できる。

## Day 23: Pattern 3 の TypeScript contract

Pattern 3 では、まだ Vue component や composable は追加しない。代わりに、Day 22 の `ProductController` / `ProductPresenter` が公開する HTTP boundary を `resources/js/contracts/product.ts` に写した。

Pattern 2 の TypeScript type は、Composable が API module と共有する DTO として置いた。一方で Pattern 3 の TypeScript type は、`CreateProductRequest`、`IncreaseStockRequest`、`DecreaseStockRequest`、`AdjustStockRequest`、`ProductPresenterResponse` のように、UseCase の入口と Presenter の出口を名前で示す。

これにより、将来 Vue 側を追加するときも、component や composable が最初から `type` の discriminated union と snake_case の HTTP key を使える。Pattern 3 の frontend は、Pattern 2 よりも「どのユースケースを呼ぶ payload か」を型名で読み取りやすくなる。
