import axios from 'axios';
import type { ApiDataResponse, CreateProductPayload, Product, UpdateStockPayload } from '../types/product';

export async function fetchProducts(): Promise<Product[]> {
    const response = await axios.get<ApiDataResponse<Product[]>>('/api/products');

    return response.data.data;
}

export async function createProduct(payload: CreateProductPayload): Promise<Product> {
    const response = await axios.post<ApiDataResponse<Product>>('/api/products', payload);

    return response.data.data;
}

export async function updateProductStock(productId: number, payload: UpdateStockPayload): Promise<Product> {
    const response = await axios.post<ApiDataResponse<Product>>(`/api/products/${productId}/stock`, payload);

    return response.data.data;
}
