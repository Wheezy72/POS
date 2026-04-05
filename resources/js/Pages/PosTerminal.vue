<template>
    <Head title="Duka POS Terminal" />

    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto flex min-h-screen max-w-[1800px] flex-col gap-3 px-3 py-3">
            <header class="grid gap-3 lg:grid-cols-[16rem_minmax(0,1fr)_14rem]">
                <section class="border border-slate-700 bg-slate-900 px-4 py-3">
                    <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Clock</p>
                    <p class="mt-2 text-3xl font-black tracking-[0.12em] text-emerald-300">{{ clock }}</p>
                    <p class="mt-2 text-[11px] uppercase tracking-[0.18em] text-slate-500">Scanner-first retail terminal</p>
                </section>

                <section class="border border-yellow-500/40 bg-slate-900 px-4 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Transaction ID</p>
                            <p class="mt-1 font-mono text-lg font-bold text-yellow-300">{{ transactionId }}</p>
                        </div>
                        <div class="text-right text-[11px] uppercase tracking-[0.18em] text-slate-500">
                            <p>Operator</p>
                            <p class="mt-1 font-semibold text-slate-300">{{ currentUser ? currentUser.name : 'Register locked' }}</p>
                        </div>
                    </div>

                    <label class="mt-3 block text-[11px] font-bold uppercase tracking-[0.25em] text-yellow-300">
                        [F8] Barcode input
                    </label>
                    <input
                        ref="scannerInput"
                        v-model.trim="barcode"
                        type="text"
                        autocomplete="off"
                        class="mt-1 h-16 w-full border border-yellow-400 bg-yellow-100 px-4 text-2xl font-black tracking-wide text-slate-950 outline-none ring-0 placeholder:font-semibold placeholder:text-slate-500 focus:border-yellow-300"
                        placeholder="Scan barcode or type SKU then press Enter"
                        @keydown.enter.prevent="searchProducts()"
                    >
                </section>

                <section class="border border-slate-700 bg-slate-900 px-4 py-3 text-right">
                    <p class="text-[11px] uppercase tracking-[0.25em] text-slate-400">Session</p>
                    <p class="mt-2 text-sm font-bold uppercase tracking-[0.18em]" :class="currentUser ? 'text-emerald-300' : 'text-red-300'">
                        {{ currentUser ? currentUser.role : 'Locked' }}
                    </p>
                    <p class="mt-3 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                        Esc closes modals instantly
                    </p>
                    <p class="mt-2 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                        Search {{ searchResults.length }} results
                    </p>
                </section>
            </header>

            <main class="grid min-h-0 flex-1 gap-3 lg:grid-cols-[minmax(0,1fr)_13rem]">
                <section class="grid min-h-0 gap-3">
                    <section class="min-h-0 border border-slate-700 bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-700 px-3 py-2">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">The Cart</p>
                                <p class="text-xs text-slate-500">High-density cashier grid</p>
                            </div>
                            <div class="text-right text-xs uppercase tracking-[0.18em] text-slate-500">
                                <p>{{ totalUnits.toFixed(2) }} units</p>
                                <p>{{ cart.length }} lines</p>
                            </div>
                        </div>

                        <div class="overflow-auto">
                            <table class="min-w-full border-collapse text-sm">
                                <thead class="bg-slate-950 text-[11px] uppercase tracking-[0.18em] text-slate-400">
                                    <tr>
                                        <th class="border-b border-r border-slate-700 px-2 py-2 text-left">#</th>
                                        <th class="border-b border-r border-slate-700 px-2 py-2 text-left">Item</th>
                                        <th class="border-b border-r border-slate-700 px-2 py-2 text-right">Price</th>
                                        <th class="border-b border-r border-slate-700 px-2 py-2 text-center">Qty</th>
                                        <th class="border-b border-r border-slate-700 px-2 py-2 text-right">Disc</th>
                                        <th class="border-b border-slate-700 px-2 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="cart.length === 0">
                                        <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-500">
                                            Cart is empty. Scan from the barcode lane or press F2 to search.
                                        </td>
                                    </tr>
                                    <tr v-for="(item, index) in cart" :key="item.product_id" class="odd:bg-slate-900 even:bg-slate-950/70">
                                        <td class="border-b border-r border-slate-800 px-2 py-2 font-mono text-slate-400">{{ index + 1 }}</td>
                                        <td class="border-b border-r border-slate-800 px-2 py-2">
                                            <p class="font-semibold text-slate-100">{{ item.name }}</p>
                                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ item.sku || 'Manual item' }}</p>
                                        </td>
                                        <td class="border-b border-r border-slate-800 px-2 py-2 text-right font-semibold">
                                            {{ formatCurrency(effectiveUnitPrice(item)) }}
                                        </td>
                                        <td class="border-b border-r border-slate-800 px-2 py-2">
                                            <div class="flex items-center justify-center gap-1">
                                                <button class="h-8 w-8 border border-slate-600 bg-slate-800 font-bold hover:bg-slate-700" @click="changeQty(item, -1)">-</button>
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="0.25"
                                                    step="0.25"
                                                    class="h-8 w-16 border border-slate-600 bg-slate-950 px-1 text-center font-semibold outline-none"
                                                    @change="normalizeQuantity(item)"
                                                >
                                                <button class="h-8 w-8 border border-slate-600 bg-slate-800 font-bold hover:bg-slate-700" @click="changeQty(item, 1)">+</button>
                                            </div>
                                        </td>
                                        <td class="border-b border-r border-slate-800 px-2 py-2">
                                            <input
                                                v-model.number="item.discount"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="h-8 w-full border border-slate-600 bg-slate-950 px-2 text-right outline-none"
                                                @change="normalizeDiscount(item)"
                                            >
                                        </td>
                                        <td class="border-b border-slate-800 px-2 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <span class="font-bold text-yellow-300">{{ formatCurrency(lineTotal(item)) }}</span>
                                                <button class="border border-red-500/40 bg-red-500/10 px-2 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-red-300 hover:bg-red-500/20" @click="removeItem(item.product_id)">
                                                    Del
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_22rem]">
                        <section class="border border-slate-700 bg-slate-900">
                            <div class="flex items-center justify-between border-b border-slate-700 px-3 py-2">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Search results</p>
                                    <p class="text-xs text-slate-500">Scanner misses and product lookups</p>
                                </div>
                                <button class="border border-sky-500/40 bg-sky-500/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-sky-300 hover:bg-sky-500/20" @click="showSearchModal = true">
                                    [F2] Search
                                </button>
                            </div>

                            <div class="max-h-56 overflow-auto">
                                <div v-if="searchBusy" class="px-3 py-6 text-sm text-slate-500">Searching…</div>
                                <div v-else-if="searchResults.length === 0" class="px-3 py-6 text-sm text-slate-500">
                                    No staged results. Scan a barcode or open the search panel.
                                </div>
                                <button
                                    v-for="product in searchResults"
                                    :key="product.id"
                                    class="grid w-full grid-cols-[1fr_auto_auto] gap-3 border-b border-slate-800 px-3 py-2 text-left hover:bg-slate-800"
                                    @click="addProductToCart(product)"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-100">{{ product.name }}</p>
                                        <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ product.sku }}</p>
                                    </div>
                                    <p class="text-right text-sm font-bold text-slate-200">{{ formatCurrency(Number(product.base_price)) }}</p>
                                    <p class="text-right text-[11px] uppercase tracking-[0.16em]" :class="Number(product.stock_quantity) < 10 ? 'text-red-300' : 'text-slate-500'">
                                        {{ Number(product.stock_quantity).toFixed(2) }}
                                    </p>
                                </button>
                            </div>
                        </section>

                        <section class="border border-slate-700 bg-slate-900">
                            <div class="flex items-center justify-between border-b border-slate-700 px-3 py-2">
                                <div>
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">M-PESA live feed</p>
                                    <p class="text-xs text-slate-500">Inbound C2B webhook lane</p>
                                </div>
                                <button class="border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-300 hover:bg-emerald-500/20" @click="fetchLivePayments()">
                                    Refresh
                                </button>
                            </div>
                            <div class="max-h-56 overflow-auto">
                                <div v-if="liveFeedBusy" class="px-3 py-6 text-sm text-slate-500">Loading live feed…</div>
                                <div v-else-if="livePayments.length === 0" class="px-3 py-6 text-sm text-slate-500">No pending M-PESA deposits.</div>
                                <button
                                    v-for="payment in livePayments"
                                    :key="payment.id"
                                    class="w-full border-b border-slate-800 px-3 py-2 text-left hover:bg-slate-800"
                                    :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-500/10' : ''"
                                    @click="selectLivePayment(payment)"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-100">{{ payment.customer_name }}</p>
                                            <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ payment.transaction_code }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-emerald-300">{{ formatCurrency(Number(payment.amount)) }}</p>
                                            <p class="text-[11px] text-slate-500">{{ shortTimestamp(payment.created_at) }}</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </section>
                    </section>
                </section>

                <aside class="grid gap-3">
                    <button class="border border-yellow-500 bg-yellow-300 px-3 py-4 text-left text-slate-950 hover:bg-yellow-200" @click="newSale()">
                        <p class="text-lg font-black">[F1] New</p>
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.18em]">Clear cart and reset sale</p>
                    </button>
                    <button class="border border-sky-500 bg-sky-600 px-3 py-4 text-left text-white hover:bg-sky-500" @click="openSearchModal()">
                        <p class="text-lg font-black">[F2] Search</p>
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.18em]">Lookup products</p>
                    </button>
                    <button class="border border-emerald-500 bg-emerald-600 px-3 py-4 text-left text-white hover:bg-emerald-500" @click="openPayModal()">
                        <p class="text-lg font-black">[F4] Pay</p>
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.18em]">Cash, STK, or C2B claim</p>
                    </button>
                    <button class="border border-red-500 bg-red-600 px-3 py-4 text-left text-white hover:bg-red-500" @click="logout()">
                        <p class="text-lg font-black">[F10] Logout</p>
                        <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.18em]">Lock the register</p>
                    </button>

                    <section class="border border-slate-700 bg-slate-900 px-3 py-3">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Selected inbound payment</p>
                        <div v-if="selectedLivePayment" class="mt-2">
                            <p class="font-semibold text-slate-100">{{ selectedLivePayment.customer_name }}</p>
                            <p class="text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ selectedLivePayment.transaction_code }}</p>
                            <p class="mt-1 font-bold text-emerald-300">{{ formatCurrency(Number(selectedLivePayment.amount)) }}</p>
                        </div>
                        <p v-else class="mt-2 text-sm text-slate-500">Nothing selected.</p>
                    </section>

                    <section class="border border-slate-700 bg-slate-900 px-3 py-3">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Pending STK</p>
                        <p class="mt-2 font-mono text-sm text-yellow-300">{{ stkCheckoutRequestId || 'None' }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ stkStatusMessage }}</p>
                    </section>
                </aside>
            </main>

            <footer class="ml-auto w-full max-w-[28rem] border border-yellow-500 bg-yellow-300 text-slate-950">
                <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-2 px-4 py-4">
                    <p class="text-sm font-bold uppercase tracking-[0.18em]">Subtotal</p>
                    <p class="text-right text-lg font-black">{{ formatCurrency(subtotal) }}</p>
                    <p class="text-sm font-bold uppercase tracking-[0.18em]">Tax</p>
                    <p class="text-right text-lg font-black">{{ formatCurrency(tax) }}</p>
                    <p class="border-t border-slate-950 pt-2 text-lg font-black uppercase tracking-[0.18em]">Grand Total</p>
                    <p class="border-t border-slate-950 pt-2 text-right text-4xl font-black tracking-tight">{{ formatCurrency(grandTotal) }}</p>
                </div>
            </footer>
        </div>

        <div class="fixed right-3 top-3 z-50 flex w-full max-w-sm flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="border px-3 py-2 shadow-lg"
                :class="toast.variant === 'error' ? 'border-red-500 bg-red-950 text-red-100' : toast.variant === 'success' ? 'border-emerald-500 bg-emerald-950 text-emerald-100' : 'border-slate-500 bg-slate-900 text-slate-100'"
            >
                <p class="font-bold uppercase tracking-[0.18em]">{{ toast.title }}</p>
                <p class="mt-1 text-sm">{{ toast.message }}</p>
            </div>
        </div>

        <div v-if="showSearchModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/80 p-4">
            <div class="w-full max-w-4xl border border-slate-600 bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-700 px-4 py-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Product search</p>
                        <p class="text-sm text-slate-500">Press Esc to close</p>
                    </div>
                    <button class="border border-slate-600 px-3 py-2 text-sm font-bold hover:bg-slate-800" @click="closeModals()">Esc</button>
                </div>
                <div class="p-4">
                    <input
                        ref="searchInput"
                        v-model.trim="searchQuery"
                        type="text"
                        autocomplete="off"
                        class="h-14 w-full border border-sky-500 bg-slate-950 px-4 text-lg font-bold outline-none"
                        placeholder="Search by barcode, SKU, or product name"
                        @keydown.enter.prevent="searchProducts(searchQuery)"
                    >
                    <div class="mt-4 max-h-[24rem] overflow-auto border border-slate-700">
                        <button
                            v-for="product in searchResults"
                            :key="product.id"
                            class="grid w-full grid-cols-[1fr_auto_auto] gap-3 border-b border-slate-800 px-3 py-2 text-left hover:bg-slate-800"
                            @click="addProductToCart(product); showSearchModal = false"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-100">{{ product.name }}</p>
                                <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ product.sku }}</p>
                            </div>
                            <p class="font-bold text-yellow-300">{{ formatCurrency(Number(product.base_price)) }}</p>
                            <p class="text-[11px] uppercase tracking-[0.16em]" :class="Number(product.stock_quantity) < 10 ? 'text-red-300' : 'text-slate-500'">
                                {{ Number(product.stock_quantity).toFixed(2) }}
                            </p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPayModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/80 p-4">
            <div class="w-full max-w-5xl border border-slate-600 bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-700 px-4 py-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Tender sale</p>
                        <p class="text-sm text-slate-500">Cash, outbound STK, or inbound C2B claim</p>
                    </div>
                    <button class="border border-slate-600 px-3 py-2 text-sm font-bold hover:bg-slate-800" @click="closeModals()">Esc</button>
                </div>

                <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <section>
                        <div class="flex gap-2">
                            <button class="border px-3 py-2 text-sm font-bold" :class="paymentTab === 'cash' ? 'border-yellow-400 bg-yellow-300 text-slate-950' : 'border-slate-600 bg-slate-800'" @click="paymentTab = 'cash'">Cash</button>
                            <button class="border px-3 py-2 text-sm font-bold" :class="paymentTab === 'stk' ? 'border-yellow-400 bg-yellow-300 text-slate-950' : 'border-slate-600 bg-slate-800'" @click="paymentTab = 'stk'">M-PESA STK</button>
                            <button class="border px-3 py-2 text-sm font-bold" :class="paymentTab === 'live' ? 'border-yellow-400 bg-yellow-300 text-slate-950' : 'border-slate-600 bg-slate-800'" @click="paymentTab = 'live'">C2B Live Feed</button>
                        </div>

                        <div v-if="paymentTab === 'cash'" class="mt-4 grid gap-3 border border-slate-700 p-4">
                            <label class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Cash received</label>
                            <input v-model.number="cashReceived" type="number" min="0" step="0.01" class="h-12 border border-slate-600 bg-slate-950 px-3 text-xl font-black outline-none">
                            <div class="grid gap-2 sm:grid-cols-4">
                                <button v-for="preset in cashPresets" :key="preset" class="border border-slate-600 bg-slate-800 px-3 py-2 text-sm font-bold hover:bg-slate-700" @click="cashReceived = preset">
                                    {{ formatCurrency(preset) }}
                                </button>
                            </div>
                            <p class="text-sm" :class="cashChange >= 0 ? 'text-emerald-300' : 'text-red-300'">
                                Change: {{ formatCurrency(cashChange) }}
                            </p>
                            <button class="border border-emerald-500 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="submitCashCheckout()">
                                Complete cash sale
                            </button>
                        </div>

                        <div v-else-if="paymentTab === 'stk'" class="mt-4 grid gap-3 border border-slate-700 p-4">
                            <label class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Customer phone</label>
                            <input v-model.trim="stkPhone" type="text" class="h-12 border border-slate-600 bg-slate-950 px-3 text-lg font-bold outline-none" placeholder="2547XXXXXXXX">
                            <p class="text-sm text-slate-500">This triggers outbound STK push, then polls status until verified.</p>
                            <button class="border border-emerald-500 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="submitStkCheckout()">
                                Start STK and checkout
                            </button>
                        </div>

                        <div v-else class="mt-4 grid gap-3 border border-slate-700 p-4">
                            <p class="text-sm text-slate-500">Select an inbound payment from the live feed. The backend claims it against this sale.</p>
                            <div class="max-h-64 overflow-auto border border-slate-700">
                                <button
                                    v-for="payment in livePayments"
                                    :key="payment.id"
                                    class="w-full border-b border-slate-800 px-3 py-2 text-left hover:bg-slate-800"
                                    :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-500/10' : ''"
                                    @click="selectLivePayment(payment)"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-100">{{ payment.customer_name }}</p>
                                            <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ payment.transaction_code }}</p>
                                        </div>
                                        <p class="font-bold text-emerald-300">{{ formatCurrency(Number(payment.amount)) }}</p>
                                    </div>
                                </button>
                            </div>
                            <button class="border border-emerald-500 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || !selectedLivePayment || cart.length === 0" @click="submitLiveFeedCheckout()">
                                Claim inbound payment and checkout
                            </button>
                        </div>
                    </section>

                    <aside class="grid gap-3">
                        <section class="border border-slate-700 bg-slate-950 p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Manual override guard</p>
                            <label class="mt-3 block text-[11px] uppercase tracking-[0.22em] text-slate-500">Manager PIN</label>
                            <input v-model.trim="managerPin" type="password" maxlength="6" class="mt-1 h-12 w-full border border-slate-600 bg-slate-900 px-3 text-lg font-bold outline-none" placeholder="Required only below floor">
                            <p class="mt-2 text-xs text-slate-500">If any line discount drops below the margin floor, checkout returns 422 unless this PIN is valid.</p>
                        </section>

                        <section class="border border-slate-700 bg-yellow-300 p-4 text-slate-950">
                            <p class="text-sm font-black uppercase tracking-[0.18em]">Hero Summary</p>
                            <div class="mt-4 grid grid-cols-[1fr_auto] gap-y-2">
                                <span class="font-bold uppercase tracking-[0.16em]">Subtotal</span>
                                <span class="text-right font-black">{{ formatCurrency(subtotal) }}</span>
                                <span class="font-bold uppercase tracking-[0.16em]">Tax</span>
                                <span class="text-right font-black">{{ formatCurrency(tax) }}</span>
                                <span class="border-t border-slate-950 pt-2 text-lg font-black uppercase tracking-[0.18em]">Grand Total</span>
                                <span class="border-t border-slate-950 pt-2 text-right text-4xl font-black">{{ formatCurrency(grandTotal) }}</span>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </div>

        <div v-if="showPinOverlay" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-md">
            <div class="w-full max-w-md rounded-3xl border border-white/20 bg-white/10 p-6 text-white shadow-2xl backdrop-blur-2xl">
                <p class="text-[11px] uppercase tracking-[0.25em] text-slate-300">PIN Lock</p>
                <h1 class="mt-2 text-3xl font-black">{{ overlayHeading }}</h1>
                <p class="mt-2 text-sm text-slate-200">The terminal stays blocked until a valid staff PIN is entered.</p>
                <p v-if="blockedRole" class="mt-2 text-sm text-red-200">
                    Logged-in role "{{ blockedRole }}" cannot operate the cashier terminal.
                </p>

                <label class="mt-5 block text-[11px] font-bold uppercase tracking-[0.25em] text-slate-300">{{ overlayLabel }}</label>
                <input
                    ref="pinInput"
                    v-model.trim="pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    class="mt-2 h-14 w-full rounded-2xl border border-white/20 bg-black/20 px-4 text-2xl font-black tracking-[0.4em] outline-none placeholder:tracking-normal placeholder:text-slate-400"
                    placeholder="0000"
                    @keydown.enter.prevent="loginWithPin()"
                >

                <button class="mt-5 w-full rounded-2xl border border-emerald-400 bg-emerald-500 px-4 py-4 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-400 disabled:opacity-50" :disabled="pinBusy" @click="loginWithPin()">
                    {{ pinBusy ? 'Unlocking…' : 'Unlock register' }}
                </button>

                <div class="mt-4 grid gap-2 text-xs text-slate-300">
                    <p>Demo PINs: Cashier 0000 · Admin/Manager 1234</p>
                    <p>Esc closes dialogs; F8 always restores barcode focus after unlock.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    overlayHeading: {
        type: String,
        default: 'Unlock the register',
    },
    overlayLabel: {
        type: String,
        default: 'Staff PIN',
    },
});

