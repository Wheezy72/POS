import { computed, ref } from 'vue';
import { roundCurrency } from '../../utils/formatters';

export function useCart() {
    const cart = ref([]);

    const subtotal = computed(() => cart.value.reduce((sum, item) => sum + lineSubtotal(item), 0));
    const tax = computed(() => cart.value.reduce((sum, item) => sum + lineTax(item), 0));
    const grandTotal = computed(() => roundCurrency(subtotal.value + tax.value));
    const totalUnits = computed(() => cart.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0));

    function addProductToCart(product) {
        const existingItem = cart.value.find((item) => item.product_id === product.id);

        if (existingItem) {
            existingItem.quantity = roundQuantity(existingItem.quantity + 1, existingItem.allow_fractional_sales);
            return;
        }

        cart.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            quantity: 1,
            base_price: Number(product.base_price),
            discount: 0,
            tax_rate: Number(product.tax_category?.rate ?? 0),
            allow_fractional_sales: Boolean(product.allow_fractional_sales),
        });
    }

    function removeItem(productId) {
        cart.value = cart.value.filter((item) => item.product_id !== productId);
    }

    function changeQty(item, delta) {
        const step = item.allow_fractional_sales ? 0.25 : 1;
        item.quantity = roundQuantity(Number(item.quantity || 0) + (step * delta), item.allow_fractional_sales);

        if (item.quantity <= 0) {
            removeItem(item.product_id);
        }
    }

    function normalizeQuantity(item) {
        item.quantity = roundQuantity(item.quantity, item.allow_fractional_sales);

        if (item.quantity <= 0) {
            removeItem(item.product_id);
        }
    }

    function normalizeDiscount(item) {
        const nextDiscount = Math.max(0, Number(item.discount || 0));
        item.discount = Math.min(roundCurrency(nextDiscount), roundCurrency(item.base_price - 0.01));
    }

    function effectiveUnitPrice(item) {
        const candidate = Number(item.base_price) - Number(item.discount || 0);

        return roundCurrency(Math.max(candidate, 0.01));
    }

    function lineSubtotal(item) {
        return roundCurrency(Number(item.quantity || 0) * effectiveUnitPrice(item));
    }

    function lineTax(item) {
        return roundCurrency(lineSubtotal(item) * (Number(item.tax_rate || 0) / 100));
    }

    function lineTotal(item) {
        return roundCurrency(lineSubtotal(item) + lineTax(item));
    }

    function resetCart() {
        cart.value = [];
    }

    return {
        cart,
        subtotal,
        tax,
        grandTotal,
        totalUnits,
        addProductToCart,
        removeItem,
        changeQty,
        normalizeQuantity,
        normalizeDiscount,
        effectiveUnitPrice,
        lineSubtotal,
        lineTax,
        lineTotal,
        resetCart,
    };
}

export function roundQuantity(value, fractionalAllowed) {
    const numericValue = Number(value || 0);

    if (fractionalAllowed) {
        return Math.max(0.25, Math.round(numericValue * 4) / 4);
    }

    return Math.max(1, Math.round(numericValue));
}
