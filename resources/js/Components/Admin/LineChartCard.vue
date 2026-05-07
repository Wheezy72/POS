<template>
    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-950">{{ title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ description }}</p>
            </div>
            <button class="rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-50" :disabled="loading" @click="$emit('refresh')">
                Refresh
            </button>
        </div>

        <div class="mt-6 rounded-[28px] border border-slate-200 bg-slate-50 p-4">
            <div v-if="loading" class="flex min-h-[22rem] items-center justify-center text-sm text-slate-500">Loading chart…</div>
            <div v-else-if="error" class="flex min-h-[22rem] items-center justify-center text-sm text-red-600">{{ error }}</div>
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
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.25)' },
                },
                x: {
                    grid: { display: false },
                },
            },
        },
    });
}
</script>