const page = usePage();

const scannerInput = ref(null);
const pinInput = ref(null);
const searchInput = ref(null);

const currentUser = ref(page.props.auth?.user ?? null);
const blockedRole = computed(() => page.props.auth?.blockedRole ?? null);

const clock = ref('');
const barcode = ref('');
const searchQuery = ref('');
const cart = ref([]);
const searchResults = ref([]);
const livePayments = ref([]);
const selectedLivePayment = ref(null);
const showSearchModal = ref(false);
const showPayModal = ref(false);
const showPinOverlay = ref(!currentUser.value);
const paymentTab = ref('cash');
const pin = ref('');
const managerPin = ref('');
const cashReceived = ref(0);
const stkPhone = ref('2547');
const stkCheckoutRequestId = ref('');
const stkStatusMessage = ref('Idle');
const searchBusy = ref(false);
const checkoutBusy = ref(false);
const pinBusy = ref(false);
const liveFeedBusy = ref(false);
const toasts = ref([]);
const transactionId = ref(generateTransactionId());

let clockTimer = null;
let focusTimer = null;
let liveFeedTimer = null;
let stkPollTimer = null;

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + lineSubtotal(item), 0));
const tax = computed(() => cart.value.reduce((sum, item) => sum + lineTax(item), 0));
const grandTotal = computed(() => roundCurrency(subtotal.value + tax.value));
const totalUnits = computed(() => cart.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0));
const cashChange = computed(() => roundCurrency(Number(cashReceived.value || 0) - grandTotal.value));
const cashPresets = computed(() => {
    const total = grandTotal.value;

    return Array.from(new Set([
        total,
        Math.ceil(total / 50) * 50,
        Math.ceil(total / 100) * 100,
        Math.ceil(total / 200) * 200,
    ])).filter((value) => value > 0);
});

