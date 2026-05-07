<template>
    <header class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)_14rem]">
        <section class="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Clock</p>
            <p class="mt-2 text-3xl font-black tracking-[0.08em] text-emerald-600">{{ clock }}</p>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Transaction</p>
                    <p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ transactionId }}</p>
                </div>
                <div class="text-right text-[11px] uppercase tracking-[0.18em] text-slate-500">
                    <p>Operator</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ currentUser ? currentUser.name : 'Register locked' }}</p>
                </div>
            </div>

            <label class="mt-4 block text-[11px] font-bold uppercase tracking-[0.25em] text-slate-500">
                Barcode or SKU
            </label>
            <input
                ref="scannerInput"
                :value="barcode"
                type="text"
                autocomplete="off"
                class="mt-2 h-16 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-2xl font-black tracking-wide text-slate-950 outline-none ring-0 placeholder:font-semibold placeholder:text-slate-400 focus:border-sky-500 focus:bg-white"
                placeholder="Scan barcode or type SKU"
                @input="$emit('update:barcode', $event.target.value.trim())"
                @keydown.enter.prevent="$emit('search')"
            >
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white px-5 py-4 text-right shadow-sm">
            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">Session</p>
            <p class="mt-2 text-sm font-bold uppercase tracking-[0.18em]" :class="currentUser ? 'text-emerald-600' : 'text-red-600'">
                {{ currentUser ? currentUser.role : 'Locked' }}
            </p>
            <p class="mt-4 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                {{ resultCount }} results ready
            </p>
        </section>
    </header>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    clock: {
        type: String,
        required: true,
    },
    transactionId: {
        type: String,
        required: true,
    },
    currentUser: {
        type: Object,
        default: null,
    },
    barcode: {
        type: String,
        required: true,
    },
    resultCount: {
        type: Number,
        required: true,
    },
});

defineEmits([
    'search',
    'update:barcode',
]);

const scannerInput = ref(null);

defineExpose({
    focus() {
        scannerInput.value?.focus();
    },
    select() {
        scannerInput.value?.select();
    },
});
</script>
