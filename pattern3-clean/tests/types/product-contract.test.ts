import type {
    AdjustStockRequest,
    CreateProductRequest,
    DecreaseStockRequest,
    IncreaseStockRequest,
    ProductPresenterResponse,
    StockOperationRequest,
} from '../../resources/js/contracts/product';

const createProductRequest = {
    sku: 'SKU-001',
    name: 'Notebook',
    stock_quantity: 10,
    price_amount_in_cents: 1200,
} satisfies CreateProductRequest;

const increaseStockRequest = {
    type: 'in',
    quantity: 5,
    operator_role: 'staff',
} satisfies IncreaseStockRequest;

const decreaseStockRequest = {
    type: 'out',
    quantity: 2,
    operator_role: 'manager',
} satisfies DecreaseStockRequest;

const adjustStockRequest = {
    type: 'adjustment',
    quantity: 20,
    operator_role: 'manager',
} satisfies AdjustStockRequest;

const stockOperationRequests = [
    increaseStockRequest,
    decreaseStockRequest,
    adjustStockRequest,
] satisfies StockOperationRequest[];

const productPresenterResponse = {
    data: {
        id: null,
        sku: createProductRequest.sku,
        name: createProductRequest.name,
        stock_quantity: createProductRequest.stock_quantity,
        price_amount_in_cents: createProductRequest.price_amount_in_cents,
    },
} satisfies ProductPresenterResponse;

const invalidIncreaseRequest = {
    // @ts-expect-error type discriminates increase stock requests.
    type: 'out',
    quantity: 5,
    operator_role: 'staff',
} satisfies IncreaseStockRequest;

const invalidAdjustmentRole = {
    type: 'adjustment',
    quantity: 20,
    // @ts-expect-error adjustment is the direct stock-adjustment use case.
    operator_role: 'staff',
} satisfies AdjustStockRequest;

const invalidOperatorRoleKey = {
    type: 'in',
    quantity: 5,
    // @ts-expect-error stock operation requests keep the current snake_case HTTP key.
    operatorRole: 'staff',
} satisfies StockOperationRequest;

const invalidPresenterResponse = {
    // @ts-expect-error presenter output keeps id nullable but not optional.
    data: {
        sku: 'SKU-001',
        name: 'Notebook',
        stock_quantity: 10,
        price_amount_in_cents: 1200,
    },
} satisfies ProductPresenterResponse;

void stockOperationRequests;
void productPresenterResponse;
void invalidIncreaseRequest;
void invalidAdjustmentRole;
void invalidOperatorRoleKey;
void invalidPresenterResponse;