watch([showSearchModal, showPayModal, showPinOverlay], async () => {
    await nextTick();
    focusPriorityInput();
});

onMounted(() => {
    updateClock();
    clockTimer = window.setInterval(updateClock, 1000);
    focusTimer = window.setInterval(() => focusScannerInput(false), 800);
    liveFeedTimer = window.setInterval(() => {
        if (currentUser.value && !showPinOverlay.value) {
            fetchLivePayments();
        }
    }, 15000);

    window.addEventListener('keydown', handleGlobalKeydown);
    window.addEventListener('focus', focusPriorityInput);
    window.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('pos:unauthenticated', handleUnauthenticated);

    if (currentUser.value) {
        fetchLivePayments();
    } else {
        focusPinInput();
    }
});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }

    if (focusTimer) {
        window.clearInterval(focusTimer);
    }

    if (liveFeedTimer) {
        window.clearInterval(liveFeedTimer);
    }

    stopStkPolling();

    window.removeEventListener('keydown', handleGlobalKeydown);
    window.removeEventListener('focus', focusPriorityInput);
    window.removeEventListener('visibilitychange', handleVisibilityChange);
    window.removeEventListener('pos:unauthenticated', handleUnauthenticated);
});

function handleVisibilityChange() {
    if (!document.hidden) {
        focusPriorityInput();
    }
}

function handleGlobalKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeModals();
        return;
    }

    switch (event.key) {
        case 'F1':
            event.preventDefault();
            newSale();
            break;
        case 'F2':
            event.preventDefault();
            openSearchModal();
            break;
        case 'F4':
            event.preventDefault();
            openPayModal();
            break;
        case 'F8':
            event.preventDefault();
            focusScannerInput(true);
            break;
        case 'F10':
            event.preventDefault();
            logout();
            break;
        default:
            break;
    }
}

function updateClock() {
    clock.value = new Intl.DateTimeFormat('en-KE', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(new Date());
}

function openSearchModal() {
    showSearchModal.value = true;
    searchQuery.value = barcode.value;
}

function openPayModal() {
    if (cart.value.length === 0) {
        toast('Cart empty', 'Add at least one item before taking payment.', 'error');
        return;
    }

    showPayModal.value = true;
    paymentTab.value = selectedLivePayment.value ? 'live' : 'cash';
    fetchLivePayments();
}

function closeModals() {
    showSearchModal.value = false;
    showPayModal.value = false;
    focusPriorityInput();
}

function focusPriorityInput() {
    if (showPinOverlay.value) {
        focusPinInput();
        return;
    }

    if (showSearchModal.value) {
        nextTick(() => searchInput.value?.focus());
        return;
    }

    focusScannerInput(true);
}

function focusPinInput() {
    nextTick(() => pinInput.value?.focus());
}

function focusScannerInput(force = true) {
    if (showPinOverlay.value || showSearchModal.value || showPayModal.value) {
        return;
    }

    const element = scannerInput.value;

    if (!element) {
        return;
    }

    if (!force && document.activeElement === element) {
        return;
    }

    element.focus();
    element.select();
}

async function loginWithPin() {
    if (!pin.value) {
        toast('PIN required', 'Enter a staff PIN to unlock the register.', 'error');
        return;
    }

    pinBusy.value = true;

    try {
        const response = await window.axios.post('/api/login-pin', {
            pin: pin.value,
        });

        currentUser.value = response.data.user;
        updateCsrfToken(response.data.csrf_token);
        pin.value = '';
        showPinOverlay.value = false;
        toast('Register unlocked', `${response.data.user.name} is now active on the terminal.`, 'success');
        fetchLivePayments();
        focusPriorityInput();
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Unable to unlock the register.';
        toast('Access denied', message, 'error');
        focusPinInput();
    } finally {
        pinBusy.value = false;
    }
}

async function logout() {
    stopStkPolling();

    try {
        if (currentUser.value) {
            await window.axios.post('/api/logout');
        }
    } catch (error) {
        // Keep the terminal lock-first even if logout request fails.
    }

    currentUser.value = null;
    showPinOverlay.value = true;
    showSearchModal.value = false;
    showPayModal.value = false;
    newSale(false);
    toast('Register locked', 'The cashier session has been closed.', 'info');
    focusPinInput();
}

function handleUnauthenticated() {
    currentUser.value = null;
    showPinOverlay.value = true;
    showSearchModal.value = false;
    showPayModal.value = false;
    newSale(false);
    toast('Session expired', 'The terminal was unauthenticated and has been redirected to PIN lock.', 'error');
    focusPinInput();
}

async function searchProducts(query = barcode.value) {
    const term = String(query ?? '').trim();

    if (!term) {
        focusScannerInput(true);
        return;
    }

    searchBusy.value = true;

    try {
        const response = await window.axios.post('/api/pos/search', {
            query: term,
        });

        searchResults.value = response.data;
        searchQuery.value = term;

        const exactMatch = response.data.find((product) => product.barcode === term || product.sku === term);

        if (exactMatch) {
            addProductToCart(exactMatch);
            barcode.value = '';
            searchResults.value = response.data;
            showSearchModal.value = false;
            focusScannerInput(true);
            return;
        }

        if (response.data.length > 0) {
            showSearchModal.value = true;
        } else {
            toast('No match', `No product matched "${term}".`, 'info');
        }
    } catch (error) {
        toast('Search failed', error?.response?.data?.message ?? 'Unable to search products.', 'error');
    } finally {
        searchBusy.value = false;
    }
}

function addProductToCart(product) {
    const existingItem = cart.value.find((item) => item.product_id === product.id);

    if (existingItem) {
        existingItem.quantity = roundQuantity(existingItem.quantity + 1, existingItem.allow_fractional_sales);
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            quantity: 1,
            base_price: Number(product.base_price),
            discount: 0,
            tax_rate: Number(product.tax_category?.rate ?? 0),
            allow_fractional_sales: Boolean(product.allow_fractional_sales),
        });
    }

    toast('Item added', `${product.name} added to the cart.`, 'success');
    barcode.value = '';
    focusScannerInput(true);
}

