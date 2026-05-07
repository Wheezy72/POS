<template>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <div>
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">M-PESA live feed</p>
            </div>
            <button class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-700 hover:bg-emerald-100" @click="$emit('refresh')">
                Refresh
            </button>
        </div>
        <div class="max-h-56 overflow-auto">
            <div v-if="liveFeedBusy" class="px-4 py-6 text-sm text-slate-500">Loading live feed…</div>
            <div v-else-if="livePayments.length === 0" class="px-4 py-6 text-sm text-slate-500">No pending M-PESA deposits.</div>
            <button
                v-for="payment in livePayments"
                :key="payment.id"
                class="w-full border-b border-slate-200 px-4 py-3 text-left hover:bg-slate-50"
                :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-50' : ''"
                @click="$emit('select-payment', payment)"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ payment.customer_name }}</p>
                        <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ payment.transaction_code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-emerald-600">{{ formatCurrency(Number(payment.amount)) }}</p>
                        <p class="text-[11px] text-slate-500">{{ shortTimestamp(payment.created_at) }}</p>
                    </div>
                </div>
            </button>
        </div>
    </section>
</template>

<script setup>
defineProps({
    liveFeedBusy: {
        type: Boolean,
        required: true,
    },
    livePayments: {
        type: Array,
        required: true,
    },
    selectedLivePayment: {
        type: Object,
        default: null,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
    shortTimestamp: {
        type: Function,
        required: true,
    },
});

defineEmits([
    'refresh',
    'select-payment',
]);
</script>
