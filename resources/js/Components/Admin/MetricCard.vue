<template>
    <section class="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-4">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">{{ label }}</p>
                <p class="mt-2 truncate text-2xl font-medium tracking-tight tabular-nums" :class="valueClass">{{ value }}</p>
                <p v-if="hint" class="mt-1 truncate text-xs leading-5 text-zinc-500">{{ hint }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" :class="iconClass">
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
        default: 'text-zinc-50',
    },
    iconClass: {
        type: String,
        default: 'border-zinc-800 bg-zinc-900 text-zinc-300',
    },
});
</script>
