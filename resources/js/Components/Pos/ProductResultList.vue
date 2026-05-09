<template>
    <div class="overflow-auto" :class="heightClass">
        <div v-if="busy" class="flex items-center gap-2 px-4 py-6 text-sm text-zinc-400">
            <ArrowPathIcon class="h-4 w-4 animate-spin" />
            Searching inventory…
        </div>
        <div v-else-if="products.length === 0" class="px-4 py-6 text-sm text-zinc-500">
            {{ emptyMessage }}
        </div>
        <button
            v-for="product in products"
            :key="product.id"
            class="grid w-full grid-cols-[minmax(0,1fr)_auto_auto] gap-3 border-b border-zinc-800 px-4 py-3 text-left transition hover:bg-zinc-800/60 focus:bg-blue-500/10 focus:outline-none"
            @click="$emit('select', product)"
        >
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-100">{{ product.name }}</p>
                <p class="truncate text-[11px] uppercase tracking-[0.18em] text-zinc-500">{{ product.sku }}</p>
            </div>
            <p class="text-right text-sm font-medium text-zinc-100 tabular-nums">{{ formatCurrency(Number(product.base_price)) }}</p>
            <p class="text-right text-[11px] uppercase tracking-[0.18em] tabular-nums" :class="Number(product.stock_quantity) < 10 ? 'text-rose-400' : 'text-zinc-500'">
                {{ Number(product.stock_quantity).toFixed(2) }}
            </p>
        </button>
    </div>
</template>

<script setup>
import { RefreshCw as ArrowPathIcon } from 'lucide-vue-next';

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
