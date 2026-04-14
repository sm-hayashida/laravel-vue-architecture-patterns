# SOLID原則

## なぜSOLIDを学ぶのか

この学習ではMVC/Onion/Cleanの３パターンを比較実装する。  
アーキテクチャの「何が違うのか」を言語化するための土台としてSOLID原則を学ぶ必要がある。  
SOLIDはコードを「変化に強く・読みやすく・テストしやすく」するための５つの指針   

---


## S - Single Responsibility Prinsiple(単一責任の原則)

### 一言で言うと
**クラス（関数）が変更される理由は、ただ一つであるべき。**
### なぜ必要か
一つのクラスが「DBアクセス・ビジネスロジック・メール送信」を全てやると、  
どれか一つを変えるだけで他の部分まで壊れるリスクが生まれる。  
変更理由が複数 = 責務が複数 = 密結合の温床。

### Laravel での悪い例（MVC の罠）
  ```php
  class OrderController extends Controller
  {
      public function store(Request $request)
      {
          // バリデーション
          $request->validate([...]);

          // 在庫チェック（ドメインロジック）
          $stock = Stock::find($request->product_id);
          if ($stock->quantity < $request->amount) {
              return response()->json(['error' => '在庫不足'], 422);
          }

          // DB保存
          $order = Order::create([...]);

          // メール送信
          Mail::to($request->user())->send(new OrderConfirmation($order));

          return response()->json($order);
      }
  }
  ```
**問題点:メールの仕様変更・在庫ロジックの変更・DB設計の変更、どれが起きても`OrderControll`を触ることになる。**

### 改善の方向

- バリデーション → `FormRequest`
- 在庫チェック → Domain / UseCase 層
- DB保存 → Repository
- メール送信 → Listener（イベント駆動）

#### 1. バリデーション → `FormRequest`

```php
// app/Http/Requests/StoreOrderRequest.php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'amount'     => ['required', 'integer', 'min:1'],
        ];
    }
}
```

Controller は `$request->validate([...])` を書かなくてよくなる。
バリデーションルールが変わっても `StoreOrderRequest` だけ触ればいい。

---

#### 2. 在庫チェック → Domain / UseCase 層

```php
// app/UseCases/PlaceOrderUseCase.php
class PlaceOrderUseCase
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function execute(int $productId, int $amount, int $userId): Order
    {
        $stock = $this->stockRepository->findByProductId($productId);

        // ドメインロジックはここに集中する
        if ($stock->quantity < $amount) {
            throw new InsufficientStockException('在庫が不足しています');
        }

        return $this->orderRepository->create([
            'product_id' => $productId,
            'amount'     => $amount,
            'user_id'    => $userId,
        ]);
    }
}
```

在庫チェックのルールが変わっても Controller は一切触らない。

---

#### 3. DB保存 → `Repository`

```php
// app/Repositories/OrderRepositoryInterface.php
interface OrderRepositoryInterface
{
    public function create(array $data): Order;
}

// app/Repositories/EloquentOrderRepository.php
class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }
}
```

UseCase は「保存する」という意図だけを知っていればよく、
Eloquent の詳細には依存しない。

---

#### 4. メール送信 → `Listener`（イベント駆動）

```php
// app/Events/OrderPlaced.php
class OrderPlaced
{
    public function __construct(public readonly Order $order) {}
}

// app/Listeners/SendOrderConfirmationMail.php
class SendOrderConfirmationMail
{
    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->user)->send(
            new OrderConfirmation($event->order)
        );
    }
}
```

UseCase はイベントを発火するだけ。メールの送信先や内容が変わっても UseCase は触らない。

---

#### 全部組み合わせた後の Controller

```php
class OrderController extends Controller
{
    public function __construct(
        private readonly PlaceOrderUseCase $useCase,
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->useCase->execute(
            productId: $request->integer('product_id'),
            amount:    $request->integer('amount'),
            userId:    $request->user()->id,
        );

        OrderPlaced::dispatch($order); // イベント発火だけ

        return response()->json($order, 201);
    }
}
```

### パターン比較での位置付け
|パターン|SRPの扱い|
|-------|--------|
|MVC|Controllerに責務が集中しがち（違反しやすい）|
|Onion|Application Serviceが「手順の指揮」に集中|
|Clean|UseCase = 一つのビジネスシナリオ = 一つの変更理由|

---

## O - Open/Closed Principle(開放閉鎖の原則)
### 一言で言うと
**拡張に対して開いており、修正に対して閉じているべき。**
**新しい振る舞いを追加するとき、既存コードを書き換えずに済む設計。**

### なぜ必要か
既存コードを書き換えるたびに、動いていた部分を壊すリスクが発生する。
インターフェース（抽象）に依存することで、新しい実装を「差し込む」だけで実装できる。

### Laravel での悪い例
```php
  class DiscountCalculator
  {
      public function calculate(string $type, int $price): int
      {
          if ($type === 'member') {
              return $price * 0.9;
          } elseif ($type === 'vip') {
              return $price * 0.8;
          }
          // 新しい割引タイプが増えるたびにここを書き換える
          return $price;
      }
  }
```

**問題点：　割引タイプが増えるたびにifを追加 = 既存コードを修正。**
**テストも再実行が必要になる。**

