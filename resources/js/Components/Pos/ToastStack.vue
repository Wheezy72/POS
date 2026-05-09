<template>
    <div v-auto-animate class="fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="rounded-xl border px-4 py-3 shadow-lg shadow-black/40 backdrop-blur"
            :class="toast.variant === 'error'
                ? 'border-rose-500/40 bg-rose-500/10 text-rose-200'
                : toast.variant === 'success'
                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
                    : 'border-zinc-700 bg-zinc-900/95 text-zinc-100'"
        >
            <p class="text-[11px] font-medium uppercase tracking-[0.18em] opacity-80">{{ toast.title }}</p>
            <p class="mt-1 text-sm">{{ toast.message }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    toasts: {
        type: Array,
        required: true,
    },
});
</script>
