<template>
    <main class="app-shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Pattern 2 / Onion + DDD</p>
                <h1>Inventory</h1>
            </div>
            <button class="ghost-button" type="button" :disabled="loading" @click="loadProducts">
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
            <form class="panel" @submit.prevent="submitProduct">
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
                        Price Cents
                        <input v-model.number="productForm.price_amount_in_cents" required min="0" type="number">
                    </label>
                </div>
                <button class="primary-button" type="submit" :disabled="loading">
                    Create
                </button>
            </form>

            <form class="panel" @submit.prevent="submitStockUpdate">
                <h2>Stock Update</h2>
                <label>
                    Product
                    <select v-model.number="stockForm.product_id" required>
                        <option disabled :value="null">Select product</option>
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
                <button class="primary-button" type="submit" :disabled="loading || stockForm.product_id === null">
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
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products" :key="product.id">
                        <td>{{ product.sku }}</td>
                        <td>{{ product.name }}</td>
                        <td class="number">{{ product.stock_quantity }}</td>
                        <td class="number">{{ formatPrice(product.price_amount_in_cents) }}</td>
                    </tr>
                    <tr v-if="!products.length">
                        <td colspan="4" class="empty-cell">No products</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</template>

<script setup lang="ts">
import { useInventoryProducts } from '../composables/useInventoryProducts';

const {
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
} = useInventoryProducts();
</script>