function removeItem(productId) {
    cart.value = cart.value.filter((item) => item.product_id !== productId);
}

function changeQty(item, delta) {
    const step = item.allow_fractional_sales ? 0.25 : 1;
    item.quantity = roundQuantity(Number(item.quantity || 0) + (step * delta), item.allow_fractional_sales);

    if (item.quantity <= 0) {
        removeItem(item.product_id);
    }
}

function normalizeQuantity(item) {
    item.quantity = roundQuantity(item.quantity, item.allow_fractional_sales);

    if (item.quantity <= 0) {
        removeItem(item.product_id);
    }
}

function normalizeDiscount(item) {
    const nextDiscount = Math.max(0, Number(item.discount || 0));
    item.discount = Math.min(roundCurrency(nextDiscount), roundCurrency(item.base_price - 0.01));
}

function effectiveUnitPrice(item) {
    const candidate = Number(item.base_price) - Number(item.discount || 0);

    return roundCurrency(Math.max(candidate, 0.01));
}

function lineSubtotal(item) {
    return roundCurrency(Number(item.quantity || 0) * effectiveUnitPrice(item));
}

function lineTax(item) {
    return roundCurrency(lineSubtotal(item) * (Number(item.tax_rate || 0) / 100));
}

function lineTotal(item) {
    return roundCurrency(lineSubtotal(item) + lineTax(item));
}

