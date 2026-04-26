# Pattern 2: Onion Architecture + DDD

## 概要
オニオンアーキテクチャを採用し、DDD（ドメイン駆動設計）の戦術的設計パターンを適用した実装例です。

## ディレクトリ構成（予定）
```
pattern2-onion/
├── app/
│   ├── Domain/              # ドメイン層 (Core)
│   │   ├── Entities/        # エンティティ
│   │   ├── ValueObjects/    # 値オブジェクト
│   │   ├── Services/        # ドメインサービス
│   │   └── Repositories/    # リポジトリ・インターフェース
│   ├── Application/         # アプリケーション層
│   │   └── Services/        # アプリケーションサービス
│   └── Infrastructure/      # インフラストラクチャ層
│       └── Repositories/    # Eloquent による実装
└── ...
```

## 学習のポイント
- **DIP (Dependency Inversion Principle):**
  `Domain/Repositories` にインターフェースを置き、`Infrastructure` でそれを実装することで、ドメインが Eloquent に依存しないようにします。
- **ドメインロジックの集約:**
  Model や Controller に書いていた計算ロジックやバリデーションを、Entity や Value Object に閉じ込めます。

## 比較のヒント
- Pattern 1 (MVC) と比べて、コード量（クラス数）がどう増えたか。
- ビジネスロジックの「場所」が明確になったか。
- 単体テストの書きやすさはどう変わったか。
