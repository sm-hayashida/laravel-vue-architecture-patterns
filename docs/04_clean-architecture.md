# 04. クリーンアーキテクチャ (Clean Architecture)

## 概要
クリーンアーキテクチャは、ロバート・C・マーチン（Uncle Bob）によって提唱された、システムの「関心の分離」を極限まで追求したアーキテクチャです。
その核心は **「依存性のルール（Dependency Rule）」** にあり、ソースコードの依存関係は常に内側（ビジネスルール）に向かってのみ存在しなければなりません。

## レイヤー構造と責務

### 1. Entities (中心：ビジネスルール)
- **内容:** DDDにおけるエンティティ、値オブジェクト。
- **責務:** 企業全体のビジネスルールをカプセル化する。特定のアプリケーション（WebかCLIか等）の変更によって、このレイヤーが影響を受けることはありません。

### 2. Use Cases (中心：アプリケーションルール)
- **内容:** **Interactor (インタラクター)**。
- **責務:** システムが「何をするか」を記述する。エンティティへのデータの流れを調整し、エンティティにビジネスルールを遂行するよう指示を出す。
- **ユースケース駆動:** `ProductService` のような「データのまとまり」ではなく、`IncreaseStockUseCase`（在庫を増やす）のように「ユーザーの意図」に基づいた単位でクラスを分割します。

### 3. Interface Adapters (外側：変換)
- **内容:** **Controllers, Presenters, Gateways (Repository Interfaces)**。
- **責務:** 外部（Web, DB）と内部（Use Case, Entity）のデータの形式を変換する。
- **Presenter の役割:** ユースケースから返された「データ（Output Data）」を、画面に表示しやすい「ビューモデル（View Model）」に変換する責務を持ちます。

### 4. Frameworks & Drivers (最外周：詳細)
- **内容:** Laravel (Framework), Database (Eloquent), UI, Web Server。
- **責務:** すべての「詳細」がここに配置されます。クリーンアーキテクチャにおいて、フレームワークはあくまで「ツール」であり、システムの核ではありません。

---

## ユースケース駆動 (Use Case Driven) の重要性

クリーンアーキテクチャでは、「何ができるシステムか」がフォルダ構成を見るだけでわかることを理想とします（これを **Screaming Architecture** と呼びます）。

- **MVCの場合:** `ProductController` を見ないと、何ができるのか全容が掴みにくい。
- **クリーンアーキテクチャの場合:**
    - `UseCases/Products/AddStockUseCase.php`
    - `UseCases/Products/ShipProductUseCase.php`
    - `UseCases/Products/CheckInventoryUseCase.php`
    このように、ファイル名そのものがビジネスの機能を表現します。

---

## Onion アーキテクチャとの主な違い

どちらも「ドメイン中心」「DIP」を掲げていますが、クリーンアーキテクチャはより **「境界の分離」** に対して厳格です。

| 特徴 | Onion Architecture | Clean Architecture |
| :--- | :--- | :--- |
| **中心的な呼び名** | Application Service | Use Case (Interactor) |
| **境界の定義** | レイヤーの重なり | Input / Output Port (Interface) |
| **出力の処理** | コントローラーが値を返す | **Presenter** が出力を整形する |
| **フロントエンド** | Composableでロジックを分離 | プレゼンテーション層としてより独立 |

### 核心：Input / Output Port

クリーンアーキテクチャでは、ユースケースが外部の技術に依存しないよう、入り口（Input Port）と出口（Output Port）をインターフェースで定義します。

1. **Input Port:** コントローラーがユースケースを呼び出すための約束。
2. **Output Port:** ユースケースが結果を返す（または保存する）ための約束。

これにより、Webリクエストだけでなく、バッチ処理やテストコードからも、全く同じユースケースを再利用することが容易になります。

---

**本プロジェクトの方針:**
Pattern 3 では、この「Input/Output Port」を意識し、Laravelのコントローラーがビジネスロジックに一切触れず、ただユースケースに情報を渡し、Presenterに結果を託す構造を実現する。
