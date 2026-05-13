<template>
    <main class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Pattern 1 / MVC</p>
                <h1>Inventory Baseline</h1>
            </div>
            <button class="ghost-button" type="button" :disabled="loading" @click="fetchProducts">
                Refresh
            </button>
        </header>

        <section class="summary-band">
            <div>
                <span>Total SKU</span>
                <strong>{{ products.length }}</strong>
            </div>
            <div>
                <span>Total Stock</span>
                <strong>{{ totalStock }}</strong>
            </div>
            <div>
                <span>Selected Role</span>
                <strong>{{ stockForm.operator_role }}</strong>
            </div>
        </section>

        <p v-if="message" class="notice" :class="messageType">{{ message }}</p>

        <section class="work-grid">
            <form class="panel" @submit.prevent="createProduct">
                <h2>New Product</h2>
                <label>
                    SKU
                    <input v-model="productForm.sku" required maxlength="64" placeholder="SKU-001">
                </label>
                <label>
                    Name
                    <input v-model="productForm.name" required maxlength="255" placeholder="Sample Product">
                </label>
                <div class="form-row">
                    <label>
                        Stock
                        <input v-model.number="productForm.stock_quantity" required min="0" type="number">
                    </label>
                    <label>
                        Price
                        <input v-model.number="productForm.price" required min="0" step="0.01" type="number">
                    </label>
                </div>
                <button class="primary-button" type="submit" :disabled="loading">
                    Create
                </button>
            </form>

            <form class="panel" @submit.prevent="updateStock">
                <h2>Stock Update</h2>
                <label>
                    Product
                    <select v-model.number="stockForm.product_id" required>
                        <option disabled value="">Select product</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.sku }} / {{ product.name }}
                        </option>
                    </select>
                </label>
                <div class="form-row">
                    <label>
                        Type
                        <select v-model="stockForm.type" required>
                            <option value="in">In</option>
                            <option value="out">Out</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </label>
                    <label>
                        Role
                        <select v-model="stockForm.operator_role" required>
                            <option value="staff">staff</option>
                            <option value="manager">manager</option>
                        </select>
                    </label>
                </div>
                <label>
                    Quantity
                    <input v-model.number="stockForm.quantity" required min="0" type="number">
                </label>
                <label>
                    Reason
                    <input v-model="stockForm.reason" maxlength="1000" placeholder="Shipped">
                </label>
                <button class="primary-button" type="submit" :disabled="loading || !stockForm.product_id">
                    Apply
                </button>
            </form>
        </section>

        <section class="table-wrap">
            <div class="table-header">
                <h2>Products</h2>
                <span>{{ loading ? 'Loading' : `${products.length} rows` }}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Movements</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products" :key="product.id">
                        <td>{{ product.sku }}</td>
                        <td>{{ product.name }}</td>
                        <td class="number">{{ product.stock_quantity }}</td>
                        <td class="number">{{ formatPrice(product.price) }}</td>
                        <td class="number">{{ product.stock_movements?.length ?? 0 }}</td>
                    </tr>
                    <tr v-if="!products.length">
                        <td colspan="5" class="empty-cell">No products</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';

const products = ref([]);
const loading = ref(false);
const message = ref('');
const messageType = ref('success');

const productForm = reactive({
    sku: '',
    name: '',
    stock_quantity: 0,
    price: 0,
});

const stockForm = reactive({
    product_id: '',
    type: 'in',
    quantity: 1,
    reason: '',
    operator_role: 'staff',
});

const totalStock = computed(() => products.value.reduce((sum, product) => {
    return sum + Number(product.stock_quantity);
}, 0));

function showMessage(text, type = 'success') {
    message.value = text;
    messageType.value = type;
}

function formatPrice(price) {
    return Number(price).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function resetProductForm() {
    productForm.sku = '';
    productForm.name = '';
    productForm.stock_quantity = 0;
    productForm.price = 0;
}

function resetStockForm(productId = stockForm.product_id) {
    stockForm.product_id = productId || '';
    stockForm.type = 'in';
    stockForm.quantity = 1;
    stockForm.reason = '';
    stockForm.operator_role = 'staff';
}

function extractError(error) {
    const errors = error.response?.data?.errors;

    if (errors) {
        return Object.values(errors).flat().join(' ');
    }

    return error.response?.data?.message || error.message || 'Request failed.';
}

async function fetchProducts() {
    loading.value = true;

    try {
        const response = await axios.get('/api/products');
        products.value = response.data.data;
    } catch (error) {
        showMessage(extractError(error), 'error');
    } finally {
        loading.value = false;
    }
}

async function createProduct() {
    loading.value = true;

    try {
        const response = await axios.post('/api/products', productForm);
        showMessage(`Created ${response.data.data.sku}.`);
        resetProductForm();
        await fetchProducts();
    } catch (error) {
        showMessage(extractError(error), 'error');
    } finally {
        loading.value = false;
    }
}

async function updateStock() {
    if (!stockForm.product_id) {
        showMessage('Select product.', 'error');
        return;
    }

    loading.value = true;

    try {
        const response = await axios.post(`/api/products/${stockForm.product_id}/stock`, {
            type: stockForm.type,
            quantity: stockForm.quantity,
            reason: stockForm.reason,
            operator_role: stockForm.operator_role,
        });

        showMessage(`Updated ${response.data.data.sku}.`);
        resetStockForm(response.data.data.id);
        await fetchProducts();
    } catch (error) {
        showMessage(extractError(error), 'error');
    } finally {
        loading.value = false;
    }
}

onMounted(fetchProducts);
</script>