async function fetchLivePayments() {
    if (!currentUser.value) {
        return;
    }

    liveFeedBusy.value = true;

    try {
        const response = await window.axios.get('/api/pos/mpesa/live-feed');
        livePayments.value = response.data.incoming_payments ?? [];

        if (selectedLivePayment.value) {
            selectedLivePayment.value = livePayments.value.find((payment) => payment.id === selectedLivePayment.value.id) ?? null;
        }
    } catch (error) {
        toast('Live feed unavailable', error?.response?.data?.message ?? 'Unable to refresh pending M-PESA payments.', 'error');
    } finally {
        liveFeedBusy.value = false;
    }
}

function selectLivePayment(payment) {
    selectedLivePayment.value = payment;
    toast('Inbound payment selected', `${payment.transaction_code} is staged for this sale.`, 'success');
}

async function submitCashCheckout() {
    if (cashChange.value < 0) {
        toast('Cash shortfall', 'Cash received is less than the grand total.', 'error');
        return;
    }

    await finalizeCheckout({
        payments: [
            {
                method: 'cash',
                amount: grandTotal.value,
                reference_number: null,
                status: 'completed',
            },
        ],
    });
}

async function submitStkCheckout() {
    if (!stkPhone.value || !/^254\d{9}$/.test(stkPhone.value)) {
        toast('Invalid phone', 'Use Kenyan format 2547XXXXXXXX for STK push.', 'error');
        return;
    }

    checkoutBusy.value = true;
    stkStatusMessage.value = 'Initiating STK push…';

    try {
        const stkResponse = await window.axios.post('/api/pos/mpesa/stk-push', {
            phone: stkPhone.value,
            amount: grandTotal.value,
            reference: transactionId.value,
        });

        stkCheckoutRequestId.value = stkResponse.data.checkout_request_id;
        stkStatusMessage.value = 'STK sent. Recording pending sale…';

        const checkoutResponse = await finalizeCheckout({
            payments: [
                {
                    method: 'mpesa',
                    amount: grandTotal.value,
                    reference_number: stkCheckoutRequestId.value,
                    status: 'pending',
                },
            ],
        }, false);

        closeModals();
        startStkPolling(checkoutResponse.receipt_number, stkCheckoutRequestId.value);
        newSale(false);
        toast('STK pending', `STK push started for ${stkPhone.value}. Sale ${checkoutResponse.receipt_number} is awaiting confirmation.`, 'info');
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Unable to complete the STK workflow.';
        stkStatusMessage.value = message;
        toast('STK failed', message, 'error');
    } finally {
        checkoutBusy.value = false;
    }
}

