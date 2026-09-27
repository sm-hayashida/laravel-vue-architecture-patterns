# Laravel + Vue アーキテクチャ・パターン比較

同じ在庫管理題材を、**Laravel Standard MVC**、**Onion Architecture + DDD**、**Clean Architecture + DDD** で実装・比較する学習プロジェクトです。アーキテクチャ名そのものではなく、業務ルール・アプリケーション操作・HTTP/永続化の責務をどこに置き、依存をどちらへ向けるかをコードで学びます。

## 3方式の比較

| 方式 | 現在の責務配置と依存方向 | 利点 | コスト | 適する状況 |
| --- | --- | --- | --- | --- |
| Laravel Standard MVC | Eloquent `Product` が在庫計算と履歴作成を担い、`ProductController` がHTTP入力、権限、トランザクション、応答を担う。Vueも画面・API・状態を近接して持つ。Laravel標準の依存方向に従う。 | 少ない構成要素で素早く作れ、HTTPからDBまでのMVC sliceを確認しやすい。 | 業務ルールがModel、Controller、Vueへ分散し、変更範囲と小さな単体テストの境界が広がりやすい。 | 小規模で要求が安定し、Laravel標準の速い開発を優先するとき。 |
| Onion Architecture + DDD | `Product`、Value Object、`StockAdjustmentPolicy`、Repository InterfaceをDomain中心に置く。`ProductInventoryService` が操作手順を組み立て、InfrastructureのEloquent実装とHTTP Controllerは内側へ依存する。 | DomainをLaravel/Eloquentから守り、関連する在庫操作を1つのApplication Serviceで追いやすい。 | Entity、Value Object、Repository、Application/Infrastructureの分だけファイルと翻訳処理が増える。 | ドメイン中心で関連操作をまとめ、永続化を差し替えながら業務ルールを保護したいとき。 |
| Clean Architecture + DDD | Entityの業務ルールを内側に置き、`CreateProductInteractor`など操作別UseCaseが流れを担う。ControllerはInput Portに入力し、InteractorはOutput Portへ出力し、PresenterがJSON形を担う。外側が内側の境界へ依存する。 | 入力・出力・操作単位・テスト対象が明示され、変更とUseCase境界のテストを局所化しやすい。 | Input Port、Output Port、Interactor、Presenterという明示的な境界型が増え、儀式と配線の負担が増える。 | 操作別の変更、出力形式、UseCase境界のテストを明確に扱いたいとき。 |

## 学習上の結論

3方式の違いは名称やクラス数だけでは決まりません。重要なのは、在庫ルールをどこで守るか、入力と出力を誰が所有するか、外側の詳細が内側の業務ルールへ依存しているかです。小さく速く作るならMVC、ドメイン中心に操作をまとめるならOnion、UseCaseの入力・出力を強く明示するならCleanという、責務配置と依存方向に基づいて選びます。

## Pattern 3 の検証済み範囲と未接続の境界

Pattern 3 はEntity、UseCase、Input/Output Port、Controller、Presenter、TypeScript contract、およびRepository/Output Port mockを使うInteractor unit testまでを実装・検証しています。現在の証明範囲はUseCase境界までです。

一方で、Eloquent repositoryの具象実装、Laravel DI wiring、API routes、HTTP requestからEloquent DB保存までを通すHTTP-to-DB integration proofは未実装です。したがって、3方式が同じ完成度でend-to-end動作するとは示していません。

## プロジェクト構成
```
├── docs/                # アーキテクチャやDDDの学習ログ
├── pattern1-mvc/        # 実装①: Laravel標準
├── pattern2-onion/      # 実装②: オニオン + DDD
└── pattern3-clean/      # 実装③: クリーン + DDD
```

## 学習ログ
- `docs/08_onion-vs-clean.md`: Day 25 の Onion + DDD と Clean + DDD の構造比較。現在の Pattern 2 / Pattern 3 のクラスに沿って、リクエストからレスポンスまでの流れと境界の違いを整理しています。
- Day 26: 3方式の責務配置、依存方向、利点、コスト、適用目安とPattern 3の証拠境界をこのREADMEに整理しました（2026/09/14完了）。

## 開発環境
- PHP 8.x / Laravel 10.x
- Node.js / Vue.js 3 / TypeScript
- Docker (Laravel Sail)

---
*本プロジェクトは社内昇格評価用ポートフォリオとして作成されています。*
