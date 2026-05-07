<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="pin-lock-title">
        <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 text-slate-900 shadow-2xl">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <LockClosedIcon class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">PIN lock</p>
                    <h1 id="pin-lock-title" class="mt-1 text-3xl font-black">{{ heading }}</h1>
                </div>
            </div>

            <p v-if="blockedRole" class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Logged-in role "{{ blockedRole }}" cannot operate the cashier terminal.
            </p>

            <label class="mt-5 block text-[11px] font-bold uppercase tracking-[0.25em] text-slate-500">{{ label }}</label>
            <input
                ref="pinInput"
                :value="pin"
                type="password"
                inputmode="numeric"
                maxlength="6"
                class="mt-2 h-14 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-2xl font-black tracking-[0.4em] outline-none placeholder:tracking-normal placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white"
                placeholder="0000"
                @input="$emit('update:pin', $event.target.value.trim())"
                @keydown.enter.prevent="$emit('submit')"
            >

            <button class="mt-5 flex w-full items-center justify-between rounded-2xl border border-emerald-500 bg-emerald-500 px-4 py-4 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy" @click="$emit('submit')">
                <span>{{ busy ? 'Unlocking…' : 'Unlock register' }}</span>
                <ArrowPathIcon v-if="busy" class="h-5 w-5 animate-spin" />
                <KeyIcon v-else class="h-5 w-5" />
            </button>

            <p class="mt-4 text-xs text-slate-500">Enter a valid staff PIN to continue.</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { ArrowPathIcon, KeyIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    heading: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    blockedRole: {
        type: String,
        default: null,
    },
    pin: {
        type: String,
        required: true,
    },
    busy: {
        type: Boolean,
        required: true,
    },
});

defineEmits([
    'submit',
    'update:pin',
]);

const pinInput = ref(null);

defineExpose({
    focus() {
        pinInput.value?.focus();
    },
});
</script>