### 改善の方向
```php
  interface DiscountPolicy
  {
      public function apply(int $price): int;
  }

  class MemberDiscount implements DiscountPolicy
  {
      public function apply(int $price): int { return $price * 0.9; }
  }

  class VipDiscount implements DiscountPolicy
  {
      public function apply(int $price): int { return $price * 0.8; }
  }

  // 新しい割引 → 新クラスを追加するだけ。既存コードは触らない。
```
### パターン比較での位置付け
|パターン|OCPの扱い|
|-------|--------|
|MVC|Modelにifが積み重なりやすい（違反しやすい）|
|Onion|Domain Serviceをinterface + 実装で分離|
|Clean|Interface Adapterがポートを実装、UseCaseは抽象に依存|

## L - Liskov Substitution Principle(リスコフの置換原則)
### 一言で言うと
**子クラス（具象）は親クラス（抽象）の性質を壊してはいけないと言う原則**
**抽象（インターフェース）を使っている側が、その具体的な中身を知らなくても、どれに置き換えても正しく動くべき**

### なぜ必要か
**「RepositoryInterface を使っているのに、特定の DB 実装の時だけ例外を吐く」
「親クラスでは true を返していたのに、子クラスでは勝手に nullを返す」
といったことが起きると、呼び出し側がいちいち中身を判定しなければならず、
ポリモーフィズムが壊れるため。**

### Laravelでの悪い例
```php
interface StockRepository {
      public function find(int $id): Stock;
  }

  class DatabaseStockRepository implements StockRepository {
      public function find(int $id): Stock {
          return Stock::findOrFail($id); // 見つからなければ例外を投げる
      }
  }

  class MockStockRepository implements StockRepository {
      public function find(int $id): ?Stock {
          return null; // インターフェースの規約を無視して nullable に変えてしまう
      }
  }
```
**問題点：StockRepositoryを使っているUseCaseは、Stock型が帰って来ると期待しているのに、Mockに差し替えた瞬間にnullで落ちる**
### 改善の方向
- インターフェース（抽象）で定義した方宣言、引数の数、例外の型を厳守する。
- 事前条件（引数の制約）を強めない、事後条件（戻り値の制約）を弱めない。


### パターン比較での位置付け
|パターン|  LSPの扱い|
|-------|--------|
|MVC|Eloquent Modelを継承しすぎると、メソッドを上書きして振る舞いを壊しやすい。|
|Onion/Clean|Interfaceを厳格に定義し、具象（DB↔︎Mock）の差し替えを安全に行えるようにする。|

## I - Interface Segregation Principle(インターフェース分離の原則)

### 一言で言うと
**クライアントが利用しないメソッドに依存することを強制してはいけない。**
**巨大なインターフェース一つより、小さなインターフェース複数**

### なぜ必要か
**一つのインターフェースに「読み取り・書き込み・削除・集計」を詰め込みすぎると、読み取りしかないクラスまで「削除」メソッドの実装を強要されたり、削除ロジックの変更の影響を受けたりするため。**

### Laravelでの悪い例
```php
interface ProductService {
      public function list();
      public function updatePrice(int $id, int $price);
      public function delete(int $id);
      public function calculateTax(int $price); // 計算ロジックまで入っている
  }

  class ProductListController {
      public function __construct(private ProductService $service) {}
      // 一覧を表示したいだけなのに、更新や削除メソッドを持つ Service に依存している
  }
```
### 改善の方向
- 役割ごとにインターフェースを分ける。
- 例えば、参照ようのProdactQueryと、更新ようのProdactCommandに分割する。

### パターン比較での位置付け
|パターン| DIPの扱い|
|-------|--------|
|MVC|Eloquentは巨大なインターフェース（Active Recode）。不要なメソッドも全て公開されている。|
|Onion|Repositoryはドメインが必要なメソッドだけに絞り、必要最低限の依存を作る。|
|Clean|Input Port/Output PortをUseCaseごとに定義し、必要な情報だけに絞る。|

## D - Dependency inversion Principle(依存性逆転の原則)
### 一言で言うと
**上位モジュールは下位モジュールに依存しなくてはならない。両者は抽象に依存すべき。**
**具体的な詳細(DB／Framework／API)ではなく、抽象（Interface）に依存せよ。**

### なぜ必要か
**UseCase（ビジネスロジック）が直接Eloquent（DB）に依存すると、DBを変えたい時やテストしたいときに、UseCaseまで書き換える必要がある。
これを「逆転」させて、UseCaseは「Interface（抽象）」に依存し、DB（詳細）がそのInterfaceを実装するようにするため。**

### Laravel での例（Service Container を活用）

```php
// app/Providers/AppServiceProvider.php
  public function register() {
      // 「抽象」に対して「具象」を紐づける
      $this->app->bind(StockRepositoryInterface::class, EloquentStockRepository::class);
  }
```

### パターン比較での位置付け
|パターン| DIPの扱い|
|-------|--------|
|MVC|Controller → Model（DB）と言う直接の依存。DIPを使わず密結合になりやすい。|
|Onion|外側の層（Infrastructure）が内側の層（Domain Interface）に依存する。|
|Clean|全ての依存が内側（Entities/UseCases）に向かう。Frameworksは一番外側。|

