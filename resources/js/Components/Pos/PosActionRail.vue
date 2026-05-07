<template>
    <aside class="grid gap-3">
        <button class="rounded-3xl border border-yellow-300 bg-yellow-300 px-4 py-4 text-left text-slate-950 shadow-sm hover:bg-yellow-200" @click="$emit('new-sale')">
            <p class="text-lg font-black">[F1] New</p>
        </button>
        <button class="rounded-3xl border border-sky-600 bg-sky-600 px-4 py-4 text-left text-white shadow-sm hover:bg-sky-500" @click="$emit('open-search')">
            <p class="text-lg font-black">[F2] Search</p>
        </button>
        <button class="rounded-3xl border border-emerald-600 bg-emerald-600 px-4 py-4 text-left text-white shadow-sm hover:bg-emerald-500" @click="$emit('open-pay')">
            <p class="text-lg font-black">[F4] Pay</p>
        </button>
        <button class="rounded-3xl border border-red-600 bg-red-600 px-4 py-4 text-left text-white shadow-sm hover:bg-red-500" @click="$emit('logout')">
            <p class="text-lg font-black">[F10] Logout</p>
        </button>

        <section class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Selected payment</p>
            <div v-if="selectedLivePayment" class="mt-2">
                <p class="font-semibold text-slate-900">{{ selectedLivePayment.customer_name }}</p>
                <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ selectedLivePayment.transaction_code }}</p>
                <p class="mt-1 font-bold text-emerald-600">{{ formatCurrency(Number(selectedLivePayment.amount)) }}</p>
            </div>
            <p v-else class="mt-2 text-sm text-slate-500">Nothing selected.</p>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Pending STK</p>
            <p class="mt-2 font-mono text-sm text-amber-600">{{ stkCheckoutRequestId || 'None' }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ stkStatusMessage }}</p>
        </section>
    </aside>
</template>

<script setup>
defineProps({
    selectedLivePayment: {
        type: Object,
        default: null,
    },
    stkCheckoutRequestId: {
        type: String,
        required: true,
    },
    stkStatusMessage: {
        type: String,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'new-sale',
    'open-search',
    'open-pay',
    'logout',
]);
</script>
