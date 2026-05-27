export type StockMovementType = 'in' | 'out' | 'adjustment';

export type OperatorRole = 'staff' | 'manager';

export interface Product {
    id: number;
    sku: string;
    name: string;
    stock_quantity: number;
    price_amount_in_cents: number;
}

export interface CreateProductPayload {
    sku: string;
    name: string;
    stock_quantity: number;
    price_amount_in_cents: number;
}

export interface UpdateStockPayload {
    type: StockMovementType;
    quantity: number;
    operator_role: OperatorRole;
}

export interface ApiDataResponse<T> {
    data: T;
}
