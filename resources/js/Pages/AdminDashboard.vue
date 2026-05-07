<template>
    <Head title="Owner Dashboard" />

    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-[1800px] flex-col gap-6 px-4 py-4 sm:px-6 lg:px-8">
            <header class="overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-white px-6 py-6 lg:px-8">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.22em] text-slate-500">
                                <ChartBarIcon class="h-4 w-4" />
                                Owner analytics
                            </div>
                            <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950">Finance and stock command centre</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                Revenue, profit, stock pressure, payment health, and movement signals built for a fast retail shop.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <MetricCard label="Session" :value="sessionLabel" :hint="sessionHint">
                                <ShieldCheckIcon class="h-5 w-5" />
                            </MetricCard>
                            <MetricCard label="Month revenue" :value="formatCurrency(kpis.month_revenue)">
                                <BanknotesIcon class="h-5 w-5" />
                            </MetricCard>
                            <MetricCard label="Month profit" :value="formatCurrency(kpis.month_profit)" value-class="text-emerald-600" icon-class="bg-emerald-50 text-emerald-700">
                                <ArrowTrendingUpIcon class="h-5 w-5" />
                            </MetricCard>
                        </div>
                    </div>
                </div>
            </header>

            <main v-if="isAdmin" class="grid flex-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_28rem]">
                <div class="grid gap-6">
                    <LineChartCard
                        description="Net revenue and product-level gross profit for the last seven trading days."
                        :datasets="profitDatasets"
                        :error="error"
                        :labels="overview.profit_vs_revenue.labels"
                        :loading="loading"
                        title="Profit vs revenue"
                        @refresh="fetchOverview"
                    />

                    <LineChartCard
                        description="Revenue concentration by hour over the last 30 days. Use this to staff busy periods."
                        :datasets="hourlyDatasets"
                        :error="error"
                        :labels="overview.hourly_heatmap.labels"
                        :loading="loading"
                        title="Hourly sales pressure"
                        @refresh="fetchOverview"
                    />
                </div>

                <aside class="grid gap-6 xl:sticky xl:top-6 xl:self-start">
                    <CriticalStockList :items="overview.critical_alerts" :loading="loading" />

                    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-black tracking-tight text-slate-950">Practical owner signals</h2>
                        <div class="mt-6 grid gap-3">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Profit intensity</p>
                                <p class="mt-2 text-2xl font-black text-emerald-600">{{ profitMarginText }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Shows whether growth is coming with healthy margin, not just volume.</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Runway alerts</p>
                                <p class="mt-2 text-2xl font-black text-red-600">{{ kpis.critical_alert_count }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Prioritise reorders before high-movement goods disappear.</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Security posture</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Reports are fetched only after admin session authentication. Charts are bundled locally, not loaded from CDN.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </main>
        </div>

        <PinLockOverlay
            v-if="!isAdmin"
            ref="pinInput"
            v-model:pin="pin"
            :blocked-role="blockedRole"
            :busy="pinBusy"
            :heading="overlayHeading"
            :label="overlayLabel"
            :show="!isAdmin"
            @submit="loginWithPin"
        />

        <ToastStack :toasts="toasts" />
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    ArrowTrendingUpIcon,
    BanknotesIcon,
    ChartBarIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import CriticalStockList from '../Components/Admin/CriticalStockList.vue';
import LineChartCard from '../Components/Admin/LineChartCard.vue';
import MetricCard from '../Components/Admin/MetricCard.vue';
import PinLockOverlay from '../Components/Pos/PinLockOverlay.vue';
import ToastStack from '../Components/Pos/ToastStack.vue';
import { formatCurrency } from '../utils/formatters';
import { useToasts } from '../composables/pos/useToasts';

defineProps({
    overlayHeading: {
        type: String,
        default: 'Unlock the admin dashboard',
    },
    overlayLabel: {
        type: String,
        default: 'Admin PIN',
    },
});

const page = usePage();
const currentUser = ref(page.props.auth?.user ?? null);
const pinInput = ref(null);
const pin = ref('');
const pinBusy = ref(false);
const loading = ref(false);
const error = ref('');
const { toasts, toast } = useToasts();

const overview = ref({
    kpis: {
        month_revenue: 0,
        month_profit: 0,
        critical_alert_count: 0,
    },
    profit_vs_revenue: {
        labels: [],
        revenue: [],
        profit: [],
    },
    critical_alerts: [],
    hourly_heatmap: {
        labels: [],
        revenue: [],
    },
});

const isAdmin = computed(() => currentUser.value?.role === 'admin');
const blockedRole = computed(() => currentUser.value && !isAdmin.value ? currentUser.value.role : null);
const kpis = computed(() => overview.value.kpis);
const sessionLabel = computed(() => currentUser.value?.name ?? 'Admin sign-in required');
const sessionHint = computed(() => currentUser.value?.role ?? 'locked');
const profitMarginText = computed(() => {
    const revenue = Number(kpis.value.month_revenue || 0);
    const profit = Number(kpis.value.month_profit || 0);

    if (revenue <= 0) {
        return '0.0%';
    }

    return `${Math.round((profit / revenue) * 1000) / 10}%`;
});

const profitDatasets = computed(() => [
    {
        label: 'Revenue',
        data: overview.value.profit_vs_revenue.revenue,
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.1)',
        tension: 0.35,
    },
    {
        label: 'Profit',
        data: overview.value.profit_vs_revenue.profit,
        borderColor: '#059669',
        backgroundColor: 'rgba(5, 150, 105, 0.1)',
        tension: 0.35,
    },
]);

const hourlyDatasets = computed(() => [
    {
        label: 'Revenue',
        data: overview.value.hourly_heatmap.revenue,
        borderColor: '#d97706',
        backgroundColor: 'rgba(217, 119, 6, 0.18)',
        tension: 0.25,
        fill: true,
    },
]);

onMounted(() => {
    if (isAdmin.value) {
        fetchOverview();
        return;
    }

    nextTick(() => pinInput.value?.focus?.());
});

async function fetchOverview() {
    loading.value = true;
    error.value = '';

    try {
        const response = await window.axios.get('/api/admin/reports/dashboard-overview');
        overview.value = response.data;
    } catch (requestError) {
        error.value = requestError?.response?.data?.message ?? 'Unable to load dashboard insights.';
        toast('Dashboard unavailable', error.value, 'error');
    } finally {
        loading.value = false;
    }
}

async function loginWithPin() {
    if (!pin.value) {
        toast('PIN required', 'Enter an admin PIN to unlock the owner dashboard.', 'error');
        return;
    }

    pinBusy.value = true;

    try {
        const response = await window.axios.post('/api/auth/pin-login', {
            pin: pin.value,
        });

        if (response.data.user.role !== 'admin') {
            currentUser.value = response.data.user;
            toast('Access denied', 'Only admin accounts can open the owner dashboard.', 'error');
            return;
        }

        currentUser.value = response.data.user;
        updateCsrfToken(response.data.csrf_token);
        pin.value = '';
        toast('Dashboard unlocked', `${response.data.user.name} can now view owner insights.`, 'success');
        await fetchOverview();
    } catch (requestError) {
        toast('Access denied', requestError?.response?.data?.message ?? 'Unable to unlock the dashboard.', 'error');
        nextTick(() => pinInput.value?.focus?.());
    } finally {
        pinBusy.value = false;
    }
}

function updateCsrfToken(token) {
    if (!token) {
        return;
    }

    const meta = document.head.querySelector('meta[name="csrf-token"]');

    if (meta) {
        meta.setAttribute('content', token);
    }

    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}
</script>
