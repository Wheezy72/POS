<template>
    <section class="min-h-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div>
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Cart</p>
            </div>
            <div class="text-right text-xs uppercase tracking-[0.18em] text-slate-500">
                <p>{{ totalUnits.toFixed(2) }} units</p>
                <p>{{ cart.length }} lines</p>
            </div>
        </div>

        <div class="overflow-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                    <tr>
                        <th class="border-b border-r border-slate-200 px-2 py-2 text-left">#</th>
                        <th class="border-b border-r border-slate-200 px-2 py-2 text-left">Item</th>
                        <th class="border-b border-r border-slate-200 px-2 py-2 text-right">Price</th>
                        <th class="border-b border-r border-slate-200 px-2 py-2 text-center">Qty</th>
                        <th class="border-b border-r border-slate-200 px-2 py-2 text-right">Disc</th>
                        <th class="border-b border-slate-200 px-2 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="cart.length === 0">
                        <td colspan="6" class="px-3 py-12 text-center text-sm text-slate-500">
                            Cart is empty.
                        </td>
                    </tr>
                    <tr v-for="(item, index) in cart" :key="item.product_id" class="odd:bg-white even:bg-slate-50/70">
                        <td class="border-b border-r border-slate-200 px-2 py-2 font-mono text-slate-500">{{ index + 1 }}</td>
                        <td class="border-b border-r border-slate-200 px-2 py-2">
                            <p class="font-semibold text-slate-900">{{ item.name }}</p>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ item.sku || 'Manual item' }}</p>
                        </td>
                        <td class="border-b border-r border-slate-200 px-2 py-2 text-right font-semibold">
                            {{ formatCurrency(effectiveUnitPrice(item)) }}
                        </td>
                        <td class="border-b border-r border-slate-200 px-2 py-2">
                            <div class="flex items-center justify-center gap-1">
                                <button class="h-8 w-8 rounded-xl border border-slate-300 bg-white font-bold hover:bg-slate-100" @click="$emit('change-quantity', item, -1)">-</button>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="0.25"
                                    step="0.25"
                                    class="h-8 w-16 rounded-xl border border-slate-300 bg-slate-50 px-1 text-center font-semibold outline-none"
                                    @change="$emit('normalize-quantity', item)"
                                >
                                <button class="h-8 w-8 rounded-xl border border-slate-300 bg-white font-bold hover:bg-slate-100" @click="$emit('change-quantity', item, 1)">+</button>
                            </div>
                        </td>
                        <td class="border-b border-r border-slate-200 px-2 py-2">
                            <input
                                v-model.number="item.discount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-8 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-right outline-none"
                                @change="$emit('normalize-discount', item)"
                            >
                        </td>
                        <td class="border-b border-slate-200 px-2 py-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <span class="font-bold text-slate-900">{{ formatCurrency(lineTotal(item)) }}</span>
                                <button class="rounded-xl border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-red-600 hover:bg-red-100" @click="$emit('remove-item', item.product_id)">
                                    Del
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
defineProps({
    cart: {
        type: Array,
        required: true,
    },
    totalUnits: {
        type: Number,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
    effectiveUnitPrice: {
        type: Function,
        required: true,
    },
    lineTotal: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'change-quantity',
    'normalize-quantity',
    'normalize-discount',
    'remove-item',
]);
</script>