async function submitLiveFeedCheckout() {
    if (!selectedLivePayment.value) {
        toast('Payment required', 'Select an inbound M-PESA payment from the live feed first.', 'error');
        return;
    }

    await finalizeCheckout({
        claim_transaction_code: selectedLivePayment.value.transaction_code,
        payments: [
            {
                method: 'mpesa',
                amount: grandTotal.value,
                reference_number: selectedLivePayment.value.transaction_code,
                status: 'completed',
            },
        ],
    });
}

async function finalizeCheckout(payload, resetAfterSuccess = true) {
    checkoutBusy.value = true;

    try {
        const response = await window.axios.post('/api/pos/checkout', {
            manager_pin: managerPin.value || null,
            cart: cart.value.map((item) => ({
                product_id: item.product_id,
                quantity: Number(item.quantity),
                override_unit_price: Number(item.discount || 0) > 0 ? effectiveUnitPrice(item) : null,
            })),
            ...payload,
        });

        const adjustments = response.data.pricing_adjustments ?? [];

        if (adjustments.length > 0) {
            adjustments.forEach((adjustment) => {
                toast(
                    adjustment.price_source === 'margin_floor' ? 'Margin floor applied' : 'Server pricing adjustment',
                    `${adjustment.product_name}: ${formatCurrency(Number(adjustment.final_unit_price))} (${adjustment.price_source}).`,
                    adjustment.price_source === 'margin_floor' ? 'error' : 'info',
                );
            });
        } else {
            toast('Checkout complete', `Sale ${response.data.receipt_number} completed successfully.`, 'success');
        }

        if (selectedLivePayment.value) {
            selectedLivePayment.value = null;
            fetchLivePayments();
        }

        if (resetAfterSuccess) {
            closeModals();
            newSale(false);
        }

        return response.data;
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Checkout failed.';
        toast('Checkout blocked', message, 'error');
        throw error;
    } finally {
        checkoutBusy.value = false;
    }
}

