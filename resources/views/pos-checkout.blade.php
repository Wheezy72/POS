<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duka-App POS</title>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script>
        (() => {
            const storedTheme = localStorage.getItem('duka-theme');

            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            }

            window.tailwind = window.tailwind || {};
            window.tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        boxShadow: {
                            panel: '0 18px 50px rgba(15, 23, 42, 0.12)',
                        },
                    },
                },
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-slate-100 text-slate-900 antialiased transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100">
    <div
        x-data="posEngine()"
        x-init="init()"
        @keydown.window="handleGlobalKeydown($event)"
        class="h-screen w-screen overflow-hidden"
    >
        <div class="grid h-full w-full grid-cols-10 gap-4 p-4 lg:gap-6 lg:p-6">
            <section class="col-span-10 flex min-h-0 flex-col rounded-3xl border border-slate-200 bg-white shadow-panel dark:border-slate-800 dark:bg-slate-900 lg:col-span-7">
                <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 lg:px-6">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                        <div class="flex-1">
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <div>
                                    <h1 class="text-xl font-black tracking-tight text-slate-900 dark:text-white lg:text-2xl">Duka-App POS</h1>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Offline-first checkout with STK Push and live till feed claiming.</p>
                                </div>

                                <button
                                    type="button"
                                    @click="toggleTheme()"
                                    class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                                    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                                    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                                >
                                    <svg x-show="!isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v2.25M12 18.75V21m9-9h-2.25M5.25 12H3m15.114 6.364-1.59-1.59M7.476 7.476l-1.59-1.59m12.228 0-1.59 1.59M7.476 16.524l-1.59 1.59M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                    </svg>
                                    <svg x-show="isDark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12.79A9 9 0 1 1 11.21 3c0 .18-.01.36-.01.54a9 9 0 0 0 9.26 9.25c.18 0 .36 0 .54-.01Z" />
                                    </svg>
                                </button>
                            </div>

                            <label for="product-search" class="mb-2 block text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                Barcode / Search
                            </label>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <input
                                    id="product-search"
                                    x-ref="searchInput"
                                    x-model.trim="searchTerm"
                                    @keydown.enter.prevent="searchProduct()"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="Scan barcode or search by SKU / name"
                                    class="h-16 flex-1 rounded-2xl border border-slate-300 bg-slate-50 px-5 text-lg font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-green-500 focus:bg-white focus:ring-4 focus:ring-green-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:focus:border-green-500 dark:focus:bg-slate-900 dark:focus:ring-green-500/10"
                                >
                                <button
                                    type="button"
                                    @click="searchProduct()"
                                    :disabled="isSearching"
                                    class="inline-flex h-16 items-center justify-center rounded-2xl bg-slate-900 px-8 text-base font-bold uppercase tracking-wide text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white dark:disabled:bg-slate-600 dark:disabled:text-slate-300"
                                >
                                    <span x-text="isSearching ? 'Searching...' : 'Add Item'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-slate-500 dark:text-slate-400">
                            Space focuses search, F2 opens checkout, F4 prompts manager void, F10 parks cart, Esc closes modals.
                        </p>
                        <p class="font-semibold" :class="errorMessage ? 'text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400'" x-text="errorMessage || statusMessage"></p>
                    </div>
                </header>

                <div class="min-h-0 flex-1 px-4 py-4 lg:px-6 lg:py-5">
                    <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="grid grid-cols-12 gap-3 border-b border-slate-200 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:border-slate-800 dark:text-slate-400 lg:px-5">
                            <div class="col-span-5">Item</div>
                            <div class="col-span-2 text-center">Qty</div>
                            <div class="col-span-2 text-right">Price</div>
                            <div class="col-span-2 text-right">Total</div>
                            <div class="col-span-1 text-right">Remove</div>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <template x-if="cart.length === 0">
                                <div class="flex h-full flex-col items-center justify-center gap-4 px-6 text-center">
                                    <div class="rounded-full bg-slate-200 p-4 dark:bg-slate-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.25 3.75h1.386a1.5 1.5 0 0 1 1.455 1.136l.383 1.532m0 0L6.75 12.75h10.939a1.5 1.5 0 0 0 1.455-1.136l1.263-5.053H5.474Zm0 0L4.5 15.75m2.25 0A1.125 1.125 0 1 0 6.75 18a1.125 1.125 0 0 0 0-2.25Zm10.5 0A1.125 1.125 0 1 0 17.25 18a1.125 1.125 0 0 0 0-2.25Z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold text-slate-900 dark:text-white">Cart is empty</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Search a product or tap a quick-add tile to start the sale.</p>
                                    </div>
                                </div>
                            </template>

                            <template x-for="item in cart" :key="item.product_id">
                                <div class="grid grid-cols-12 gap-3 border-b border-slate-200 px-4 py-4 text-sm dark:border-slate-800 lg:px-5">
                                    <div class="col-span-5 flex min-w-0 flex-col justify-center">
                                        <span class="truncate text-base font-bold text-slate-900 dark:text-white" x-text="item.name"></span>
                                        <span class="truncate text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="item.sku || 'Manual entry'"></span>
                                    </div>

                                    <div class="col-span-2 flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click="decrementQty(item.product_id)"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                        >
                                            −
                                        </button>
                                        <span class="min-w-[3rem] text-center text-base font-bold text-slate-900 dark:text-white" x-text="formatQuantity(item.quantity)"></span>
                                        <button
                                            type="button"
                                            @click="incrementQty(item.product_id)"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-lg font-black text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div class="col-span-2 flex items-center justify-end font-semibold text-slate-700 dark:text-slate-300" x-text="formatCurrency(item.unit_price)"></div>
                                    <div class="col-span-2 flex items-center justify-end text-base font-black text-slate-900 dark:text-white" x-text="formatCurrency(item.quantity * item.unit_price)"></div>
                                    <div class="col-span-1 flex items-center justify-end">
                                        <button
                                            type="button"
                                            @click="removeItem(item.product_id)"
                                            class="inline-flex h-10 items-center justify-center rounded-xl bg-red-50 px-3 text-xs font-black uppercase tracking-wide text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <footer class="border-t border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-900 lg:px-6 lg:py-5">
                    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/60">
                                <span class="text-base font-semibold text-slate-500 dark:text-slate-400">Subtotal</span>
                                <span class="text-xl font-bold text-slate-900 dark:text-white" x-text="formatCurrency(subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/60">
                                <span class="text-base font-semibold text-slate-500 dark:text-slate-400">Tax</span>
                                <span class="text-xl font-bold text-slate-900 dark:text-white" x-text="formatCurrency(tax)"></span>
                            </div>
                            <div class="flex items-center justify-between rounded-3xl bg-slate-950 px-5 py-5 text-white dark:bg-slate-100 dark:text-slate-950">
                                <span class="text-lg font-black uppercase tracking-[0.18em]">Grand Total</span>
                                <span class="text-4xl font-black tracking-tight lg:text-5xl" x-text="formatCurrency(grandTotal)"></span>
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <button
                                type="button"
                                @click="openCheckoutModal()"
                                :disabled="cart.length === 0 || isBusy"
                                class="inline-flex h-24 items-center justify-center rounded-3xl bg-green-600 px-6 text-3xl font-black uppercase tracking-wide text-white shadow-lg transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-green-300 dark:disabled:bg-green-900/40"
                            >
                                Pay (F2)
                            </button>
                            <button
                                type="button"
                                @click="promptVoidCart()"
                                :disabled="cart.length === 0 || isBusy"
                                class="inline-flex h-16 items-center justify-center rounded-2xl bg-red-600 px-6 text-lg font-black uppercase tracking-wide text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300 dark:disabled:bg-red-900/40"
                            >
                                Void (F4)
                            </button>
                            <button
                                type="button"
                                @click="parkCart()"
                                :disabled="cart.length === 0 || isBusy"
                                class="inline-flex h-16 items-center justify-center rounded-2xl bg-yellow-500 px-6 text-lg font-black uppercase tracking-wide text-slate-950 transition hover:bg-yellow-400 disabled:cursor-not-allowed disabled:bg-yellow-200"
                            >
                                Park (F10)
                            </button>
                        </div>
                    </div>
                </footer>
            </section>

            <aside class="col-span-10 flex min-h-0 flex-col rounded-3xl border border-slate-200 bg-white shadow-panel dark:border-slate-800 dark:bg-slate-900 lg:col-span-3">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Quick Add</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Common items for rapid basket building.</p>
                </div>

                <div class="min-h-0 flex-1">
                    <div class="grid h-full min-h-0 grid-rows-2">
                        <div class="min-h-0 border-b border-slate-200 p-4 dark:border-slate-800">
                            <div class="grid h-full min-h-0 grid-cols-2 gap-3 overflow-y-auto">
                                <template x-for="item in quickAddItems" :key="item">
                                    <button
                                        type="button"
                                        @click="quickAdd(item)"
                                        class="flex min-h-28 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-5 text-center text-base font-black text-slate-800 transition hover:border-green-400 hover:bg-green-50 hover:text-green-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-green-500 dark:hover:bg-green-500/10 dark:hover:text-green-300"
                                        x-text="item"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        <div class="min-h-0 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Live Till Feed</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pending C2B payments from the last 24 hours.</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="`${livePayments.length} pending`"></span>
                            </div>

                            <div class="h-full min-h-0 overflow-y-auto rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                                <template x-if="livePayments.length === 0">
                                    <div class="flex h-full min-h-[12rem] items-center justify-center text-center text-sm text-slate-500 dark:text-slate-400">
                                        Waiting for incoming M-PESA payments...
                                    </div>
                                </template>

                                <div class="space-y-3">
                                    <template x-for="payment in livePayments" :key="payment.id">
                                        <button
                                            type="button"
                                            @click="selectLivePayment(payment)"
                                            class="w-full rounded-2xl border px-4 py-3 text-left transition"
                                            :class="selectedTransactionCode === payment.transaction_code
                                                ? 'border-green-500 bg-green-50 text-green-900 dark:bg-green-500/10 dark:text-green-100'
                                                : 'border-slate-200 bg-white text-slate-900 hover:border-green-400 hover:bg-green-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-green-500 dark:hover:bg-green-500/10'"
                                        >
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="truncate text-base font-black" x-text="payment.customer_name"></p>
                                                    <p class="mt-1 truncate text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="payment.transaction_code"></p>
                                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="payment.phone_number || 'Phone unavailable'"></p>
                                                </div>
                                                <span class="text-base font-black" x-text="formatCurrency(payment.amount)"></span>
                                            </div>
                                            <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                                <span x-text="formatTimestamp(payment.created_at)"></span>
                                                <span class="font-black uppercase tracking-wide">Use Payment</span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div
            x-cloak
            x-show="showCheckoutModal"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm"
            @click="closeCheckoutModal()"
        ></div>

        <div
            x-cloak
            x-show="showCheckoutModal"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                @click.stop
            >
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800 lg:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Checkout</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose a payment path and submit the sale to the backend.</p>
                        </div>
                        <button
                            type="button"
                            @click="closeCheckoutModal()"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                            aria-label="Close checkout modal"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="grid min-h-0 flex-1 gap-0 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-b-0 lg:border-r lg:p-6">
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                @click="activePaymentTab = 'cash'"
                                class="rounded-2xl px-4 py-3 text-sm font-black uppercase tracking-wide transition"
                                :class="activePaymentTab === 'cash'
                                    ? 'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            >
                                Cash
                            </button>
                            <button
                                type="button"
                                @click="activePaymentTab = 'stk'"
                                class="rounded-2xl px-4 py-3 text-sm font-black uppercase tracking-wide transition"
                                :class="activePaymentTab === 'stk'
                                    ? 'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            >
                                STK Push
                            </button>
                            <button
                                type="button"
                                @click="activePaymentTab = 'live-feed'"
                                class="rounded-2xl px-4 py-3 text-sm font-black uppercase tracking-wide transition"
                                :class="activePaymentTab === 'live-feed'
                                    ? 'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'"
                            >
                                Live Feed Link
                            </button>
                        </div>

                        <div class="mt-6 min-h-[20rem]">
                            <div x-show="activePaymentTab === 'cash'" x-cloak class="space-y-5">
                                <div>
                                    <label for="cash-tendered" class="mb-2 block text-sm font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                        Cash Received
                                    </label>
                                    <input
                                        id="cash-tendered"
                                        x-model.number="cashTendered"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-16 w-full rounded-2xl border border-slate-300 bg-slate-50 px-5 text-2xl font-black text-slate-900 outline-none transition focus:border-green-500 focus:bg-white focus:ring-4 focus:ring-green-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-green-500 dark:focus:bg-slate-900 dark:focus:ring-green-500/10"
                                    >
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <template x-for="preset in cashPresets" :key="preset">
                                        <button
                                            type="button"
                                            @click="setCashTendered(preset)"
                                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-black uppercase tracking-wide text-slate-700 transition hover:border-green-400 hover:bg-green-50 hover:text-green-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:border-green-500 dark:hover:bg-green-500/10 dark:hover:text-green-300"
                                            x-text="formatCurrency(preset)"
                                        ></button>
                                    </template>
                                </div>

                                <div class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400">Amount Due</span>
                                        <span class="text-2xl font-black text-slate-900 dark:text-white" x-text="formatCurrency(grandTotal)"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400">Change</span>
                                        <span class="text-2xl font-black" :class="cashChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="formatCurrency(cashChange)"></span>
                                    </div>
                                </div>
                            </div>

                            <div x-show="activePaymentTab === 'stk'" x-cloak class="space-y-5">
                                <div>
                                    <label for="stk-phone" class="mb-2 block text-sm font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                        M-PESA Phone Number
                                    </label>
                                    <input
                                        id="stk-phone"
                                        x-model.trim="stk.phone"
                                        type="text"
                                        placeholder="2547XXXXXXXX"
                                        class="h-16 w-full rounded-2xl border border-slate-300 bg-slate-50 px-5 text-xl font-semibold text-slate-900 outline-none transition focus:border-green-500 focus:bg-white focus:ring-4 focus:ring-green-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-green-500 dark:focus:bg-slate-900 dark:focus:ring-green-500/10"
                                    >
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400">Amount</span>
                                        <span class="text-3xl font-black text-slate-900 dark:text-white" x-text="formatCurrency(grandTotal)"></span>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">The STK request will use the current cart total and a generated POS reference.</p>
                                </div>

                                <button
                                    type="button"
                                    @click="startStkPush()"
                                    :disabled="stk.isSubmitting || stk.isPolling || grandTotal <= 0"
                                    class="inline-flex h-16 w-full items-center justify-center rounded-2xl bg-green-600 px-6 text-lg font-black uppercase tracking-wide text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-green-300 dark:disabled:bg-green-900/40"
                                >
                                    <span x-text="stk.isSubmitting ? 'Sending STK...' : (stk.isPolling ? 'Polling Status...' : 'Send STK Push')"></span>
                                </button>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="font-semibold text-slate-500 dark:text-slate-400">Checkout Request ID</span>
                                            <span class="max-w-[18rem] truncate font-mono text-slate-900 dark:text-white" x-text="stk.checkoutRequestId || 'Pending initiation'"></span>
                                        </div>
                                        <div class="flex items-center justify-between gap-4">
                                            <span class="font-semibold text-slate-500 dark:text-slate-400">Status</span>
                                            <span class="font-black uppercase tracking-wide" :class="stkStatusClass()" x-text="stk.statusLabel"></span>
                                        </div>
                                        <p class="pt-2 text-slate-500 dark:text-slate-400" x-text="stk.statusMessage || 'No STK request has been sent yet.'"></p>
                                    </div>
                                </div>
                            </div>

                            <div x-show="activePaymentTab === 'live-feed'" x-cloak class="space-y-5">
                                <template x-if="selectedLivePayment">
                                    <div class="rounded-3xl border border-green-500 bg-green-50 p-5 dark:bg-green-500/10">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="text-lg font-black text-green-900 dark:text-green-100" x-text="selectedLivePayment.customer_name"></h3>
                                                <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-green-800 dark:text-green-200" x-text="selectedLivePayment.transaction_code"></p>
                                                <p class="mt-3 text-sm text-green-800 dark:text-green-200">
                                                    Incoming amount:
                                                    <span class="font-black" x-text="formatCurrency(selectedLivePayment.amount)"></span>
                                                </p>
                                                <p class="mt-1 text-sm text-green-800 dark:text-green-200" x-text="selectedLivePayment.phone_number || 'Phone unavailable'"></p>
                                            </div>
                                            <button
                                                type="button"
                                                @click="clearSelectedLivePayment()"
                                                class="rounded-2xl border border-green-600 px-3 py-2 text-xs font-black uppercase tracking-wide text-green-800 transition hover:bg-green-100 dark:border-green-400 dark:text-green-200 dark:hover:bg-green-500/10"
                                            >
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!selectedLivePayment">
                                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-400">
                                        Click a payer from the Live Till Feed on the right to link that incoming M-PESA payment to this sale.
                                    </div>
                                </template>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/60">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400">Sale Total</span>
                                        <span class="text-3xl font-black text-slate-900 dark:text-white" x-text="formatCurrency(grandTotal)"></span>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                        On submission, the selected transaction code will be sent as
                                        <code class="rounded bg-slate-200 px-1 py-0.5 text-xs dark:bg-slate-800">claim_transaction_code</code>
                                        and attached to the M-PESA payment line.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="min-h-0 bg-slate-50 p-5 dark:bg-slate-950/60 lg:p-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-lg font-black uppercase tracking-[0.18em] text-slate-900 dark:text-white">Order Summary</h3>
                            <div class="mt-4 max-h-64 space-y-3 overflow-y-auto">
                                <template x-for="item in cart" :key="`summary-${item.product_id}`">
                                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3 dark:border-slate-800">
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-slate-900 dark:text-white" x-text="item.name"></p>
                                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                                <span x-text="formatQuantity(item.quantity)"></span>
                                                ×
                                                <span x-text="formatCurrency(item.unit_price)"></span>
                                            </p>
                                        </div>
                                        <span class="font-black text-slate-900 dark:text-white" x-text="formatCurrency(item.quantity * item.unit_price)"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-5 space-y-3">
                                <div class="flex items-center justify-between text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    <span>Subtotal</span>
                                    <span x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex items-center justify-between text-sm font-semibold text-slate-500 dark:text-slate-400">
                                    <span>Tax</span>
                                    <span x-text="formatCurrency(tax)"></span>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-slate-950 px-4 py-4 text-white dark:bg-slate-100 dark:text-slate-950">
                                    <span class="text-sm font-black uppercase tracking-[0.18em]">Grand Total</span>
                                    <span class="text-3xl font-black" x-text="formatCurrency(grandTotal)"></span>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3">
                                <button
                                    type="button"
                                    @click="submitCheckout()"
                                    :disabled="!canSubmitCheckout() || isSubmittingCheckout"
                                    class="inline-flex h-16 items-center justify-center rounded-2xl bg-green-600 px-6 text-lg font-black uppercase tracking-wide text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-green-300 dark:disabled:bg-green-900/40"
                                >
                                    <span x-text="isSubmittingCheckout ? 'Completing Sale...' : 'Complete Sale'"></span>
                                </button>
                                <button
                                    type="button"
                                    @click="closeCheckoutModal()"
                                    :disabled="isSubmittingCheckout"
                                    class="inline-flex h-14 items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 text-sm font-black uppercase tracking-wide text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posEngine() {
            return {
                searchTerm: '',
                cart: [],
                livePayments: [],
                selectedTransactionCode: null,
                selectedLivePayment: null,
                statusMessage: 'Ready for checkout.',
                errorMessage: '',
                isBusy: false,
                isSearching: false,
                isSubmittingCheckout: false,
                showCheckoutModal: false,
                activePaymentTab: 'cash',
                liveFeedTimer: null,
                isDark: document.documentElement.classList.contains('dark'),
                quickAddItems: [
                    'Bread',
                    'Milk',
                    'Sugar',
                    'Soda',
                    'Water',
                    'Cooking Oil',
                    'Rice',
                    'Soap',
                ],
                cashTendered: 0,
                cashPresets: [],
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

                init() {
                    this.cashTendered = this.roundMoney(this.grandTotal);
                    this.refreshCashPresets();
                    this.fetchLivePayments();
                    this.liveFeedTimer = window.setInterval(() => this.fetchLivePayments(), 3000);

                    this.$watch('grandTotal', () => {
                        this.refreshCashPresets();

                        if (this.activePaymentTab === 'cash' && Number(this.cashTendered) < this.grandTotal) {
                            this.cashTendered = this.roundMoney(this.grandTotal);
                        }
                    });

                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                    });
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

                toggleTheme() {
                    this.isDark = !this.isDark;
                    document.documentElement.classList.toggle('dark', this.isDark);
                    localStorage.setItem('duka-theme', this.isDark ? 'dark' : 'light');
                },

                handleGlobalKeydown(event) {
                    if (event.key === 'F2') {
                        event.preventDefault();
                        this.openCheckoutModal();
                        return;
                    }

                    if (event.key === 'F4') {
                        event.preventDefault();
                        this.promptVoidCart();
                        return;
                    }

                    if (event.key === 'F10') {
                        event.preventDefault();
                        this.parkCart();
                        return;
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        this.closeCheckoutModal();
                        return;
                    }

                    if (event.code === 'Space' && !this.shouldIgnoreGlobalSpace(event.target)) {
                        event.preventDefault();
                        this.$refs.searchInput.focus();
                        this.$refs.searchInput.select();
                    }
                },

                shouldIgnoreGlobalSpace(target) {
                    if (!target) {
                        return false;
                    }

                    const tagName = target.tagName ? target.tagName.toLowerCase() : '';

                    return tagName === 'input' || tagName === 'textarea' || target.isContentEditable;
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                },

                postHeaders() {
                    return {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    };
                },

                async searchProduct(forcedQuery = null) {
                    const query = String(forcedQuery ?? this.searchTerm).trim();

                    if (!query || this.isSearching) {
                        return;
                    }

                    this.errorMessage = '';
                    this.statusMessage = 'Searching products...';
                    this.isSearching = true;

                    try {
                        const response = await fetch('/api/pos/search', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({ query }),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Product search failed.');
                        }

                        if (!Array.isArray(payload) || payload.length === 0) {
                            throw new Error('No matching product found.');
                        }

                        this.addProductToCart(payload[0]);
                        this.searchTerm = '';
                        this.errorMessage = '';
                        this.statusMessage = `${payload[0].name} added to cart.`;
                    } catch (error) {
                        this.errorMessage = error.message || 'Product search failed.';
                        alert(this.errorMessage);
                    } finally {
                        this.isSearching = false;
                        this.$nextTick(() => this.$refs.searchInput.focus());
                    }
                },

                quickAdd(itemName) {
                    this.searchProduct(itemName);
                },

                addProductToCart(product) {
                    const existingItem = this.cart.find((item) => item.product_id === product.id);

                    if (existingItem) {
                        this.incrementQty(product.id);
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
                },

                incrementQty(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);

                    if (!item) {
                        return;
                    }

                    item.quantity = this.roundMoney(Number(item.quantity) + 1);
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
                },

                removeItem(productId) {
                    this.cart = this.cart.filter((item) => item.product_id !== productId);
                    this.statusMessage = this.cart.length === 0 ? 'Cart cleared.' : 'Item removed from cart.';
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

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to fetch live till feed.');
                        }

                        this.livePayments = Array.isArray(payload.incoming_payments) ? payload.incoming_payments : [];

                        if (this.selectedTransactionCode) {
                            const stillPresent = this.livePayments.find((payment) => payment.transaction_code === this.selectedTransactionCode);

                            if (stillPresent) {
                                this.selectedLivePayment = stillPresent;
                            } else {
                                this.clearSelectedLivePayment();
                            }
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
                    this.errorMessage = '';
                    this.statusMessage = `Linked ${payment.customer_name}'s M-PESA payment.`;
                },

                clearSelectedLivePayment() {
                    this.selectedTransactionCode = null;
                    this.selectedLivePayment = null;
                },

                openCheckoutModal() {
                    if (this.cart.length === 0) {
                        alert('Add at least one item to the cart before checkout.');
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
                        alert('Enter the customer phone number for STK Push.');
                        return;
                    }

                    this.errorMessage = '';
                    this.stk.isSubmitting = true;
                    this.stk.statusLabel = 'Initiating';
                    this.stk.statusMessage = 'Sending STK request to Daraja...';
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

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to initiate STK push.');
                        }

                        this.stk.checkoutRequestId = payload.checkout_request_id;
                        this.stk.statusLabel = 'Pending';
                        this.stk.statusMessage = 'STK prompt sent. Waiting for customer confirmation...';
                        this.beginStkPolling();
                    } catch (error) {
                        this.stk.statusLabel = 'Failed';
                        this.stk.statusMessage = error.message || 'Unable to initiate STK push.';
                        alert(this.stk.statusMessage);
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

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to check STK status.');
                        }

                        this.stk.statusPayload = payload.status || {};
                        const normalized = this.normalizeStkStatus(this.stk.statusPayload);
                        this.stk.statusLabel = normalized.label;
                        this.stk.statusMessage = normalized.message;

                        if (normalized.state === 'completed' || normalized.state === 'failed') {
                            this.stopStkPolling();
                        }
                    } catch (error) {
                        this.stk.statusLabel = 'Failed';
                        this.stk.statusMessage = error.message || 'Unable to check STK status.';
                        this.stopStkPolling();
                        alert(this.stk.statusMessage);
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
                        return 'text-green-600 dark:text-green-400';
                    }

                    if (this.stk.statusLabel === 'Failed') {
                        return 'text-red-600 dark:text-red-400';
                    }

                    return 'text-yellow-600 dark:text-yellow-400';
                },

                canSubmitCheckout() {
                    if (this.cart.length === 0) {
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
                    this.errorMessage = '';
                    this.statusMessage = 'Submitting checkout...';

                    try {
                        const response = await fetch('/api/pos/checkout', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify(this.buildCheckoutPayload()),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Checkout failed.');
                        }

                        alert(`Payment successful. Receipt: ${payload.receipt_number}`);
                        this.clearCartStateAfterSuccess();
                        this.statusMessage = 'Checkout completed successfully.';
                    } catch (error) {
                        this.errorMessage = error.message || 'Checkout failed.';
                        alert(this.errorMessage);
                    } finally {
                        this.isSubmittingCheckout = false;
                    }
                },

                clearCartStateAfterSuccess() {
                    this.cart = [];
                    this.cashTendered = 0;
                    this.searchTerm = '';
                    this.clearSelectedLivePayment();
                    this.resetStkState();
                    this.closeCheckoutModal();
                    localStorage.removeItem('duka-parked-cart');
                    this.fetchLivePayments();
                    this.$nextTick(() => this.$refs.searchInput.focus());
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

                async promptVoidCart() {
                    if (this.cart.length === 0 || this.isBusy) {
                        return;
                    }

                    const managerPin = window.prompt('Manager PIN required to void the current cart:');

                    if (!managerPin) {
                        return;
                    }

                    this.isBusy = true;

                    try {
                        const response = await fetch('/api/auth/manager-override', {
                            method: 'POST',
                            headers: this.postHeaders(),
                            body: JSON.stringify({
                                manager_pin: managerPin,
                                action: 'void_cart',
                                reference_id: null,
                            }),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Manager approval failed.');
                        }

                        this.cart = [];
                        this.closeCheckoutModal();
                        this.errorMessage = '';
                        this.statusMessage = 'Cart voided with manager approval.';
                        alert('Cart voided.');
                    } catch (error) {
                        this.errorMessage = error.message || 'Manager approval failed.';
                        alert(this.errorMessage);
                    } finally {
                        this.isBusy = false;
                        this.$nextTick(() => this.$refs.searchInput.focus());
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
                    alert('Cart parked locally.');
                    this.$nextTick(() => this.$refs.searchInput.focus());
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
