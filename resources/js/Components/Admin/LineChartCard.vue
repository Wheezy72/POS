<template>
    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-medium tracking-tight text-zinc-50">{{ title }}</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ description }}</p>
            </div>
            <button class="rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-900 disabled:opacity-50" :disabled="loading" @click="$emit('refresh')">
                Refresh
            </button>
        </div>

        <div class="mt-5 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
            <div v-if="loading" class="flex min-h-[22rem] items-center justify-center text-sm text-zinc-500">Loading chart…</div>
            <div v-else-if="error" class="flex min-h-[22rem] items-center justify-center text-sm text-rose-300">{{ error }}</div>
            <div v-else class="h-[22rem]">
                <canvas ref="canvas"></canvas>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Chart, BarElement, CategoryScale, Legend, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

Chart.register(BarElement, CategoryScale, Legend, LinearScale, LineElement, PointElement, Tooltip);

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    labels: {
        type: Array,
        required: true,
    },
    datasets: {
        type: Array,
        required: true,
    },
    loading: {
        type: Boolean,
        required: true,
    },
    error: {
        type: String,
        default: '',
    },
});

defineEmits(['refresh']);

const canvas = ref(null);
let chart = null;

watch(
    () => [props.labels, props.datasets, props.loading, props.error],
    async () => {
        await nextTick();
        renderChart();
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => {
    chart?.destroy();
});

function renderChart() {
    if (props.loading || props.error || !canvas.value) {
        return;
    }

    chart?.destroy();
    chart = new Chart(canvas.value, {
        type: 'line',
        data: {
            labels: props.labels,
            datasets: props.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        color: '#a1a1aa',
                    },
                },
                tooltip: {
                    backgroundColor: '#18181b',
                    borderColor: '#27272a',
                    borderWidth: 1,
                    titleColor: '#fafafa',
                    bodyColor: '#d4d4d8',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(113, 113, 122, 0.18)' },
                    ticks: { color: '#71717a' },
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#71717a' },
                },
            },
        },
    });
}
</script>
