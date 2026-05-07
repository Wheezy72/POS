<template>
    <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="product-search-title">
        <div class="w-full max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p id="product-search-title" class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Product search</p>
                        <p class="mt-1 text-xs text-slate-500">Type SKU, barcode, or name. Press Enter to search immediately.</p>
                    </div>
                </div>
                <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold hover:bg-slate-50" @click="$emit('close')">Esc</button>
            </div>
            <div class="p-4">
                <div class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input
                        ref="searchInput"
                        :value="searchQuery"
                        type="text"
                        autocomplete="off"
                        class="h-14 w-full rounded-2xl border border-slate-300 bg-slate-50 pl-12 pr-4 text-lg font-bold outline-none focus:border-sky-500 focus:bg-white"
                        placeholder="Search by barcode, SKU, or product name"
                        @input="$emit('update:search-query', $event.target.value.trim())"
                        @keydown.enter.prevent="$emit('search', searchQuery)"
                    >
                </div>
                <div class="mt-4 rounded-2xl border border-slate-200">
                    <ProductResultList
                        :busy="searchBusy"
                        :empty-message="searchQuery ? 'No products found.' : 'Start typing to search inventory.'"
                        :format-currency="formatCurrency"
                        height-class="max-h-[24rem]"
                        :products="searchResults"
                        @select="$emit('select-product', $event)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import ProductResultList from './ProductResultList.vue';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    searchQuery: {
        type: String,
        required: true,
    },
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
    'close',
    'search',
    'select-product',
    'update:search-query',
]);

const searchInput = ref(null);

defineExpose({
    focus() {
        searchInput.value?.focus();
    },
});
</script>
