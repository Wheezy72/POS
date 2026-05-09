<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                    <MagnifyingGlassIcon class="h-5 w-5" />
                </span>
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Search results</p>
            </div>
            <button class="rounded-xl border border-sky-200 bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-sky-700 hover:bg-sky-100" @click="$emit('open-search')">
                [F2] Search
            </button>
        </div>

        <ProductResultList
            :busy="searchBusy"
            empty-message="Start typing to search products."
            :format-currency="formatCurrency"
            :products="searchResults"
            @select="$emit('select-product', $event)"
        />
    </section>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import ProductResultList from './ProductResultList.vue';

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
