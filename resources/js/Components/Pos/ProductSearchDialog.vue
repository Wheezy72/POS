<template>
    <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-950/85 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="product-search-title">
        <div class="w-full max-w-4xl rounded-2xl border border-zinc-800 bg-zinc-900 shadow-xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-zinc-800 px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-500/40 bg-blue-500/10 text-blue-300">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p id="product-search-title" class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Product search</p>
                        <p class="mt-0.5 text-xs text-zinc-400">Type SKU, barcode, or name. Press Enter to search immediately.</p>
                    </div>
                </div>
                <button class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-800" @click="$emit('close')">Esc</button>
            </div>
            <div class="p-5">
                <div class="relative">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
                    <input
                        ref="searchInput"
                        :value="searchQuery"
                        type="text"
                        autocomplete="off"
                        class="h-12 w-full rounded-xl border border-zinc-800 bg-zinc-950 pl-12 pr-4 text-base text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-blue-500/60 focus:ring-2 focus:ring-blue-500/30"
                        placeholder="Search by barcode, SKU, or product name"
                        @input="$emit('update:search-query', $event.target.value.trim())"
                        @keydown.enter.prevent="$emit('search', searchQuery)"
                    >
                </div>
                <div class="mt-4 overflow-hidden rounded-xl border border-zinc-800">
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
import { Search as MagnifyingGlassIcon } from 'lucide-vue-next';
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