function startStkPolling(receiptNumber, checkoutRequestId) {
    stopStkPolling();

    let attempts = 0;
    stkStatusMessage.value = 'Polling STK status…';

    stkPollTimer = window.setInterval(async () => {
        attempts += 1;

        try {
            const statusResponse = await window.axios.post('/api/pos/mpesa/stk-status', {
                checkout_request_id: checkoutRequestId,
            });

            const status = statusResponse.data.status ?? {};
            const isSuccess = status.ResultCode === 0
                || status.ResultCode === '0'
                || status.ResponseCode === '0'
                || String(status.ResultDesc ?? '').toLowerCase().includes('success');

            if (isSuccess) {
                await window.axios.post('/api/pos/mpesa-verify', {
                    CheckoutRequestID: checkoutRequestId,
                });

                stkStatusMessage.value = `STK confirmed for ${receiptNumber}.`;
                toast('M-PESA confirmed', `Sale ${receiptNumber} has been marked paid.`, 'success');
                stopStkPolling();
                return;
            }

            stkStatusMessage.value = status.ResultDesc || status.CustomerMessage || 'Awaiting customer confirmation on handset…';

            if (attempts >= 24) {
                stkStatusMessage.value = 'Polling timed out. Use the verify endpoint later if payment completes.';
                stopStkPolling();
            }
        } catch (error) {
            stkStatusMessage.value = error?.response?.data?.message ?? 'Unable to poll STK status.';

            if (attempts >= 24) {
                stopStkPolling();
            }
        }
    }, 5000);
}

function stopStkPolling() {
    if (stkPollTimer) {
        window.clearInterval(stkPollTimer);
        stkPollTimer = null;
    }
}

function newSale(showMessage = true) {
    stopStkPolling();
    cart.value = [];
    barcode.value = '';
    searchQuery.value = '';
    cashReceived.value = 0;
    managerPin.value = '';
    selectedLivePayment.value = null;
    stkCheckoutRequestId.value = '';
    stkStatusMessage.value = 'Idle';
    transactionId.value = generateTransactionId();
    showSearchModal.value = false;
    showPayModal.value = false;

    if (showMessage) {
        toast('New sale ready', 'The terminal is reset for the next customer.', 'info');
    }

    focusPriorityInput();
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

function toast(title, message, variant = 'info') {
    const id = `${Date.now()}-${Math.random()}`;
    toasts.value.push({ id, title, message, variant });

    window.setTimeout(() => {
        toasts.value = toasts.value.filter((toastItem) => toastItem.id !== id);
    }, 4200);
}

function generateTransactionId() {
    const now = new Date();

    return `TX-${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}-${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}${String(now.getSeconds()).padStart(2, '0')}`;
}

function roundCurrency(value) {
    return Math.round(Number(value || 0) * 100) / 100;
}

function roundQuantity(value, fractionalAllowed) {
    const numericValue = Number(value || 0);

    if (fractionalAllowed) {
        return Math.max(0.25, Math.round(numericValue * 4) / 4);
    }

    return Math.max(1, Math.round(numericValue));
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
}

function shortTimestamp(value) {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('en-KE', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short',
    }).format(new Date(value));
}
</script>
