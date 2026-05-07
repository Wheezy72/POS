<template>
    <section class="rounded-[32px] border border-red-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-950">Critical stock runway</h2>
                <p class="mt-2 text-sm text-slate-600">Items likely to run out in less than three days.</p>
            </div>
            <span class="rounded-2xl bg-red-50 px-4 py-2 text-sm font-black text-red-700">{{ items.length }}</span>
        </div>

        <div class="mt-6 space-y-3">
            <div v-if="!loading && items.length === 0" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                No critical runway alerts right now.
            </div>
            <div v-if="loading" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                Loading stock alerts…
            </div>
            <article v-for="item in items" :key="item.sku" class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-950">{{ item.name }}</p>
                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">{{ item.sku }}</p>
                    </div>
                    <span class="rounded-2xl bg-red-50 px-3 py-1 text-xs font-bold text-red-700">{{ item.runway_days }} days</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div class="rounded-2xl bg-white px-3 py-3">
                        <p class="uppercase tracking-[0.18em] text-slate-500">On hand</p>
                        <p class="mt-2 text-sm font-bold text-slate-950">{{ item.stock_quantity }}</p>
                    </div>
                    <div class="rounded-2xl bg-white px-3 py-3">
                        <p class="uppercase tracking-[0.18em] text-slate-500">Daily movement</p>
                        <p class="mt-2 text-sm font-bold text-slate-950">{{ item.average_daily_quantity_sold }}</p>
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
