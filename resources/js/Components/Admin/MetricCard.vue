<template>
    <section class="rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">{{ label }}</p>
                <p class="mt-3 text-3xl font-black tracking-tight" :class="valueClass">{{ value }}</p>
                <p v-if="hint" class="mt-2 text-sm leading-6 text-slate-500">{{ hint }}</p>
            </div>
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl" :class="iconClass">
                <slot />
            </span>
        </div>
    </section>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    hint: {
        type: String,
        default: '',
    },
    valueClass: {
        type: String,
        default: 'text-slate-950',
    },
    iconClass: {
        type: String,
        default: 'bg-slate-100 text-slate-700',
    },
});
</script>
