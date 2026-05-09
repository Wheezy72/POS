<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/85 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="pin-lock-title">
        <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 p-6 text-zinc-100">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-300">
                    <LockClosedIcon class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">PIN lock</p>
                    <h1 id="pin-lock-title" class="mt-1 text-2xl font-medium tracking-tight text-zinc-50">{{ heading }}</h1>
                </div>
            </div>

            <p v-if="blockedRole" class="mt-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
                Logged-in role "{{ blockedRole }}" cannot operate the cashier terminal.
            </p>

            <label class="mt-5 block text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">{{ label }}</label>
            <input
                ref="pinInput"
                :value="pin"
                type="password"
                inputmode="numeric"
                maxlength="6"
                class="mt-2 h-14 w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 text-2xl font-medium tracking-[0.4em] text-zinc-50 outline-none placeholder:tracking-normal placeholder:text-zinc-600 focus:border-emerald-500/60 focus:ring-2 focus:ring-emerald-500/30"
                placeholder="0000"
                @input="$emit('update:pin', $event.target.value.trim())"
                @keydown.enter.prevent="$emit('submit')"
            >

            <button class="mt-5 flex w-full items-center justify-between rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3.5 text-left text-sm font-medium uppercase tracking-[0.18em] text-emerald-300 transition hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy" @click="$emit('submit')">
                <span>{{ busy ? 'Unlocking…' : 'Unlock register' }}</span>
                <ArrowPathIcon v-if="busy" class="h-5 w-5 animate-spin" />
                <KeyIcon v-else class="h-5 w-5" />
            </button>

            <p class="mt-4 text-xs text-zinc-500">Enter a valid staff PIN to continue.</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { RefreshCw as ArrowPathIcon, Key as KeyIcon, Lock as LockClosedIcon } from 'lucide-vue-next';

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
