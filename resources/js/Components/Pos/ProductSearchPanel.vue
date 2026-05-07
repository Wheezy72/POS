<template>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div>
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Search results</p>
            </div>
            <button class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-sky-700 hover:bg-sky-100" @click="$emit('open-search')">
                [F2] Search
            </button>
        </div>

        <div class="max-h-56 overflow-auto">
            <div v-if="searchBusy" class="px-4 py-6 text-sm text-slate-500">Searching…</div>
            <div v-else-if="searchResults.length === 0" class="px-3 py-6 text-sm text-slate-500">
                Start typing to search products.
            </div>
            <button
                v-for="product in searchResults"
                :key="product.id"
                class="grid w-full grid-cols-[1fr_auto_auto] gap-3 border-b border-slate-200 px-4 py-3 text-left hover:bg-slate-50"
                @click="$emit('select-product', product)"
            >
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ product.name }}</p>
                    <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ product.sku }}</p>
                </div>
                <p class="text-right text-sm font-bold text-slate-900">{{ formatCurrency(Number(product.base_price)) }}</p>
                <p class="text-right text-[11px] uppercase tracking-[0.16em]" :class="Number(product.stock_quantity) < 10 ? 'text-red-600' : 'text-slate-500'">
                    {{ Number(product.stock_quantity).toFixed(2) }}
                </p>
            </button>
        </div>
    </section>
</template>

<script setup>
defineProps({
    searchBusy: {
        type: Boolean,
        required: true,
    },
    searchResults: {
        type: Array,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'open-search',
    'select-product',
]);
</script>
