import { computed, onMounted, reactive, ref } from 'vue';
import { createProduct, fetchProducts, updateProductStock } from '../api/productApi';
import type { CreateProductPayload, OperatorRole, Product, StockMovementType } from '../types/product';

interface StockForm {
    product_id: number | null;
    type: StockMovementType;
    quantity: number;
    operator_role: OperatorRole;
}

export function useInventoryProducts() {
    const products = ref<Product[]>([]);
    const loading = ref(false);
    const message = ref('');
    const messageType = ref<'success' | 'error'>('success');

    const productForm = reactive<CreateProductPayload>({
        sku: '',
        name: '',
        stock_quantity: 0,
        price_amount_in_cents: 0,
    });

    const stockForm = reactive<StockForm>({
        product_id: null,
        type: 'in',
        quantity: 1,
        operator_role: 'staff',
    });

    const totalStock = computed(() => products.value.reduce((sum, product) => {
        return sum + product.stock_quantity;
    }, 0));

    function showMessage(text: string, type: 'success' | 'error' = 'success'): void {
        message.value = text;
        messageType.value = type;
    }

    function formatPrice(amountInCents: number): string {
        const whole = Math.trunc(amountInCents / 100);
        const fraction = String(amountInCents % 100).padStart(2, '0');

        return `${whole.toLocaleString('en-US')}.${fraction}`;
    }

    function resetProductForm(): void {
        productForm.sku = '';
        productForm.name = '';
        productForm.stock_quantity = 0;
        productForm.price_amount_in_cents = 0;
    }

    function resetStockForm(productId: number | null = stockForm.product_id): void {
        stockForm.product_id = productId;
        stockForm.type = 'in';
        stockForm.quantity = 1;
        stockForm.operator_role = 'staff';
    }

    function extractError(error: unknown): string {
        if (!error || typeof error !== 'object') {
            return 'Request failed.';
        }

        const maybeAxiosError = error as {
            message?: string;
            response?: {
                data?: {
                    message?: string;
                    errors?: Record<string, string[]>;
                };
            };
        };
        const errors = maybeAxiosError.response?.data?.errors;

        if (errors) {
            return Object.values(errors).flat().join(' ');
        }

        return maybeAxiosError.response?.data?.message || maybeAxiosError.message || 'Request failed.';
    }

    async function loadProducts(): Promise<void> {
        loading.value = true;

        try {
            products.value = await fetchProducts();
        } catch (error) {
            showMessage(extractError(error), 'error');
        } finally {
            loading.value = false;
        }
    }

    async function submitProduct(): Promise<void> {
        loading.value = true;

        try {
            const product = await createProduct({ ...productForm });
            showMessage(`Created ${product.sku}.`);
            resetProductForm();
            await loadProducts();
        } catch (error) {
            showMessage(extractError(error), 'error');
        } finally {
            loading.value = false;
        }
    }

    async function submitStockUpdate(): Promise<void> {
        if (stockForm.product_id === null) {
            showMessage('Select product.', 'error');
            return;
        }

        loading.value = true;

        try {
            const product = await updateProductStock(stockForm.product_id, {
                type: stockForm.type,
                quantity: stockForm.quantity,
                operator_role: stockForm.operator_role,
            });
            showMessage(`Updated ${product.sku}.`);
            resetStockForm(product.id);
            await loadProducts();
        } catch (error) {
            showMessage(extractError(error), 'error');
        } finally {
            loading.value = false;
        }
    }

    onMounted(loadProducts);

    return {
        products,
        loading,
        message,
        messageType,
        productForm,
        stockForm,
        totalStock,
        formatPrice,
        loadProducts,
        submitProduct,
        submitStockUpdate,
    };
}
