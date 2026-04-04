<!DOCTYPE html>
<html lang="en" class="h-full bg-[#020617]">
@php
    /** @var \App\Models\User|null $authUser */
    $authUser = auth()->user();
    $initialUser = $authUser !== null && $authUser->role === 'admin' ? [
        'id' => (string) $authUser->getAuthIdentifier(),
        'name' => $authUser->name,
        'role' => $authUser->role,
    ] : null;
    $initialBlockedRole = $authUser !== null && $authUser->role !== 'admin'
        ? $authUser->role
        : null;
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duka-App CFO Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-[#020617] text-slate-100 antialiased">
    <div
        x-data="cfoConsole({
            authenticated: @js($initialUser !== null),
            user: @js($initialUser),
            blockedRole: @js($initialBlockedRole),
        })"
        x-init="boot()"
        class="relative min-h-screen overflow-hidden"
    >
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-emerald-500/10 to-transparent"></div>
            <div class="absolute left-0 top-20 h-80 w-80 rounded-full bg-cyan-500/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-violet-500/10 blur-3xl"></div>
        </div>

        <div class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="pointer-events-auto rounded-3xl border border-white/10 bg-slate-900/90 px-4 py-4 shadow-2xl shadow-slate-950/30 backdrop-blur-md">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-2xl"
                            :class="toast.variant === 'success' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300'">
                            <svg x-show="toast.variant === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <svg x-show="toast.variant !== 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm8.25-.75a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white" x-text="toast.title"></p>
                            <p class="mt-1 text-sm leading-6 text-slate-300" x-text="toast.message"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-[1800px] flex-col gap-6 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <header class="overflow-hidden rounded-[32px] border border-white/10 bg-slate-950/70 shadow-2xl shadow-slate-950/20 backdrop-blur-md">
                <div class="border-b border-white/10 bg-gradient-to-r from-slate-900 via-slate-950 to-emerald-800 px-6 py-6 lg:px-8">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="inline-flex items-center rounded-2xl border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-200">
                                CFO Command Center
                            </div>
                            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-white">Duka-App Finance Deck</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
                                Revenue, real profit, stock runway, and trading tempo in one dark-mode finance cockpit.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-3xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-md">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Finance session</p>
                                <p class="mt-2 text-lg font-semibold text-white" x-text="user ? user.name : 'Awaiting admin PIN'"></p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400" x-text="user ? user.role : 'locked'"></p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-md">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Month revenue</p>
                                <p class="mt-2 text-2xl font-semibold text-white" x-text="formatCurrency(kpis.month_revenue)"></p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-md">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Month profit</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-300" x-text="formatCurrency(kpis.month_profit)"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="grid flex-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_28rem]">
                <div class="grid gap-6">
                    <section class="rounded-[32px] border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-md lg:p-8">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-white">Profit vs. Revenue</h2>
                                <p class="mt-2 text-sm text-slate-400">Real profit is calculated after product cost deduction, not from gross sales alone.</p>
                            </div>
                            <button
                                type="button"
                                @click="fetchOverview()"
                                class="inline-flex h-11 items-center rounded-2xl border border-white/10 bg-white/10 px-4 text-sm font-semibold text-white transition hover:bg-white/15"
                            >
                                Refresh
                            </button>
                        </div>

                        <div class="mt-6 rounded-[28px] border border-white/10 bg-slate-900/80 p-4">
                            <div x-show="loading" x-cloak class="flex min-h-[22rem] items-center justify-center text-sm text-slate-400">Loading finance data…</div>
                            <div x-show="!loading && !error" class="h-[22rem]">
                                <canvas x-ref="profitChart"></canvas>
                            </div>
                            <div x-show="error" x-cloak class="flex min-h-[22rem] items-center justify-center text-sm text-red-300" x-text="error"></div>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-md lg:p-8">
                        <div>
                            <h2 class="text-xl font-semibold text-white">Hourly Heatmap</h2>
                            <p class="mt-2 text-sm text-slate-400">Revenue concentration across the trading day over the last 30 days.</p>
                        </div>

                        <div class="mt-6 rounded-[28px] border border-white/10 bg-slate-900/80 p-4">
                            <div x-show="loading" x-cloak class="flex min-h-[22rem] items-center justify-center text-sm text-slate-400">Loading hourly trend…</div>
                            <div x-show="!loading && !error" class="h-[22rem]">
                                <canvas x-ref="hourlyChart"></canvas>
                            </div>
                            <div x-show="error" x-cloak class="flex min-h-[22rem] items-center justify-center text-sm text-red-300" x-text="error"></div>
                        </div>
                    </section>
                </div>

                <aside class="grid gap-6 xl:sticky xl:top-6 xl:self-start">
                    <section class="rounded-[32px] border border-red-500/20 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-md">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-white">Critical Stock Runway</h2>
                                <p class="mt-2 text-sm text-slate-400">Items projected to run out in less than three days.</p>
                            </div>
                            <div class="inline-flex h-11 min-w-11 items-center justify-center rounded-2xl bg-red-500/15 px-3 text-sm font-semibold text-red-300" x-text="criticalAlerts.length"></div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <template x-if="!loading && criticalAlerts.length === 0">
                                <div class="rounded-3xl border border-white/10 bg-slate-900/80 px-4 py-5 text-sm text-slate-400">
                                    No critical runway alerts right now.
                                </div>
                            </template>

                            <template x-for="item in criticalAlerts" :key="item.sku">
                                <article class="rounded-3xl border border-white/10 bg-slate-900/80 px-4 py-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white" x-text="item.name"></p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500" x-text="item.sku"></p>
                                        </div>
                                        <span class="rounded-2xl bg-red-500/15 px-3 py-1 text-xs font-semibold text-red-300" x-text="`${item.runway_days} days`"></span>
                                    </div>
                                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                        <div class="rounded-2xl bg-slate-800/80 px-3 py-3">
                                            <p class="uppercase tracking-[0.18em] text-slate-500">On hand</p>
                                            <p class="mt-2 text-sm font-semibold text-white" x-text="item.stock_quantity"></p>
                                        </div>
                                        <div class="rounded-2xl bg-slate-800/80 px-3 py-3">
                                            <p class="uppercase tracking-[0.18em] text-slate-500">Daily sell-through</p>
                                            <p class="mt-2 text-sm font-semibold text-white" x-text="item.average_daily_quantity_sold"></p>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/10 bg-slate-950/70 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-md">
                        <h2 class="text-xl font-semibold text-white">Finance Signals</h2>
                        <div class="mt-6 grid gap-3">
                            <div class="rounded-3xl border border-white/10 bg-slate-900/80 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Profit intensity</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-300" x-text="profitMarginText()"></p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-slate-900/80 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Runway alerts</p>
                                <p class="mt-2 text-2xl font-semibold text-white" x-text="kpis.critical_alert_count"></p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-slate-900/80 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Auth path</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">The dashboard uses PIN session auth and fetches protected admin report endpoints after unlocking.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </main>
        </div>

        <div
            x-cloak
            x-show="!authenticated"
            x-transition.opacity
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/80 px-4 py-4 backdrop-blur-md"
        >
            <div class="w-full max-w-md overflow-hidden rounded-[32px] border border-white/10 bg-white/10 shadow-2xl shadow-slate-950/30 backdrop-blur-md">
                <div class="border-b border-white/10 px-6 py-6">
                    <div class="inline-flex items-center rounded-2xl border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">
                        Finance unlock
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white">Unlock the CFO Console</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Enter the admin PIN to load financial analytics and protected report endpoints.
                    </p>
                </div>

                <div class="grid gap-5 px-6 py-6">
                    <template x-if="blockedRole">
                        <div class="rounded-2xl border border-amber-400/20 bg-amber-500/10 p-4 text-sm text-amber-100">
                            A <span class="font-semibold" x-text="blockedRole"></span> session is active, but only admin accounts can open the finance console.
                        </div>
                    </template>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Demo finance PIN</p>
                        <p class="mt-2 text-sm font-semibold text-white">CFO Console · 9999</p>
                    </div>

                    <div>
                        <label for="admin-pin" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Admin PIN</label>
                        <input
                            id="admin-pin"
                            x-ref="loginInput"
                            x-model.trim="login.pin"
                            @keydown.enter.prevent="loginWithPin()"
                            type="password"
                            inputmode="numeric"
                            placeholder="Enter admin PIN"
                            class="h-14 w-full rounded-2xl border border-white/10 bg-slate-950/50 px-4 text-lg tracking-[0.2em] text-white outline-none transition placeholder:tracking-normal placeholder:text-slate-500 focus:border-emerald-400"
                        >
                    </div>

                    <button
                        type="button"
                        @click="loginWithPin()"
                        :disabled="login.busy"
                        class="inline-flex h-14 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                    >
                        <span x-text="login.busy ? 'Unlocking…' : 'Unlock dashboard'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cfoConsole({ authenticated, user, blockedRole }) {
            return {
                authenticated,
                user,
                blockedRole,
                loading: false,
                error: '',
                kpis: {
                    month_revenue: 0,
                    month_profit: 0,
                    critical_alert_count: 0,
                },
                criticalAlerts: [],
                overview: {
                    profit_vs_revenue: {
                        labels: [],
                        revenue: [],
                        profit: [],
                    },
                    hourly_heatmap: {
                        labels: [],
                        revenue: [],
                    },
                },
                login: {
                    pin: '',
                    busy: false,
                },
                toasts: [],
                toastCounter: 0,
                charts: {
                    profit: null,
                    hourly: null,
                },

                boot() {
                    if (this.authenticated) {
                        this.fetchOverview();
                        return;
                    }

                    this.$nextTick(() => this.$refs.loginInput?.focus());

                    if (this.blockedRole) {
                        this.toast('error', 'Admin sign-in required', `${this.blockedRole} accounts cannot access the finance console.`);
                    }
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                },

                updateCsrfToken(token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');

                    if (meta) {
                        meta.setAttribute('content', token);
                    }
                },

                postHeaders() {
                    return {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    };
                },

                async parseJson(response) {
                    const text = await response.text();

                    if (!text) {
                        return {};
                    }

                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        return {};
                    }
                },

                toast(variant, title, message) {
                    const id = ++this.toastCounter;
                    this.toasts.push({ id, variant, title, message });
                    window.setTimeout(() => {
                        this.toasts = this.toasts.filter((toast) => toast.id !== id);
                    }, 4200);
                },

                async loginWithPin() {
                    if (this.login.busy || !this.login.pin.trim()) {
                        return;
                    }

                    this.login.busy = true;

                    try {
                        const response = await fetch('/api/auth/pin-login', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({ pin: this.login.pin.trim() }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to authenticate that PIN.');
                        }

                        if (payload.user?.role !== 'admin') {
                            this.blockedRole = payload.user?.role || 'non-admin';
                            this.toast('error', 'Access denied', 'Only admin accounts can open the finance console.');
                            return;
                        }

                        this.authenticated = true;
                        this.user = payload.user;
                        this.blockedRole = null;
                        this.login.pin = '';

                        if (payload.csrf_token) {
                            this.updateCsrfToken(payload.csrf_token);
                        }

                        this.toast('success', 'Dashboard unlocked', 'Finance reports are now live.');
                        await this.fetchOverview();
                    } catch (error) {
                        this.toast('error', 'Unlock failed', error.message || 'Unable to unlock the finance console.');
                    } finally {
                        this.login.busy = false;
                    }
                },

                async fetchOverview() {
                    this.loading = true;
                    this.error = '';

                    try {
                        const response = await fetch('/api/admin/reports/dashboard-overview', {
                            headers: {
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 401 || response.status === 403) {
                            this.authenticated = false;
                            this.user = null;
                            this.error = '';
                            this.$nextTick(() => this.$refs.loginInput?.focus());
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to load dashboard analytics.');
                        }

                        this.kpis = payload.kpis || this.kpis;
                        this.criticalAlerts = Array.isArray(payload.critical_alerts) ? payload.critical_alerts : [];
                        this.overview = {
                            profit_vs_revenue: payload.profit_vs_revenue || this.overview.profit_vs_revenue,
                            hourly_heatmap: payload.hourly_heatmap || this.overview.hourly_heatmap,
                        };

                        this.$nextTick(() => {
                            this.renderProfitChart();
                            this.renderHourlyChart();
                        });
                    } catch (error) {
                        this.error = error.message || 'Unable to load dashboard analytics.';
                    } finally {
                        this.loading = false;
                    }
                },

                renderProfitChart() {
                    this.charts.profit?.destroy();

                    this.charts.profit = new Chart(this.$refs.profitChart, {
                        type: 'bar',
                        data: {
                            labels: this.overview.profit_vs_revenue.labels,
                            datasets: [
                                {
                                    label: 'Revenue',
                                    data: this.overview.profit_vs_revenue.revenue,
                                    backgroundColor: 'rgba(148, 163, 184, 0.55)',
                                    borderRadius: 18,
                                },
                                {
                                    label: 'Real Profit',
                                    data: this.overview.profit_vs_revenue.profit,
                                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                    borderRadius: 18,
                                },
                            ],
                        },
                        options: this.chartOptions(),
                    });
                },

                renderHourlyChart() {
                    this.charts.hourly?.destroy();

                    this.charts.hourly = new Chart(this.$refs.hourlyChart, {
                        type: 'line',
                        data: {
                            labels: this.overview.hourly_heatmap.labels,
                            datasets: [
                                {
                                    label: 'Revenue',
                                    data: this.overview.hourly_heatmap.revenue,
                                    borderColor: '#22c55e',
                                    backgroundColor: 'rgba(34, 197, 94, 0.15)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 2,
                                    pointHoverRadius: 4,
                                },
                            ],
                        },
                        options: this.chartOptions(),
                    });
                },

                chartOptions() {
                    return {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#cbd5e1',
                                },
                            },
                        },
                        scales: {
                            x: {
                                ticks: {
                                    color: '#94a3b8',
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.08)',
                                },
                            },
                            y: {
                                ticks: {
                                    color: '#94a3b8',
                                    callback: (value) => this.formatCurrency(value),
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.08)',
                                },
                            },
                        },
                    };
                },

                profitMarginText() {
                    if (this.kpis.month_revenue <= 0) {
                        return '0.0%';
                    }

                    return `${((this.kpis.month_profit / this.kpis.month_revenue) * 100).toFixed(1)}%`;
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-KE', {
                        style: 'currency',
                        currency: 'KES',
                        maximumFractionDigits: 0,
                    }).format(Number(value || 0));
                },
            };
        }
    </script>
</body>
</html>
