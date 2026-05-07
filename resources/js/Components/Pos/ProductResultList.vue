<template>
    <div class="overflow-auto" :class="heightClass">
        <div v-if="busy" class="flex items-center gap-2 px-4 py-6 text-sm text-slate-500">
            <ArrowPathIcon class="h-4 w-4 animate-spin" />
            Searching inventory…
        </div>
        <div v-else-if="products.length === 0" class="px-4 py-6 text-sm text-slate-500">
            {{ emptyMessage }}
        </div>
        <button
            v-for="product in products"
            :key="product.id"
            class="grid w-full grid-cols-[minmax(0,1fr)_auto_auto] gap-3 border-b border-slate-200 px-4 py-3 text-left transition hover:bg-slate-50 focus:bg-sky-50 focus:outline-none"
            @click="$emit('select', product)"
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
</template>

<script setup>
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

defineProps({
    busy: {
        type: Boolean,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
    emptyMessage: {
        type: String,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
    heightClass: {
        type: String,
        default: 'max-h-56',
    },
});

defineEmits(['select']);
</script>
