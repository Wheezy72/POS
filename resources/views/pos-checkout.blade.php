<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
@php
    /** @var \App\Models\User|null $authUser */
    $authUser = auth()->user();
    $initialUser = $authUser !== null && $authUser->role === 'cashier' ? [
        'id' => (string) $authUser->getAuthIdentifier(),
        'name' => $authUser->name,
        'role' => $authUser->role,
    ] : null;
    $initialBlockedRole = $authUser !== null && $authUser->role !== 'cashier'
        ? $authUser->role
        : null;
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duka-App POS Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            color-scheme: light;
        }

        html.dark {
            color-scheme: dark;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .premium-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
        }

        .premium-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .premium-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }

        .premium-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>
    <script>
        (() => {
            const storedTheme = localStorage.getItem('duka-theme');

            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        console: {
                            dark: '#0f172a',
                            success: '#10b981',
                        },
                    },
                    boxShadow: {
                        soft: '0 1px 3px rgba(15, 23, 42, 0.08)',
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased transition-colors duration-300 dark:bg-[#020617] dark:text-slate-100">
    <div
        x-data="posConsole({
            authenticated: @js($initialUser !== null),
            user: @js($initialUser),
            blockedRole: @js($initialBlockedRole),
        })"
        x-init="boot()"
        @keydown.window="handleGlobalKeydown($event)"
        class="relative min-h-screen"
    >
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -left-24 top-0 h-72 w-72 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-500/10"></div>
            <div class="absolute right-0 top-24 h-96 w-96 rounded-full bg-sky-200/40 blur-3xl dark:bg-cyan-500/10"></div>
            <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-violet-200/35 blur-3xl dark:bg-violet-500/10"></div>
        </div>

        <div class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-3 sm:right-6 sm:top-6">
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-show="true"
                    x-transition:enter="transform transition duration-300 ease-out"
                    x-transition:enter-start="translate-x-6 opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transform transition duration-200 ease-in"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="translate-x-6 opacity-0"
                    class="pointer-events-auto overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#0f172a]"
                >
                    <div class="flex items-start gap-3 px-4 py-4">
                        <div
                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl"
                            :class="toast.variant === 'success'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                : toast.variant === 'error'
                                    ? 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'
                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                        >
                            <svg x-show="toast.variant === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <svg x-show="toast.variant === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v3.75m0 3.75h.007v.008H12v-.008Zm8.25-.75a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />
                            </svg>
                            <svg x-show="toast.variant === 'info'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11.25 11.25 12 11.25v5.25m0-9h.008v.008H12V7.5ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="toast.title"></p>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300" x-text="toast.message"></p>
                        </div>
                        <button
                            type="button"
                            @click="dismissToast(toast.id)"
                            class="text-slate-400 transition hover:text-slate-700 dark:hover:text-slate-100"
                            aria-label="Dismiss notification"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m6 6 12 12M6 18 18 6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mx-auto flex min-h-screen max-w-[1800px] flex-col gap-6 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <header class="overflow-hidden rounded-[28px] border border-white/70 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-[#0f172a]/95">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-950 via-slate-900 to-emerald-700 px-5 py-5 text-white dark:border-slate-800 lg:px-6 lg:py-6">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-3">
                            <div class="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-100">
                                Enterprise Retail Console
                            </div>
                            <div>
                                <h1 class="text-4xl font-semibold tracking-tight">Duka-App POS</h1>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                                    Faster cashier flow, stronger visual hierarchy, and a proper operator console instead of a generic admin screen.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                            <div class="min-w-[19rem] rounded-[24px] border border-white/10 bg-white/10 px-4 py-4 shadow-sm backdrop-blur">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-100/80">Logged in operator</p>
                                        <p class="mt-2 text-lg font-semibold" x-text="user ? user.name : 'Register locked'"></p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-300" x-text="user ? user.role : 'cashier required'"></p>
                                    </div>
                                    <div class="flex h-14 w-14 items-center justify-center rounded-[20px] bg-emerald-400/20 text-lg font-semibold text-white ring-1 ring-white/10">
                                        <span x-text="user ? user.name.charAt(0) : '—'"></span>
                                    </div>
                                </div>
                                <div class="mt-4 rounded-2xl bg-black/15 px-3 py-3 text-xs font-medium text-slate-200">
                                    Cashier-only register. Admin stays out of checkout and uses approvals/reports instead.
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="toggleTheme()"
                                class="inline-flex h-auto min-h-14 items-center justify-center gap-3 rounded-[24px] border border-white/10 bg-white/10 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-white/15"
                                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                            >
                                <svg x-show="!isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.364 6.364-1.06-1.06M6.697 6.697 5.636 5.636m12.728 0-1.06 1.06M6.697 17.303l-1.06 1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12.79A9 9 0 1 1 11.21 3c-.11.57-.17 1.15-.17 1.75a9 9 0 0 0 9.21 9.04c.58 0 1.16-.06 1.75-.17Z" />
                                </svg>
                                <span x-text="isDark ? 'Switch to daylight' : 'Switch to dark mode'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <button type="button" @click="scrollToSection('catalogSection')" class="inline-flex h-10 items-center rounded-2xl border border-white/10 bg-white/10 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/15">Catalog</button>
                        <button type="button" @click="scrollToSection('cartSection')" class="inline-flex h-10 items-center rounded-2xl border border-white/10 bg-white/10 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/15">Cart</button>
                        <button type="button" @click="openCheckoutModal()" class="inline-flex h-10 items-center rounded-2xl border border-white/10 bg-white/10 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/15">Payment</button>
                        <button type="button" @click="scrollToSection('liveFeedSection')" class="inline-flex h-10 items-center rounded-2xl border border-white/10 bg-white/10 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/15">Till feed</button>
                    </div>
                </div>

                <div class="flex flex-col gap-6 px-5 py-5 lg:px-6 lg:py-6">
                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                        <div class="space-y-4">
                            <div class="relative">
                                <label for="product-search" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
                                    Product discovery
                                </label>
                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <div class="relative flex-1">
                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                            </svg>
                                        </span>
                                        <input
                                            id="product-search"
                                            x-ref="searchInput"
                                            x-model.trim="searchTerm"
                                            @keydown.enter.prevent="searchProducts()"
                                            type="text"
                                            autocomplete="off"
                                            placeholder="Scan barcode or type Mac, AirPods, Coffee..."
                                            class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-12 pr-4 text-base font-medium text-slate-950 outline-none shadow-sm transition placeholder:text-slate-400 focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-[#0b1220] dark:focus:ring-emerald-500/10"
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        @click="searchProducts()"
                                        :disabled="isSearching"
                                        class="inline-flex h-14 items-center justify-center rounded-2xl bg-emerald-500 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300 dark:disabled:bg-emerald-800/50"
                                    >
                                        <span x-text="isSearching ? 'Searching…' : 'Search inventory'"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <template x-for="term in quickSearchTerms" :key="term">
                                    <button
                                        type="button"
                                        @click="searchProducts(term)"
                                        class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white"
                                        x-text="term"
                                    ></button>
                                </template>
                                <button
                                    x-show="hasParkedCart"
                                    x-cloak
                                    type="button"
                                    @click="restoreParkedCart()"
                                    class="inline-flex items-center rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 shadow-sm transition hover:bg-amber-100 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/15"
                                >
                                    Restore parked cart
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 xl:w-[28rem]">
                            <div class="rounded-[24px] border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white px-4 py-4 shadow-sm dark:border-emerald-500/20 dark:bg-gradient-to-br dark:from-emerald-500/10 dark:to-slate-900">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Status</p>
                                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white" x-text="statusMessage"></p>
                            </div>
                            <div class="rounded-[24px] border border-sky-200 bg-gradient-to-br from-sky-50 to-white px-4 py-4 shadow-sm dark:border-sky-500/20 dark:bg-gradient-to-br dark:from-sky-500/10 dark:to-slate-900">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">Items</p>
                                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white" x-text="totalItems"></p>
                            </div>
                            <div class="rounded-[24px] border border-violet-200 bg-gradient-to-br from-violet-50 to-white px-4 py-4 shadow-sm dark:border-violet-500/20 dark:bg-gradient-to-br dark:from-violet-500/10 dark:to-slate-900">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet-700 dark:text-violet-300">Flow</p>
                                <p class="mt-2 text-sm font-medium text-slate-900 dark:text-white">F2 Pay · F4 Void · F10 Park</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="grid flex-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
                <div class="grid min-h-0 gap-6">
                    <section x-ref="catalogSection" class="rounded-[28px] border border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-[#0f172a]/95">
                        <div class="flex flex-col gap-4 px-5 py-5 lg:px-6">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Search results</h2>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Scan once, review visually, then add exactly what the customer wants.
                                    </p>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" x-text="hasSearched ? `${searchResults.length} match${searchResults.length === 1 ? '' : 'es'}` : 'Awaiting search'"></p>
                            </div>

                            <div class="premium-scrollbar min-h-[18rem]">
                                <template x-if="isSearching">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        <template x-for="index in 6" :key="index">
                                            <div class="animate-pulse rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                                                <div class="h-3 w-20 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                                                <div class="mt-4 h-5 w-3/4 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                                                <div class="mt-3 h-4 w-1/2 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                                                <div class="mt-6 h-10 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!isSearching && searchResults.length > 0">
                                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        <template x-for="product in searchResults" :key="product.id">
                                            <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:border-slate-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-slate-700 dark:hover:bg-slate-900">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-lg font-semibold text-slate-950 dark:text-white" x-text="product.name"></p>
                                                        <p class="mt-1 truncate text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" x-text="product.sku"></p>
                                                    </div>
                                                    <span class="rounded-2xl bg-slate-900 px-3 py-1 text-xs font-semibold text-white dark:bg-emerald-500 dark:text-white" x-text="formatCurrency(product.base_price)"></span>
                                                </div>

                                                <div class="mt-6 grid grid-cols-2 gap-3 text-sm">
                                                    <div class="rounded-2xl bg-white px-3 py-3 dark:bg-[#0b1220]">
                                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Stock</p>
                                                        <p class="mt-1 font-semibold text-slate-900 dark:text-white" x-text="formatQuantity(product.stock_quantity)"></p>
                                                    </div>
                                                    <div class="rounded-2xl bg-white px-3 py-3 dark:bg-[#0b1220]">
                                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Tax</p>
                                                        <p class="mt-1 font-semibold text-slate-900 dark:text-white" x-text="`${Number(product.tax_category?.rate ?? 0)}%`"></p>
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="addProductToCart(product)"
                                                    class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600"
                                                >
                                                    Add to cart
                                                </button>
                                            </article>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="!isSearching && searchResults.length === 0">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center dark:border-slate-800 dark:bg-slate-900/40">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-[#0f172a]">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7.5h18M6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5A2.25 2.25 0 0 1 17.25 19.5H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75A2.25 2.25 0 0 1 6.75 4.5Z" />
                                            </svg>
                                        </div>
                                        <p class="mt-6 text-lg font-semibold text-slate-950 dark:text-white">Ready to scan…</p>
                                        <p class="mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                                            Start with a barcode, SKU, or product name. Try <span class="font-semibold text-slate-700 dark:text-slate-300">Mac</span>, <span class="font-semibold text-slate-700 dark:text-slate-300">AirPods</span>, or <span class="font-semibold text-slate-700 dark:text-slate-300">Coffee</span>.
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <div x-ref="cartSection" class="rounded-[28px] border border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-[#0f172a]/95">
                            <div class="flex h-full min-h-[24rem] flex-col">
                                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800 lg:px-6">
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Current cart</h2>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Every change is reflected in the payment pane instantly.</p>
                                    </div>
                                    <span class="rounded-2xl bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 dark:bg-slate-900 dark:text-slate-300" x-text="`${cart.length} line${cart.length === 1 ? '' : 's'}`"></span>
                                </div>

                                <div class="premium-scrollbar min-h-0 flex-1 overflow-y-auto px-5 py-4 lg:px-6">
                                    <template x-if="cart.length === 0">
                                        <div class="flex h-full min-h-[16rem] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center dark:border-slate-800 dark:bg-slate-900/40">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-[#0b1220]">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 3.75h1.386a1.5 1.5 0 0 1 1.455 1.136l.383 1.532m0 0L6.75 12.75h10.939a1.5 1.5 0 0 0 1.455-1.136l1.263-5.053H5.474Zm0 0L4.5 15.75m2.25 0A1.125 1.125 0 1 0 6.75 18a1.125 1.125 0 0 0 0-2.25Zm10.5 0A1.125 1.125 0 1 0 17.25 18a1.125 1.125 0 0 0 0-2.25Z" />
                                                </svg>
                                            </div>
                                            <p class="mt-5 text-base font-semibold text-slate-950 dark:text-white">No items in the cart yet</p>
                                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Search inventory above and add products one click at a time.</p>
                                        </div>
                                    </template>

                                    <template x-if="cart.length > 0">
                                        <div class="space-y-3">
                                            <template x-for="item in cart" :key="item.product_id">
                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                        <div class="min-w-0">
                                                            <p class="truncate text-base font-semibold text-slate-950 dark:text-white" x-text="item.name"></p>
                                                            <p class="mt-1 truncate text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" x-text="item.sku || 'Manual item'"></p>
                                                        </div>
                                                        <div class="text-left sm:text-right">
                                                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Line total</p>
                                                            <p class="text-lg font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(item.quantity * item.unit_price)"></p>
                                                        </div>
                                                    </div>

                                                    <div class="mt-4 flex flex-wrap items-center gap-3">
                                                        <div class="inline-flex items-center gap-2 rounded-2xl bg-white p-1 shadow-sm dark:bg-[#0b1220]">
                                                            <button
                                                                type="button"
                                                                @click="decrementQty(item.product_id)"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl text-lg font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                                            >
                                                                −
                                                            </button>
                                                            <span class="min-w-[3rem] text-center text-sm font-semibold text-slate-950 dark:text-white" x-text="formatQuantity(item.quantity)"></span>
                                                            <button
                                                                type="button"
                                                                @click="incrementQty(item.product_id)"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl text-lg font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                                                            >
                                                                +
                                                            </button>
                                                        </div>

                                                        <div class="rounded-2xl bg-white px-4 py-2 shadow-sm dark:bg-[#0b1220]">
                                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Unit price</p>
                                                            <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(item.unit_price)"></p>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            @click="removeItem(item.product_id)"
                                                            class="inline-flex h-10 items-center justify-center rounded-2xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-100 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/15"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <aside class="rounded-[28px] bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 p-5 text-white shadow-sm ring-1 ring-emerald-400/30 lg:p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Grand total</p>
                            <div class="mt-6">
                                <p class="text-5xl font-semibold tracking-tight" x-text="formatCurrency(grandTotal)"></p>
                                <p class="mt-2 text-sm text-white/80">Server-side pricing rules still apply on checkout, including expiry markdowns and margin protection.</p>
                            </div>

                            <div class="mt-8 space-y-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-white/70">Subtotal</span>
                                    <span class="font-semibold" x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-white/70">Tax</span>
                                    <span class="font-semibold" x-text="formatCurrency(tax)"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-white/70">Units</span>
                                    <span class="font-semibold" x-text="formatQuantity(totalItems)"></span>
                                </div>
                            </div>

                            <div class="mt-8 space-y-3">
                                <button
                                    type="button"
                                    @click="openCheckoutModal()"
                                    :disabled="cart.length === 0 || isBusy"
                                    class="inline-flex h-14 w-full items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                                >
                                    Proceed to payment
                                </button>
                                <button
                                    type="button"
                                    @click="openManagerApproval()"
                                    :disabled="cart.length === 0 || isBusy"
                                    class="inline-flex h-12 w-full items-center justify-center rounded-2xl bg-white/15 text-sm font-semibold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:bg-white/5"
                                >
                                    Void with manager approval
                                </button>
                                <button
                                    type="button"
                                    @click="parkCart()"
                                    :disabled="cart.length === 0 || isBusy"
                                    class="inline-flex h-12 w-full items-center justify-center rounded-2xl border border-white/20 bg-transparent text-sm font-semibold text-white transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Park cart on this device
                                </button>
                            </div>
                        </aside>
                    </section>
                </div>

                <aside class="grid min-h-0 gap-6 xl:sticky xl:top-6 xl:self-start">
                    <section x-ref="liveFeedSection" class="rounded-[28px] border border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-[#0f172a]/95">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Live till feed</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pending M-PESA deposits from the last 24 hours.</p>
                        </div>

                        <div class="premium-scrollbar max-h-[30rem] overflow-y-auto px-5 py-4">
                            <template x-if="livePayments.length === 0">
                                <div class="flex min-h-[14rem] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 text-center dark:border-slate-800 dark:bg-slate-900/40">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-[#0b1220]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6v6l4 2.25M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <p class="mt-5 text-base font-semibold text-slate-950 dark:text-white">No incoming payments yet</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Webhook-captured M-PESA transactions will surface here automatically.</p>
                                </div>
                            </template>

                            <template x-if="livePayments.length > 0">
                                <div class="space-y-3">
                                    <template x-for="payment in livePayments" :key="payment.id">
                                        <button
                                            type="button"
                                            @click="selectLivePayment(payment)"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left shadow-sm transition hover:border-emerald-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-emerald-500/40 dark:hover:bg-slate-900"
                                        >
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white" x-text="payment.customer_name"></p>
                                                    <p class="mt-1 truncate text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" x-text="payment.transaction_code"></p>
                                                </div>
                                                <span class="rounded-2xl bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-text="formatCurrency(payment.amount)"></span>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                                <span x-text="payment.phone_number || 'Phone hidden'"></span>
                                                <span x-text="formatTimestamp(payment.created_at)"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </section>

                    <section class="rounded-[28px] border border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-[#0f172a]/95">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Operator guidance</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Minimal friction for the cashier, tight controls for the business.</p>
                        </div>
                        <div class="space-y-3 px-5 py-4 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                                <p class="font-semibold text-slate-950 dark:text-white">1. Sign in with staff PIN</p>
                                <p class="mt-1 leading-6 text-slate-500 dark:text-slate-400">The overlay blocks checkout until a cashier session is established.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                                <p class="font-semibold text-slate-950 dark:text-white">2. Search or scan</p>
                                <p class="mt-1 leading-6 text-slate-500 dark:text-slate-400">Search results stay visible, so the operator can confirm the right item before adding it.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                                <p class="font-semibold text-slate-950 dark:text-white">3. Complete payment</p>
                                <p class="mt-1 leading-6 text-slate-500 dark:text-slate-400">Cash, STK push, and claimed live-feed payments all flow through the same premium checkout modal.</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </main>
        </div>

        <div
            x-cloak
            x-show="showCheckoutModal"
            x-transition.opacity
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/55 px-4 py-4 backdrop-blur-sm"
        >
            <div class="grid max-h-[92vh] w-full max-w-6xl gap-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#0f172a] lg:grid-cols-[minmax(0,1.1fr)_24rem]">
                <div class="premium-scrollbar min-h-0 overflow-y-auto">
                    <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 lg:px-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Checkout</p>
                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Choose payment method</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">The server recalculates pricing and validates payment totals before completing the sale.</p>
                            </div>
                            <button
                                type="button"
                                @click="closeCheckoutModal()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white"
                                aria-label="Close checkout modal"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m6 6 12 12M6 18 18 6" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="activePaymentTab = 'cash'"
                                class="inline-flex h-11 items-center rounded-2xl px-4 text-sm font-semibold transition"
                                :class="activePaymentTab === 'cash'
                                    ? 'bg-emerald-500 text-white shadow-sm'
                                    : 'border border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white'"
                            >
                                Cash
                            </button>
                            <button
                                type="button"
                                @click="activePaymentTab = 'stk'"
                                class="inline-flex h-11 items-center rounded-2xl px-4 text-sm font-semibold transition"
                                :class="activePaymentTab === 'stk'
                                    ? 'bg-emerald-500 text-white shadow-sm'
                                    : 'border border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white'"
                            >
                                M-PESA STK
                            </button>
                            <button
                                type="button"
                                @click="activePaymentTab = 'live-feed'"
                                class="inline-flex h-11 items-center rounded-2xl px-4 text-sm font-semibold transition"
                                :class="activePaymentTab === 'live-feed'
                                    ? 'bg-emerald-500 text-white shadow-sm'
                                    : 'border border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white'"
                            >
                                Live till feed
                            </button>
                        </div>
                    </div>

                    <div class="px-5 py-5 lg:px-6">
                        <div x-show="activePaymentTab === 'cash'" class="space-y-5">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 dark:text-white">Cash tendered</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Use presets for fast retail cash handling.</p>
                                    </div>
                                    <p class="text-2xl font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(Number(cashTendered || 0))"></p>
                                </div>

                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <input
                                        x-model.number="cashTendered"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-14 rounded-2xl border border-slate-200 bg-white px-4 text-base font-medium text-slate-950 outline-none shadow-sm transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-800 dark:bg-[#0b1220] dark:text-white dark:focus:ring-emerald-500/10"
                                        placeholder="Enter cash received"
                                    >
                                    <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-sm dark:bg-[#0b1220]">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Change</p>
                                            <p class="mt-1 text-lg font-semibold" :class="cashChange >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-red-600 dark:text-red-300'" x-text="formatCurrency(cashChange)"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <template x-for="preset in cashPresets" :key="preset">
                                        <button
                                            type="button"
                                            @click="setCashTendered(preset)"
                                            class="inline-flex h-11 items-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-800 dark:bg-[#0b1220] dark:text-slate-200 dark:hover:border-slate-700 dark:hover:text-white"
                                            x-text="formatCurrency(preset)"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div x-show="activePaymentTab === 'stk'" x-cloak class="space-y-5">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                    <div>
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Customer phone</label>
                                        <input
                                            x-model.trim="stk.phone"
                                            type="text"
                                            inputmode="tel"
                                            placeholder="2547XXXXXXXX"
                                            class="h-14 w-full rounded-2xl border border-slate-200 bg-white px-4 text-base font-medium text-slate-950 outline-none shadow-sm transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-800 dark:bg-[#0b1220] dark:text-white dark:focus:ring-emerald-500/10"
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        @click="startStkPush()"
                                        :disabled="stk.isSubmitting || stk.isPolling"
                                        class="inline-flex h-14 items-center justify-center rounded-2xl bg-emerald-500 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                                    >
                                        <span x-text="stk.isSubmitting ? 'Sending…' : 'Send STK push'"></span>
                                    </button>
                                </div>

                                <div class="mt-5 rounded-2xl bg-white p-4 shadow-sm dark:bg-[#0b1220]">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">STK status</p>
                                            <p class="mt-2 text-lg font-semibold" :class="stkStatusClass()" x-text="stk.statusLabel"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Reference</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white" x-text="stk.checkoutRequestId || 'Pending request'"></p>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="stk.statusMessage || 'Ready to initiate STK payment.'"></p>
                                </div>
                            </div>
                        </div>

                        <div x-show="activePaymentTab === 'live-feed'" x-cloak class="space-y-5">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 dark:text-white">Claim a live payment</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose one of the pending till payments from the right-hand feed.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="fetchLivePayments()"
                                        class="inline-flex h-11 items-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-800 dark:bg-[#0b1220] dark:text-slate-200 dark:hover:border-slate-700 dark:hover:text-white"
                                    >
                                        Refresh
                                    </button>
                                </div>

                                <div class="mt-5 rounded-2xl bg-white p-4 shadow-sm dark:bg-[#0b1220]">
                                    <template x-if="selectedLivePayment">
                                        <div>
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="text-lg font-semibold text-slate-950 dark:text-white" x-text="selectedLivePayment.customer_name"></p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" x-text="selectedLivePayment.transaction_code"></p>
                                                </div>
                                                <span class="rounded-2xl bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300" x-text="formatCurrency(selectedLivePayment.amount)"></span>
                                            </div>
                                            <div class="mt-4 flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                                                <span x-text="selectedLivePayment.phone_number || 'Phone hidden'"></span>
                                                <span x-text="formatTimestamp(selectedLivePayment.created_at)"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedLivePayment">
                                        <div class="text-sm text-slate-500 dark:text-slate-400">
                                            Select a payment from the live till feed to bind it to this sale.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/60 lg:border-l lg:border-t-0">
                    <div class="premium-scrollbar max-h-[92vh] overflow-y-auto px-5 py-5 lg:px-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Order summary</p>
                        <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white" x-text="formatCurrency(grandTotal)"></p>

                        <div class="mt-6 space-y-3">
                            <template x-for="item in cart" :key="`summary-${item.product_id}`">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-[#0b1220]">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-950 dark:text-white" x-text="item.name"></p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                <span x-text="formatQuantity(item.quantity)"></span>
                                                ×
                                                <span x-text="formatCurrency(item.unit_price)"></span>
                                            </p>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(item.quantity * item.unit_price)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-[#0b1220]">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Subtotal</span>
                                <span class="font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Tax</span>
                                <span class="font-semibold text-slate-950 dark:text-white" x-text="formatCurrency(tax)"></span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitCheckout()"
                            :disabled="!canSubmitCheckout() || isSubmittingCheckout"
                            class="mt-6 inline-flex h-14 w-full items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                        >
                            <span x-text="isSubmittingCheckout ? 'Processing…' : 'Complete sale'"></span>
                        </button>
                    </div>
                </aside>
            </div>
        </div>

        <div
            x-cloak
            x-show="managerApproval.show"
            x-transition.opacity
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/55 px-4 py-4 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#0f172a]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Manager approval</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Void current cart</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Enter a manager or admin PIN to authorize this action.</p>
                    </div>
                    <button
                        type="button"
                        @click="closeManagerApproval()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:text-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m6 6 12 12M6 18 18 6" />
                        </svg>
                    </button>
                </div>

                <div class="mt-6">
                    <label for="manager-pin" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Manager PIN</label>
                    <input
                        id="manager-pin"
                        x-ref="managerPinInput"
                        x-model.trim="managerApproval.pin"
                        @keydown.enter.prevent="submitManagerApproval()"
                        type="password"
                        inputmode="numeric"
                        placeholder="Enter manager PIN"
                        class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-base font-medium text-slate-950 outline-none shadow-sm transition focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-400 dark:focus:bg-[#0b1220] dark:focus:ring-emerald-500/10"
                    >
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        @click="closeManagerApproval()"
                        class="inline-flex h-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-700 dark:hover:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitManagerApproval()"
                        :disabled="managerApproval.busy"
                        class="inline-flex h-12 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                    >
                        <span x-text="managerApproval.busy ? 'Authorizing…' : 'Approve void'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="!authenticated"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 px-4 py-4 backdrop-blur-md"
        >
            <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#0f172a]">
                <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                    <div class="inline-flex items-center rounded-2xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        Secure operator login
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">Unlock the register</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        This register is cashier-only. Sign in with a cashier PIN to begin trading while admin and manager accounts remain available for approvals elsewhere.
                    </p>
                </div>

                <div class="grid gap-6 px-6 py-6">
                    <template x-if="blockedRole">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                            A <span class="font-semibold" x-text="blockedRole"></span> session is already active in the browser, but that role cannot run the register. Use a cashier PIN instead.
                        </div>
                    </template>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Cashier demo</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">Front Counter</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">PIN: 0000</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Access rule</p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">Cashier only</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Admin and manager stay on oversight flows, not checkout.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="staff-pin" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Staff PIN</label>
                        <input
                            id="staff-pin"
                            x-ref="loginPinInput"
                            x-model.trim="login.pin"
                            @keydown.enter.prevent="loginWithPin()"
                            type="password"
                            inputmode="numeric"
                            placeholder="Enter staff PIN"
                            class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-lg font-medium tracking-[0.2em] text-slate-950 outline-none shadow-sm transition placeholder:tracking-normal placeholder:text-slate-400 focus:border-emerald-400 focus:bg-white focus:ring-4 focus:ring-emerald-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-emerald-400 dark:focus:bg-[#0b1220] dark:focus:ring-emerald-500/10"
                        >
                    </div>

                    <button
                        type="button"
                        @click="loginWithPin()"
                        :disabled="login.busy"
                        class="inline-flex h-14 items-center justify-center rounded-2xl bg-emerald-500 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-emerald-300"
                    >
                        <span x-text="login.busy ? 'Signing in…' : 'Continue as cashier'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posConsole({ authenticated, user, blockedRole }) {
            return {
                authenticated,
                user,
                blockedRole,
                isDark: document.documentElement.classList.contains('dark'),
                statusMessage: authenticated
                    ? 'Cashier session active.'
                    : blockedRole
                        ? `Register locked for ${blockedRole} accounts.`
                        : 'Locked until cashier PIN login.',
                isBusy: false,
                isSearching: false,
                isSubmittingCheckout: false,
                showCheckoutModal: false,
                activePaymentTab: 'cash',
                searchTerm: '',
                searchResults: [],
                cart: [],
                livePayments: [],
                selectedTransactionCode: null,
                selectedLivePayment: null,
                hasSearched: false,
                quickSearchTerms: ['Mac', 'AirPods', 'Coffee', 'Tesla', 'Matcha', 'Lamp'],
                toasts: [],
                toastCounter: 0,
                liveFeedTimer: null,
                cashTendered: 0,
                cashPresets: [],
                login: {
                    pin: '',
                    busy: false,
                },
                managerApproval: {
                    show: false,
                    pin: '',
                    busy: false,
                },
                stk: {
                    phone: '',
                    checkoutRequestId: '',
                    statusPayload: null,
                    statusLabel: 'Idle',
                    statusMessage: '',
                    isSubmitting: false,
                    isPolling: false,
                    pollTimer: null,
                },

                boot() {
                    this.cashTendered = this.roundMoney(this.grandTotal);
                    this.refreshCashPresets();
                    if (this.authenticated) {
                        this.fetchLivePayments();
                        this.liveFeedTimer = window.setInterval(() => this.fetchLivePayments(), 5000);
                    }

                    this.$watch('grandTotal', () => {
                        this.refreshCashPresets();

                        if (this.activePaymentTab === 'cash' && Number(this.cashTendered) < this.grandTotal) {
                            this.cashTendered = this.roundMoney(this.grandTotal);
                        }
                    });

                    this.$nextTick(() => {
                        if (this.authenticated) {
                            this.focusSearchInput();
                        } else {
                            if (this.blockedRole) {
                                this.toast('info', 'Cashier sign-in required', `${this.blockedRole} accounts can review the system but cannot operate the register.`);
                            }
                            this.focusLoginInput();
                        }
                    });
                },

                get totalItems() {
                    return this.roundMoney(this.cart.reduce((sum, item) => sum + Number(item.quantity), 0));
                },

                get subtotal() {
                    return this.roundMoney(this.cart.reduce((sum, item) => sum + (Number(item.quantity) * Number(item.unit_price)), 0));
                },

                get tax() {
                    return this.roundMoney(this.cart.reduce((sum, item) => {
                        const lineSubtotal = Number(item.quantity) * Number(item.unit_price);
                        return sum + (lineSubtotal * (Number(item.tax_rate ?? 0) / 100));
                    }, 0));
                },

                get grandTotal() {
                    return this.roundMoney(this.subtotal + this.tax);
                },

                get cashChange() {
                    return this.roundMoney(Number(this.cashTendered || 0) - this.grandTotal);
                },

                get hasParkedCart() {
                    return localStorage.getItem('duka-parked-cart') !== null;
                },

                focusSearchInput() {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                },

                focusLoginInput() {
                    this.$nextTick(() => this.$refs.loginPinInput?.focus());
                },

                toggleTheme() {
                    this.isDark = !this.isDark;
                    document.documentElement.classList.toggle('dark', this.isDark);
                    localStorage.setItem('duka-theme', this.isDark ? 'dark' : 'light');
                },

                scrollToSection(sectionRef) {
                    const element = this.$refs[sectionRef];

                    if (!element) {
                        return;
                    }

                    element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start',
                    });
                },

                handleGlobalKeydown(event) {
                    if (!this.authenticated) {
                        return;
                    }

                    if (event.key === 'F2') {
                        event.preventDefault();
                        this.openCheckoutModal();
                        return;
                    }

                    if (event.key === 'F4') {
                        event.preventDefault();
                        this.openManagerApproval();
                        return;
                    }

                    if (event.key === 'F10') {
                        event.preventDefault();
                        this.parkCart();
                        return;
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();

                        if (this.managerApproval.show) {
                            this.closeManagerApproval();
                            return;
                        }

                        if (this.showCheckoutModal) {
                            this.closeCheckoutModal();
                        }

                        return;
                    }

                    if (event.code === 'Space' && !this.shouldIgnoreGlobalSpace(event.target)) {
                        event.preventDefault();
                        this.focusSearchInput();
                        this.$refs.searchInput?.select();
                    }
                },

                shouldIgnoreGlobalSpace(target) {
                    if (!target) {
                        return false;
                    }

                    const tagName = target.tagName ? target.tagName.toLowerCase() : '';

                    return ['input', 'textarea', 'button'].includes(tagName) || target.isContentEditable;
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

                handleUnauthorized(payload) {
                    this.authenticated = false;
                    this.user = null;
                    this.blockedRole = null;
                    this.showCheckoutModal = false;
                    this.managerApproval.show = false;
                    this.statusMessage = 'Session expired. Sign in again to continue.';
                    this.toast('error', 'Session required', payload.message || 'Your cashier session is no longer active.');
                    this.focusLoginInput();
                },

                toast(variant, title, message) {
                    const id = ++this.toastCounter;
                    this.toasts.push({ id, variant, title, message });

                    window.setTimeout(() => this.dismissToast(id), 4200);
                },

                dismissToast(id) {
                    this.toasts = this.toasts.filter((toast) => toast.id !== id);
                },

                async loginWithPin() {
                    if (this.login.busy) {
                        return;
                    }

                    const pin = this.login.pin.trim();

                    if (!pin) {
                        this.toast('error', 'PIN required', 'Enter a staff PIN to unlock the register.');
                        return;
                    }

                    this.login.busy = true;

                    try {
                        const response = await fetch('/api/login-pin', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({ pin }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to authenticate with that PIN.');
                        }

                        this.authenticated = true;
                        this.user = payload.user || null;
                        this.blockedRole = null;
                        this.login.pin = '';
                        this.statusMessage = `Signed in as ${this.user?.name ?? 'staff'}.`;

                        if (payload.csrf_token) {
                            this.updateCsrfToken(payload.csrf_token);
                        }

                        if (!this.liveFeedTimer) {
                            this.fetchLivePayments();
                            this.liveFeedTimer = window.setInterval(() => this.fetchLivePayments(), 5000);
                        }

                        this.toast('success', 'Welcome back', payload.message || 'Cashier session activated.');
                        this.focusSearchInput();
                    } catch (error) {
                        this.toast('error', 'Login failed', error.message || 'Unable to authenticate with that PIN.');
                    } finally {
                        this.login.busy = false;
                    }
                },

                async searchProducts(forcedQuery = null) {
                    if (!this.authenticated) {
                        this.toast('info', 'Sign in first', 'Unlock the register before searching inventory.');
                        this.focusLoginInput();
                        return;
                    }

                    const query = String(forcedQuery ?? this.searchTerm).trim();

                    if (!query || this.isSearching) {
                        if (!query) {
                            this.searchResults = [];
                            this.hasSearched = false;
                            this.statusMessage = 'Ready to scan.';
                        }

                        return;
                    }

                    this.isSearching = true;
                    this.hasSearched = true;
                    this.statusMessage = `Searching inventory for “${query}”…`;

                    try {
                        const response = await fetch('/api/pos/search', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({ query }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (!response.ok) {
                            throw new Error(payload.message || 'Product search failed.');
                        }

                        this.searchResults = Array.isArray(payload) ? payload : [];
                        this.searchTerm = query;
                        this.statusMessage = this.searchResults.length > 0
                            ? `${this.searchResults.length} matching product${this.searchResults.length === 1 ? '' : 's'} found.`
                            : 'Ready to scan.';
                    } catch (error) {
                        this.searchResults = [];
                        this.toast('error', 'Search failed', error.message || 'Product search failed.');
                    } finally {
                        this.isSearching = false;
                    }
                },

                addProductToCart(product) {
                    if (!this.authenticated) {
                        this.toast('info', 'Sign in first', 'Unlock the register before modifying the cart.');
                        return;
                    }

                    const existingItem = this.cart.find((item) => item.product_id === product.id);

                    if (existingItem) {
                        this.incrementQty(product.id);
                        this.toast('success', 'Cart updated', `${product.name} quantity increased.`);
                        return;
                    }

                    this.cart.push({
                        product_id: product.id,
                        name: product.name,
                        sku: product.sku,
                        quantity: 1,
                        unit_price: this.roundMoney(product.base_price),
                        tax_rate: Number(product.tax_category?.rate ?? 0),
                    });

                    this.statusMessage = `${product.name} added to cart.`;
                    this.toast('success', 'Added to cart', `${product.name} is ready for checkout.`);
                },

                incrementQty(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);

                    if (!item) {
                        return;
                    }

                    item.quantity = this.roundMoney(Number(item.quantity) + 1);
                    this.statusMessage = `${item.name} quantity updated.`;
                },

                decrementQty(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);

                    if (!item) {
                        return;
                    }

                    if (Number(item.quantity) <= 1) {
                        this.removeItem(productId);
                        return;
                    }

                    item.quantity = this.roundMoney(Number(item.quantity) - 1);
                    this.statusMessage = `${item.name} quantity updated.`;
                },

                removeItem(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);
                    this.cart = this.cart.filter((entry) => entry.product_id !== productId);

                    if (item) {
                        this.toast('info', 'Removed from cart', `${item.name} was removed from the sale.`);
                    }

                    this.statusMessage = this.cart.length === 0 ? 'Cart cleared.' : 'Cart updated.';
                },

                async fetchLivePayments() {
                    try {
                        const response = await fetch('/api/pos/mpesa/live-feed', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to fetch live till feed.');
                        }

                        this.livePayments = Array.isArray(payload.incoming_payments) ? payload.incoming_payments : [];

                        if (this.selectedTransactionCode) {
                            const stillPresent = this.livePayments.find((payment) => payment.transaction_code === this.selectedTransactionCode);
                            this.selectedLivePayment = stillPresent ?? null;
                            this.selectedTransactionCode = stillPresent ? this.selectedTransactionCode : null;
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },

                selectLivePayment(payment) {
                    this.selectedTransactionCode = payment.transaction_code;
                    this.selectedLivePayment = payment;
                    this.activePaymentTab = 'live-feed';
                    this.showCheckoutModal = true;
                    this.statusMessage = `Linked ${payment.customer_name}'s payment to the current sale.`;
                },

                clearSelectedLivePayment() {
                    this.selectedTransactionCode = null;
                    this.selectedLivePayment = null;
                },

                openCheckoutModal() {
                    if (!this.authenticated) {
                        this.toast('info', 'Sign in first', 'Unlock the register before taking payment.');
                        this.focusLoginInput();
                        return;
                    }

                    if (this.cart.length === 0) {
                        this.toast('info', 'Cart is empty', 'Add at least one item before opening checkout.');
                        return;
                    }

                    this.showCheckoutModal = true;

                    if (this.selectedTransactionCode) {
                        this.activePaymentTab = 'live-feed';
                    }
                },

                closeCheckoutModal() {
                    this.showCheckoutModal = false;
                    this.stopStkPolling();
                },

                refreshCashPresets() {
                    const total = this.grandTotal;

                    this.cashPresets = [
                        total,
                        this.roundMoney(Math.ceil(total / 100) * 100),
                        this.roundMoney(Math.ceil(total / 500) * 500),
                        this.roundMoney(Math.ceil(total / 1000) * 1000),
                    ].filter((value, index, values) => value > 0 && values.indexOf(value) === index);

                    if (Number(this.cashTendered) === 0) {
                        this.cashTendered = total;
                    }
                },

                setCashTendered(amount) {
                    this.cashTendered = this.roundMoney(amount);
                },

                buildStkReference() {
                    return `DUKA-${Date.now()}`;
                },

                async startStkPush() {
                    if (this.stk.isSubmitting || this.stk.isPolling) {
                        return;
                    }

                    if (!this.stk.phone.trim()) {
                        this.toast('error', 'Phone number required', 'Enter a customer phone number before initiating STK push.');
                        return;
                    }

                    this.stk.isSubmitting = true;
                    this.stk.statusLabel = 'Initiating';
                    this.stk.statusMessage = 'Sending STK request to Daraja…';
                    this.stk.checkoutRequestId = '';
                    this.stk.statusPayload = null;

                    try {
                        const response = await fetch('/api/pos/mpesa/stk-push', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({
                                phone: this.stk.phone.trim(),
                                amount: this.grandTotal,
                                reference: this.buildStkReference(),
                            }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 401) {
                            this.handleUnauthorized(payload);
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to initiate STK push.');
                        }

                        this.stk.checkoutRequestId = payload.checkout_request_id;
                        this.stk.statusLabel = 'Pending';
                        this.stk.statusMessage = 'STK prompt sent. Waiting for customer confirmation…';
                        this.beginStkPolling();
                        this.toast('success', 'STK sent', 'The customer should now see a payment prompt on their phone.');
                    } catch (error) {
                        this.stk.statusLabel = 'Failed';
                        this.stk.statusMessage = error.message || 'Unable to initiate STK push.';
                        this.toast('error', 'STK failed', this.stk.statusMessage);
                    } finally {
                        this.stk.isSubmitting = false;
                    }
                },

                beginStkPolling() {
                    if (!this.stk.checkoutRequestId) {
                        return;
                    }

                    this.stopStkPolling();
                    this.stk.isPolling = true;
                    this.pollStkStatus();
                    this.stk.pollTimer = window.setInterval(() => this.pollStkStatus(), 4000);
                },

                stopStkPolling() {
                    if (this.stk.pollTimer) {
                        window.clearInterval(this.stk.pollTimer);
                    }

                    this.stk.pollTimer = null;
                    this.stk.isPolling = false;
                },

                async pollStkStatus() {
                    if (!this.stk.checkoutRequestId) {
                        return;
                    }

                    try {
                        const response = await fetch('/api/pos/mpesa/stk-status', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({
                                checkout_request_id: this.stk.checkoutRequestId,
                            }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 401) {
                            this.handleUnauthorized(payload);
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to check STK status.');
                        }

                        this.stk.statusPayload = payload.status || {};
                        const normalized = this.normalizeStkStatus(this.stk.statusPayload);
                        this.stk.statusLabel = normalized.label;
                        this.stk.statusMessage = normalized.message;

                        if (normalized.state === 'completed') {
                            this.toast('success', 'Payment confirmed', normalized.message || 'M-PESA payment completed successfully.');
                            this.stopStkPolling();
                        } else if (normalized.state === 'failed') {
                            this.toast('error', 'Payment failed', normalized.message || 'M-PESA payment failed.');
                            this.stopStkPolling();
                        }
                    } catch (error) {
                        this.stk.statusLabel = 'Failed';
                        this.stk.statusMessage = error.message || 'Unable to check STK status.';
                        this.stopStkPolling();
                        this.toast('error', 'STK status failed', this.stk.statusMessage);
                    }
                },

                normalizeStkStatus(statusPayload) {
                    const responseCode = String(statusPayload?.ResponseCode ?? '');
                    const resultCode = String(statusPayload?.ResultCode ?? '');
                    const resultDesc = String(statusPayload?.ResultDesc ?? statusPayload?.ResponseDescription ?? 'Awaiting payment confirmation.');
                    const lowerDesc = resultDesc.toLowerCase();

                    if (resultCode === '0') {
                        return {
                            state: 'completed',
                            label: 'Completed',
                            message: resultDesc || 'M-PESA payment completed successfully.',
                        };
                    }

                    if (['1', '17', '1032', '1037', '2001'].includes(resultCode) || lowerDesc.includes('cancel') || lowerDesc.includes('declined') || lowerDesc.includes('failed')) {
                        return {
                            state: 'failed',
                            label: 'Failed',
                            message: resultDesc || 'M-PESA payment failed.',
                        };
                    }

                    if (responseCode === '0' || lowerDesc.includes('pending') || lowerDesc.includes('processing') || resultCode === '') {
                        return {
                            state: 'pending',
                            label: 'Pending',
                            message: resultDesc,
                        };
                    }

                    return {
                        state: 'pending',
                        label: 'Pending',
                        message: resultDesc,
                    };
                },

                stkStatusClass() {
                    if (this.stk.statusLabel === 'Completed') {
                        return 'text-emerald-600 dark:text-emerald-300';
                    }

                    if (this.stk.statusLabel === 'Failed') {
                        return 'text-red-600 dark:text-red-300';
                    }

                    return 'text-amber-600 dark:text-amber-300';
                },

                canSubmitCheckout() {
                    if (this.cart.length === 0 || !this.authenticated) {
                        return false;
                    }

                    if (this.activePaymentTab === 'cash') {
                        return this.cashChange >= 0;
                    }

                    if (this.activePaymentTab === 'stk') {
                        return this.stk.checkoutRequestId !== '' && this.stk.statusLabel === 'Completed';
                    }

                    if (this.activePaymentTab === 'live-feed') {
                        return this.selectedTransactionCode !== null;
                    }

                    return false;
                },

                buildCheckoutPayload() {
                    const payload = {
                        cart: this.cart.map((item) => ({
                            product_id: item.product_id,
                            quantity: this.roundMoney(item.quantity),
                        })),
                        payments: [],
                    };

                    if (this.activePaymentTab === 'cash') {
                        payload.payments.push({
                            method: 'cash',
                            amount: this.grandTotal,
                            reference_number: null,
                            status: 'completed',
                        });
                    }

                    if (this.activePaymentTab === 'stk') {
                        payload.payments.push({
                            method: 'mpesa',
                            amount: this.grandTotal,
                            reference_number: this.stk.checkoutRequestId,
                            status: 'completed',
                        });
                    }

                    if (this.activePaymentTab === 'live-feed') {
                        payload.payments.push({
                            method: 'mpesa',
                            amount: this.grandTotal,
                            reference_number: this.selectedTransactionCode,
                            status: 'completed',
                        });
                        payload.claim_transaction_code = this.selectedTransactionCode;
                    }

                    return payload;
                },

                async submitCheckout() {
                    if (!this.canSubmitCheckout() || this.isSubmittingCheckout) {
                        return;
                    }

                    this.isSubmittingCheckout = true;
                    this.statusMessage = 'Submitting sale…';

                    try {
                        const response = await fetch('/api/pos/checkout', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify(this.buildCheckoutPayload()),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 401) {
                            this.handleUnauthorized(payload);
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(payload.message || 'Checkout failed.');
                        }

                        this.toast('success', 'Sale completed', `Receipt ${payload.receipt_number} generated successfully.`);
                        this.clearCartStateAfterSuccess();
                        this.statusMessage = 'Checkout completed successfully.';
                    } catch (error) {
                        this.toast('error', 'Checkout failed', error.message || 'Checkout failed.');
                        this.statusMessage = 'Checkout failed.';
                    } finally {
                        this.isSubmittingCheckout = false;
                    }
                },

                clearCartStateAfterSuccess() {
                    this.cart = [];
                    this.cashTendered = 0;
                    this.searchTerm = '';
                    this.searchResults = [];
                    this.hasSearched = false;
                    this.clearSelectedLivePayment();
                    this.resetStkState();
                    this.closeCheckoutModal();
                    localStorage.removeItem('duka-parked-cart');
                    this.fetchLivePayments();
                    this.focusSearchInput();
                },

                resetStkState() {
                    this.stopStkPolling();
                    this.stk.phone = '';
                    this.stk.checkoutRequestId = '';
                    this.stk.statusPayload = null;
                    this.stk.statusLabel = 'Idle';
                    this.stk.statusMessage = '';
                    this.stk.isSubmitting = false;
                },

                openManagerApproval() {
                    if (!this.authenticated) {
                        this.toast('info', 'Sign in first', 'Unlock the register before requesting manager approval.');
                        this.focusLoginInput();
                        return;
                    }

                    if (this.cart.length === 0 || this.isBusy) {
                        return;
                    }

                    this.managerApproval.show = true;
                    this.managerApproval.pin = '';
                    this.$nextTick(() => this.$refs.managerPinInput?.focus());
                },

                closeManagerApproval() {
                    this.managerApproval.show = false;
                    this.managerApproval.pin = '';
                },

                async submitManagerApproval() {
                    if (!this.managerApproval.pin.trim() || this.managerApproval.busy) {
                        return;
                    }

                    this.managerApproval.busy = true;
                    this.isBusy = true;

                    try {
                        const response = await fetch('/api/auth/manager-override', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({
                                manager_pin: this.managerApproval.pin.trim(),
                                action: 'void_cart',
                                reference_id: null,
                            }),
                            credentials: 'same-origin',
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 401) {
                            this.handleUnauthorized(payload);
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(payload.message || 'Manager approval failed.');
                        }

                        this.cart = [];
                        this.closeCheckoutModal();
                        this.closeManagerApproval();
                        this.statusMessage = 'Cart voided with manager approval.';
                        this.toast('success', 'Cart voided', `${payload.approved_by?.name ?? 'Manager'} approved the void.`);
                        this.focusSearchInput();
                    } catch (error) {
                        this.toast('error', 'Approval failed', error.message || 'Manager approval failed.');
                    } finally {
                        this.managerApproval.busy = false;
                        this.isBusy = false;
                    }
                },

                parkCart() {
                    if (this.cart.length === 0 || this.isBusy) {
                        return;
                    }

                    const snapshot = {
                        cart: this.cart,
                        parked_at: new Date().toISOString(),
                    };

                    localStorage.setItem('duka-parked-cart', JSON.stringify(snapshot));
                    this.cart = [];
                    this.closeCheckoutModal();
                    this.statusMessage = 'Cart parked locally on this device.';
                    this.toast('success', 'Cart parked', 'The current sale was stored locally for later recovery.');
                    this.focusSearchInput();
                },

                restoreParkedCart() {
                    const snapshot = localStorage.getItem('duka-parked-cart');

                    if (!snapshot) {
                        return;
                    }

                    try {
                        const parsed = JSON.parse(snapshot);
                        this.cart = Array.isArray(parsed.cart) ? parsed.cart : [];
                        this.statusMessage = 'Parked cart restored.';
                        this.toast('success', 'Cart restored', 'The parked sale is back on screen.');
                    } catch (error) {
                        this.toast('error', 'Restore failed', 'The parked cart could not be restored.');
                    }
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-KE', {
                        style: 'currency',
                        currency: 'KES',
                        minimumFractionDigits: 2,
                    }).format(this.roundMoney(value));
                },

                formatQuantity(value) {
                    const numericValue = Number(value);

                    return Number.isInteger(numericValue) ? String(numericValue) : numericValue.toFixed(2);
                },

                formatTimestamp(value) {
                    if (!value) {
                        return 'Unknown time';
                    }

                    return new Intl.DateTimeFormat('en-KE', {
                        day: '2-digit',
                        month: 'short',
                        hour: '2-digit',
                        minute: '2-digit',
                    }).format(new Date(value));
                },

                roundMoney(value) {
                    return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
                },
            };
        }
    </script>
</body>
</html>
