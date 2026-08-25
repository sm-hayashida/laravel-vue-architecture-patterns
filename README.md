# Laravel + Vue アーキテクチャ・パターン比較 (DDD / Onion / Clean)

このリポジトリは、Laravel と Vue.js を用いたWebアプリケーション開発において、スケーラビリティと保守性を高めるための各種アーキテクチャ・パターンを比較・学習するためのハンズオン用プロジェクトです。

特に、**ドメイン駆動設計 (DDD)** の考え方を取り入れ、**オニオンアーキテクチャ**と**クリーンアーキテクチャ**の実装上の差異とメリットを検証することを主眼に置いています。

## 学習の目的
- フレームワーク（Laravel）に依存しすぎないビジネスロジックの構築手法を学ぶ。
- DDD の戦術的設計（Entity, Value Object 等）を PHP でどう実現するかを体験する。
- オニオンとクリーンの「ドメイン中心」と「ユースケース中心」の違いをコードで理解する。
- 疎結合な設計がテスト容易性や保守性にどう寄与するかを実証する。

## 比較する3つのパターン

### Pattern 1: Laravel Standard MVC
- **構成:** Eloquent Model + Controller + Blade/Vue
- **特徴:** 密結合。小規模開発では最速だが、大規模化すると Fat Model/Controller 化しやすい。
- **学び:** 「ドメインモデル貧血症」の課題を浮き彫りにする。

### Pattern 2: Onion Architecture + DDD
- **構成:** Domain Layer (Core) / Application Layer / Infrastructure Layer
- **特徴:** 依存性の逆転 (DIP) を用い、ドメインロジックをフレームワークから保護する。
- **学び:** ドメインを核とした階層構造と、DDD 戦術的設計の基礎を学ぶ。

### Pattern 3: Clean Architecture + DDD
- **構成:** Entities / Use Cases / Interface Adapters / Frameworks & Drivers
- **特徴:** ユースケース（Interactor）を独立させ、入力・出力の境界をより厳格に定義する。
- **学び:** フレームワークの交換可能性や、ユースケース駆動の設計による堅牢性を学ぶ。

## プロジェクト構成
```
├── docs/                # アーキテクチャやDDDの学習ログ
├── pattern1-mvc/        # 実装①: Laravel標準
├── pattern2-onion/      # 実装②: オニオン + DDD
└── pattern3-clean/      # 実装③: クリーン + DDD
```

## 学習ログ
- `docs/08_onion-vs-clean.md`: Day 25 の Onion + DDD と Clean + DDD の構造比較。現在の Pattern 2 / Pattern 3 のクラスに沿って、リクエストからレスポンスまでの流れと境界の違いを整理しています。

## 開発環境
- PHP 8.x / Laravel 10.x
- Node.js / Vue.js 3 / TypeScript
- Docker (Laravel Sail)

---
*本プロジェクトは社内昇格評価用ポートフォリオとして作成されています。*
