# GEMINI.md - プロジェクト固有の指針

このファイルは Gemini CLI に対するプロジェクト固有の指示を定義します。

---

## 開発コンテキスト

- **プロジェクト名**: Laravel + Vue アーキテクチャ・パターン比較
- **核心的価値**: 「変更への強さ」「テスト容易性」「責務の分離（SoC）」を、実装を通じて実証する。
- **技術スタック**: PHP 8.x (Laravel 10), Vue 3 (TypeScript), Docker (Sail)

## Gemini CLI への追加指示

1. **アーキテクチャの厳格性**:
   - `pattern2-onion` ではドメイン層（Entity, VO, Domain Service）を核とし、Infrastructure への依存を許さない。
   - `pattern3-clean` では UseCase (Interactor) を中心に、Input/Output Port を用いた厳格な境界分離を維持する。
   - 既存のパターンに反する実装（例：Onion 層で Eloquent Model を直接参照する等）は、必ず指摘し代替案を提示すること。

2. **ドキュメント優先**:
   - 実装の前に、必ず `docs/` 内の関連ドキュメントを確認し、設計思想と乖離がないか検証する。
   - 新しい概念を実装した際は、その「意図」を `docs/` に反映することを提案する。

3. **命名規則の徹底**:
   - PHP: `PascalCase` (Class), `camelCase` (Method/Variable)
   - TypeScript: `PascalCase` (Component), `camelCase` (Hook/Function)
   - DB/API: `snake_case`

4. **テスト駆動の推奨**:
   - 特に Pattern 2/3 では、ドメインロジックの単体テストが容易であることを示すため、テストコードの同時作成を重視する。

## 進捗管理

進捗は `CLAUDE.md` の「進捗ログ」と同期して管理すること。

---

## 本日のタスク (Day 9)
- [x] Vue Composables と フロントエンドの責務を `docs/05_vue-composables.md` にまとめる。
