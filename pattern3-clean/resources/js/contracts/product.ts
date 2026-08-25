export type StockOperationType = 'in' | 'out' | 'adjustment';

export type OperatorRole = 'staff' | 'manager';

export interface CreateProductRequest {
    sku: string;
    name: string;
    stock_quantity: number;
    price_amount_in_cents: number;
}

export interface IncreaseStockRequest {
    type: 'in';
    quantity: number;
    operator_role: OperatorRole;
}

export interface DecreaseStockRequest {
    type: 'out';
    quantity: number;
    operator_role: OperatorRole;
}

export interface AdjustStockRequest {
    type: 'adjustment';
    quantity: number;
    operator_role: 'manager';
}

export type StockOperationRequest =
    | IncreaseStockRequest
    | DecreaseStockRequest
    | AdjustStockRequest;

export interface ProductPresenterData {
    id: number | null;
    sku: string;
    name: string;
    stock_quantity: number;
    price_amount_in_cents: number;
}

export interface ProductPresenterResponse {
    data: ProductPresenterData;
}
