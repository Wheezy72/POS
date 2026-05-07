<template>
    <div class="fixed right-3 top-3 z-50 flex w-full max-w-sm flex-col gap-2">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="border px-3 py-2 shadow-lg"
            :class="toast.variant === 'error' ? 'border-red-200 bg-white text-red-700' : toast.variant === 'success' ? 'border-emerald-200 bg-white text-emerald-700' : 'border-slate-200 bg-white text-slate-900'"
        >
            <p class="font-bold uppercase tracking-[0.18em]">{{ toast.title }}</p>
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
