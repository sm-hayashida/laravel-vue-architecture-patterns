# 学習ロードマップと進捗

学習ステップの選択・記録が必要な場合だけ読む。通常のagent起動時には
読み込まない。

## スケジュール

### フェーズ1: 概念インプット（1〜2週目）

| Day | 内容 | ファイル |
|-----|------|----------|
| Day 1 | 全体構成の再設計 | `AGENTS.md` / `README.md` |
| Day 2 | SOLID原則（S・O）をまとめる | `docs/01_solid-principles.md` |
| Day 3 | SOLID原則（L・I・D）をまとめる | `docs/01_solid-principles.md` |
| Day 4 | 疎結合・高凝集・SoCをまとめる | `docs/01_solid-principles.md` |
| Day 5 | MVCパターンの限界と「ドメインモデル貧血症」 | `docs/02_mvc-pattern.md` |
| Day 6 | DDD 戦術的設計（Entity, VO, Repository） | `docs/07_ddd-tactical-design.md` |
| Day 7 | オニオンアーキテクチャの概念とDIP | `docs/06_onion-architecture.md` |
| Day 8 | クリーンアーキテクチャとユースケース駆動 | `docs/04_clean-architecture.md` |
| Day 9 | Vue Composables と フロントエンドの責務 | `docs/05_vue-composables.md` |

### フェーズ2: Pattern 1 実装 (MVC)

| Day | 内容 |
|-----|------|
| Day 10 | 完了: Laravel初期設定・Migration |
| Day 11 | Model/Controllerへのロジック詰め込み実装 |
| Day 12 | Vueベタ書き実装・動作確認 |
| Day 13 | P1の振り返り（変更の難しさ、テストの書きにくさ） |

### フェーズ3: Pattern 2 実装 (Onion + DDD)

| Day | 内容 |
|-----|------|
| Day 14 | Domain層の設計（Entity, Value Object） |
| Day 15 | Domain Service と Repository Interface |
| Day 16 | Application層（Service）の実装 |
| Day 17 | Infrastructure層（Eloquent Repository）の実装 |
| Day 18 | Vue Composable への分離 |
| Day 19 | 単体テスト作成・P1との比較 |

### フェーズ4: Pattern 3 実装 (Clean + DDD)

| Day | 内容 |
|-----|------|
| Day 20 | UseCase（Interactor）の定義 |
| Day 21 | Input/Output Port（Interface）の定義 |
| Day 22 | Controller / Presenter の実装 |
| Day 23 | TypeScriptによる厳格な型定義共有 |
| Day 24 | モックを使用した高速なテスト実装 |
| Day 25 | Onion vs Clean の構造的差異をまとめる |
| Day 26 | 全体振り返り・最終READMEの完成 |

## 進捗ログ

| Day | 日付 | 完了 | メモ |
|-----|------|------|------|
| Day 1 | 2026/04/10 | 完了 | 全体構成をOnion/Clean/DDD比較へシフト |
| Day 2 | 2026/04/13 | 完了 | SOLID原則 (S, O) を整理 |
| Day 3 | 2026/04/14 | 完了 | SOLID原則 (L, I, D) を整理 |
| Day 4 | 2026/04/15 | 完了 | 疎結合・高凝集・SoCを整理 |
| Day 5 | 2026/04/16 | 完了 | MVCパターンの限界と「ドメインモデル貧血症」を整理 |
| Day 6 | 2026/04/22 | 完了 | DDD 戦術的設計（Entity, VO, Repository）を整理 |
| Day 7 | 2026/04/23 | 完了 | オニオンアーキテクチャの概念とDIPを整理 |
| Day 8 | 2026/04/24 | 完了 | クリーンアーキテクチャとユースケース駆動を整理 |
| Day 9 | 2026/04/27 | 完了 | Vue Composables とフロントエンドの責務を整理 |
| Day 10 | 2026/04/30 | 完了 | Pattern 1 の Laravel 10 初期構成と在庫管理 migration を追加 |
| Day 11 | 2026/05/07 | 完了 | Pattern 1 の Model / Controller に在庫ロジックと Feature Test を追加 |
| Day 12 | 2026/05/12 | 完了 | Pattern 1 の Vue ベタ書き在庫画面を追加 |
| Day 13 | 2026/05/13 | 完了 | Pattern 1 の変更しにくさ・テストしにくさを振り返り |
| Day 14 | 2026/05/13 | 完了 | Pattern 2 の Domain 層として Entity / Value Object を追加 |
| Day 15 | 2026/05/13 | 完了 | Pattern 2 の Domain Service と Repository Interface を追加 |
| Day 16 | 2026/05/19 | 完了 | Pattern 2 の Application Service を追加 |
| Day 17 | 2026/05/19 | 完了 | Pattern 2 の Infrastructure Eloquent Repository を追加 |
| Day 18a | 2026/05/19 | 完了 | Pattern 2 の Controller / DI / API 接続を追加 |
| Day 18b | 2026/05/19 | 完了 | Pattern 2 の Vue Composable 分離を追加 |
| Day 19 | 2026/05/27 | 完了 | Pattern 2 の Domain / Application 単体テストと Pattern 1 比較を追加 |
| Day 20 | 2026/05/28 | 完了 | Pattern 3 の UseCase / Interactor を操作単位で追加 |
| Day 21 | 2026/06/12 | 完了 | Pattern 3 の Input / Output Port を追加 |
| Day 22 | 2026/06/18 | 完了 | Pattern 3 の Controller / Presenter を追加 |
| Day 23 | 2026/08/25 | 完了 | Pattern 3 の TypeScript API contract を追加 |
| Day 24 | 2026/08/25 | 完了 | Pattern 3 の Interactor を PHPUnit mock ports で直接テスト |
| Day 25 | 2026/08/25 | 完了 | Onion + DDD と Clean + DDD の構造的差異を現在のコードで比較 |
