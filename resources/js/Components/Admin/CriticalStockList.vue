<template>
    <section class="rounded-2xl border border-rose-500/30 bg-zinc-900 p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-medium tracking-tight text-zinc-50">Critical stock runway</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-400">Items likely to run out in less than three days.</p>
            </div>
            <span class="rounded-full border border-rose-500/40 bg-rose-500/10 px-3 py-1 text-xs font-medium text-rose-300 tabular-nums">{{ items.length }}</span>
        </div>

        <div v-auto-animate class="mt-5 space-y-2.5">
            <div v-if="!loading && items.length === 0" class="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-zinc-500">
                No critical runway alerts right now.
            </div>
            <div v-if="loading" class="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-4 text-sm text-zinc-500">
                Loading stock alerts…
            </div>
            <article v-for="item in items" :key="item.sku" class="rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-3.5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-zinc-50">{{ item.name }}</p>
                        <p class="mt-0.5 text-[11px] uppercase tracking-[0.18em] text-zinc-500">{{ item.sku }}</p>
                    </div>
                    <span class="rounded-full border border-rose-500/40 bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-medium text-rose-300 tabular-nums">{{ item.runway_days }} days</span>
                </div>
                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 px-3 py-2">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-zinc-500">On hand</p>
                        <p class="mt-1 text-sm font-medium text-zinc-100 tabular-nums">{{ item.stock_quantity }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-800 bg-zinc-900 px-3 py-2">
                        <p class="text-[10px] uppercase tracking-[0.22em] text-zinc-500">Daily movement</p>
                        <p class="mt-1 text-sm font-medium text-zinc-100 tabular-nums">{{ item.average_daily_quantity_sold }}</p>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
defineProps({
    items: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        required: true,
    },
});
</script>
