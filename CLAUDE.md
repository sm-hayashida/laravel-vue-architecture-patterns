# CLAUDE.md - プロジェクト指示書

このファイルはClaude AIとのセッションを開始する際に貼り付けて使用します。

---

## あなたへの指示

このファイルを読んだら以下のフォーマットで返答してください。

```
✅ プロジェクト内容を認識しました。
📅 今日はDay〇〇です。
🎯 本日のタスク：〇〇
⏱ 想定作業時間：30分
```

その後、本日のタスクの具体的な手順をCLIベースで提示してください。

---

## プロジェクト概要

**課題名**
Laravel + Vue における DDD と各アーキテクチャ（MVC / Onion / Clean）の比較実装と検証

**目的**
社内昇格評価のためのポートフォリオ作成（クリーンアーキテクチャとオニオンアーキテクチャの比較を軸にする）

**リポジトリ**
`~/Documents/learning/laravel-vue-architecture-patterns`
GitHub: `git@github.com:sm-hayashida/laravel-vue-architecture-patterns.git`

**環境**
- エディタ：VSCode
- バージョン管理：GitHub（sm-hayashida）
- 1日の作業時間：30分以内
- 期間：3ヶ月

---

## 実装する3つのパターン

| パターン | 構成・アーキテクチャ | 目的・比較のポイント |
|----------|-------------------|-------------------|
| Pattern 1 | Laravel標準MVC ＋ Vue | **【基準】** 密結合・手続き型・ドメインモデル貧血症の課題把握 |
| Pattern 2 | **Onion + DDD** | **【ドメイン中心】** Entity, VO, Domain Service 導入。DIPによるドメインの保護 |
| Pattern 3 | **Clean + DDD** | **【ユースケース駆動】** Interactor, Interface Adapter 導入。厳格な境界分離 |

**比較する機能（全パターン共通）**
在庫管理の計算ロジック（在庫数の増減・権限チェック・ドメインルールのバリデーション）

---

## リポジトリ構成

```
laravel-vue-architecture-patterns/
├── CLAUDE.md                        # この指示書
├── README.md                        # リポジトリ概要
├── docs/                            # 学習ログ
│   ├── 01_solid-principles.md
│   ├── 02_mvc-pattern.md
│   ├── 03_service-repository.md
│   ├── 04_clean-architecture.md
│   ├── 05_vue-composables.md
│   ├── 06_onion-architecture.md     # 追加: オニオンの構造
│   └── 07_ddd-tactical-design.md    # 追加: DDD戦術的設計
├── pattern1-mvc/                    # 実装①
│   └── README.md
├── pattern2-onion/                  # 実装② (Actionから変更)
│   └── README.md
└── pattern3-clean/                  # 実装③
    └── README.md
```

---

## スケジュール

### フェーズ1：概念インプット（1〜2週目）

| Day | 内容 | ファイル |
|-----|------|----------|
| Day 1 | 全体構成の再設計・CLAUDE.md更新 | `CLAUDE.md` |
| Day 2 | SOLID原則（S・O）をまとめる | `docs/01_solid-principles.md` |
| Day 3 | SOLID原則（L・I・D）をまとめる | `docs/01_solid-principles.md` |
| Day 4 | 疎結合・高凝集・SoCをまとめる | `docs/01_solid-principles.md` |
| Day 5 | MVCパターンの限界と「ドメインモデル貧血症」 | `docs/02_mvc-pattern.md` |
| Day 6 | DDD 戦術的設計（Entity, VO, Repository） | ✅ `docs/07_ddd-tactical-design.md` |
| Day 7 | オニオンアーキテクチャの概念とDIP | `docs/06_onion-architecture.md` |
| Day 8 | クリーンアーキテクチャとユースケース駆動 | `docs/04_clean-architecture.md` |
| Day 9 | Vue Composables と フロントエンドの責務 | `docs/05_vue-composables.md` |

### フェーズ2：Pattern 1 実装 (MVC)

| Day | 内容 |
|-----|------|
| Day 10 | Laravel初期設定・Migration |
| Day 11 | Model/Controllerへのロジック詰め込み実装 |
| Day 12 | Vueベタ書き実装・動作確認 |
| Day 13 | P1の振り返り（変更の難しさ、テストの書きにくさ） |

### フェーズ3：Pattern 2 実装 (Onion + DDD)

| Day | 内容 |
|-----|------|
| Day 14 | Domain層の設計（Entity, Value Object） |
| Day 15 | Domain Service と Repository Interface |
| Day 16 | Application層（Service）の実装 |
| Day 17 | Infrastructure層（Eloquent Repository）の実装 |
| Day 18 | Vue Composable への分離 |
| Day 19 | 単体テスト作成・P1との比較 |

### フェーズ4：Pattern 3 実装 (Clean + DDD)

| Day | 内容 |
|-----|------|
| Day 20 | UseCase（Interactor）の定義 |
| Day 21 | Input/Output Port（Interface）の定義 |
| Day 22 | Controller / Presenter の実装 |
| Day 23 | TypeScriptによる厳格な型定義共有 |
| Day 24 | モックを使用した高速なテスト実装 |
| Day 25 | Onion vs Clean の構造的差異をまとめる |
| Day 26 | 全体振り返り・最終READMEの完成 |

---

## 進捗ログ

| Day | 日付 | 完了 | メモ |
|-----|------|------|------|
| Day 1 | 2026/04/10 | ✅ | 全体構成をOnion/Clean/DDD比較へシフト |
| Day 2 | 2026/04/13 | ✅ | SOLID原則 (S, O) を整理 |
| Day 3 | 2026/04/14 | ✅ | SOLID原則 (L, I, D) を整理 |
| Day 4 | 2026/04/15 | ✅ | 疎結合・高凝集・SoCを整理 |
| Day 5 | 2026/04/16 | ✅ | MVCパターンの限界と「ドメインモデル貧血症」を整理 |
| Day 6 | 2026/04/22 | ✅ | DDD 戦術的設計（Entity, VO, Repository）を整理 |
| Day 7 | 2026/04/23 | ✅ | オニオンアーキテクチャの概念とDIPを整理 |
| Day 8 | 2026/04/24 | ✅ | クリーンアーキテクチャとユースケース駆動を整理 |
| Day 9 | 2026/04/27 | ✅ | Vue Composables と フロントエンドの責務を整理 |

---

## コミットメッセージのルール

```
docs: 学習ログの追加・更新
feat: 新機能の実装
refactor: リファクタリング
test: テストの追加
chore: 設定・環境構築
```

---

## 備考

- クリーンとオニオンの「何が違うのか」を常に自問自答しながら進める
- DDDの用語（エンティティ、値オブジェクト等）を正しく使う
- docsの学習ログには、自分の言葉で「なぜこの設計にするか」を必ず書く
- KISSとYAGNIを忘れずに、過剰な抽象化は避ける
