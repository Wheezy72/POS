<template>
    <footer class="ml-auto w-full max-w-[28rem] rounded-3xl border border-slate-200 bg-white text-slate-950 shadow-sm">
        <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-2 px-4 py-4">
            <p class="text-sm font-bold uppercase tracking-[0.18em]">Subtotal</p>
            <p class="text-right text-lg font-black">{{ formatCurrency(subtotal) }}</p>
            <p class="text-sm font-bold uppercase tracking-[0.18em]">Tax</p>
            <p class="text-right text-lg font-black">{{ formatCurrency(tax) }}</p>
            <p class="border-t border-slate-200 pt-2 text-lg font-black uppercase tracking-[0.18em]">Grand total</p>
            <p class="border-t border-slate-200 pt-2 text-right text-4xl font-black tracking-tight text-emerald-600">{{ formatCurrency(grandTotal) }}</p>
        </div>
    </footer>
</template>

<script setup>
defineProps({
    subtotal: {
        type: Number,
        required: true,
    },
    tax: {
        type: Number,
        required: true,
    },
    grandTotal: {
        type: Number,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});
</script>
