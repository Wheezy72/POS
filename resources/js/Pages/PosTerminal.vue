<template>
    <Head title="POS Terminal" />

    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-[1800px] flex-col gap-4 px-4 py-4">
            <TerminalHeader
                ref="scannerInput"
                v-model:barcode="barcode"
                :clock="clock"
                :current-user="currentUser"
                :result-count="searchResults.length"
                :transaction-id="transactionId"
                @search="searchProducts()"
            />

            <main class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1fr)_13rem]">
                <section class="grid min-h-0 gap-3">
                    <CartTable
                        :cart="cart"
                        :effective-unit-price="effectiveUnitPrice"
                        :format-currency="formatCurrency"
                        :line-total="lineTotal"
                        :total-units="totalUnits"
                        @change-quantity="changeQty"
                        @normalize-discount="normalizeDiscount"
                        @normalize-quantity="normalizeQuantity"
                        @remove-item="removeItem"
                    />

                    <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                        <ProductSearchPanel
                            :format-currency="formatCurrency"
                            :search-busy="searchBusy"
                            :search-results="searchResults"
                            @open-search="openSearchModal"
                            @select-product="addProductToCart"
                        />

                        <MpesaLiveFeed
                            :format-currency="formatCurrency"
                            :live-feed-busy="liveFeedBusy"
                            :live-payments="livePayments"
                            :selected-live-payment="selectedLivePayment"
                            :short-timestamp="shortTimestamp"
                            @refresh="fetchLivePayments"
                            @select-payment="selectLivePayment"
                        />
                    </section>
                </section>

                <PosActionRail
                    :format-currency="formatCurrency"
                    :selected-live-payment="selectedLivePayment"
                    :stk-checkout-request-id="stkCheckoutRequestId"
                    :stk-status-message="stkStatusMessage"
                    @logout="logout"
                    @new-sale="newSale"
                    @open-pay="openPayModal"
                    @open-search="openSearchModal"
                />
            </main>

            <footer class="ml-auto w-full max-w-[28rem] rounded-3xl border border-slate-200 bg-white text-slate-950 shadow-sm">
                <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-2 px-4 py-4">
                    <p class="text-sm font-bold uppercase tracking-[0.18em]">Subtotal</p>
                    <p class="text-right text-lg font-black">{{ formatCurrency(subtotal) }}</p>
                    <p class="text-sm font-bold uppercase tracking-[0.18em]">Tax</p>
                    <p class="text-right text-lg font-black">{{ formatCurrency(tax) }}</p>
                    <p class="border-t border-slate-200 pt-2 text-lg font-black uppercase tracking-[0.18em]">Grand Total</p>
                    <p class="border-t border-slate-200 pt-2 text-right text-4xl font-black tracking-tight text-emerald-600">{{ formatCurrency(grandTotal) }}</p>
                </div>
            </footer>
        </div>

        <ToastStack :toasts="toasts" />

        <div v-if="showSearchModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-4xl rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Product search</p>
                    </div>
                    <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold hover:bg-slate-50" @click="closeModals()">Esc</button>
                </div>
                <div class="p-4">
                    <input
                        ref="searchInput"
                        v-model.trim="searchQuery"
                        type="text"
                        autocomplete="off"
                        class="h-14 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-lg font-bold outline-none focus:border-sky-500 focus:bg-white"
                        placeholder="Search by barcode, SKU, or product name"
                        @keydown.enter.prevent="searchProducts(searchQuery)"
                    >
                    <div class="mt-4 max-h-[24rem] overflow-auto rounded-2xl border border-slate-200">
                        <div v-if="searchBusy" class="px-4 py-6 text-sm text-slate-500">Searching…</div>
                        <div v-else-if="searchResults.length === 0" class="px-4 py-6 text-sm text-slate-500">No products found.</div>
                        <button
                            v-for="product in searchResults"
                            :key="product.id"
                            class="grid w-full grid-cols-[1fr_auto_auto] gap-3 border-b border-slate-200 px-4 py-3 text-left hover:bg-slate-50"
                            @click="addProductToCart(product); showSearchModal = false"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ product.name }}</p>
                                <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ product.sku }}</p>
                            </div>
                            <p class="font-bold text-slate-900">{{ formatCurrency(Number(product.base_price)) }}</p>
                            <p class="text-[11px] uppercase tracking-[0.16em]" :class="Number(product.stock_quantity) < 10 ? 'text-red-600' : 'text-slate-500'">
                                {{ Number(product.stock_quantity).toFixed(2) }}
                            </p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPayModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-5xl rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Tender sale</p>
                    </div>
                    <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold hover:bg-slate-50" @click="closeModals()">Esc</button>
                </div>

                <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <section>
                        <div class="flex gap-2">
                            <button class="rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTab === 'cash' ? 'border-yellow-300 bg-yellow-300 text-slate-950' : 'border-slate-300 bg-white text-slate-700'" @click="paymentTab = 'cash'">Cash</button>
                            <button class="rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTab === 'stk' ? 'border-yellow-300 bg-yellow-300 text-slate-950' : 'border-slate-300 bg-white text-slate-700'" @click="paymentTab = 'stk'">M-PESA STK</button>
                            <button class="rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTab === 'live' ? 'border-yellow-300 bg-yellow-300 text-slate-950' : 'border-slate-300 bg-white text-slate-700'" @click="paymentTab = 'live'">C2B Live Feed</button>
                            <button v-if="creditSalesEnabled" class="rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTab === 'credit' ? 'border-yellow-300 bg-yellow-300 text-slate-950' : 'border-slate-300 bg-white text-slate-700'" @click="paymentTab = 'credit'">[F7] Pay Later</button>
                        </div>

                        <div v-if="paymentTab === 'cash'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Cash received</label>
                            <input v-model.number="cashReceived" type="number" min="0" step="0.01" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-xl font-black outline-none">
                            <div class="grid gap-2 sm:grid-cols-4">
                                <button v-for="preset in cashPresets" :key="preset" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold hover:bg-slate-100" @click="cashReceived = preset">
                                    {{ formatCurrency(preset) }}
                                </button>
                            </div>
                            <p class="text-sm" :class="cashChange >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                Change: {{ formatCurrency(cashChange) }}
                            </p>
                            <button class="rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="submitCashCheckout()">
                                Complete cash sale
                            </button>
                        </div>

                        <div v-else-if="paymentTab === 'stk'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Customer phone</label>
                            <input v-model.trim="stkPhone" type="text" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none" placeholder="2547XXXXXXXX">
                            <button class="rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="submitStkCheckout()">
                                Start STK and checkout
                            </button>
                        </div>

                        <div v-else-if="paymentTab === 'live'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="max-h-64 overflow-auto rounded-2xl border border-slate-200 bg-white">
                                <button
                                    v-for="payment in livePayments"
                                    :key="payment.id"
                                    class="w-full border-b border-slate-200 px-3 py-2 text-left hover:bg-slate-50"
                                    :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-50' : ''"
                                    @click="selectLivePayment(payment)"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">{{ payment.customer_name }}</p>
                                            <p class="truncate text-[11px] uppercase tracking-[0.16em] text-slate-500">{{ payment.transaction_code }}</p>
                                        </div>
                                        <p class="font-bold text-emerald-600">{{ formatCurrency(Number(payment.amount)) }}</p>
                                    </div>
                                </button>
                            </div>
                            <button class="rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:opacity-50" :disabled="checkoutBusy || !selectedLivePayment || cart.length === 0" @click="submitLiveFeedCheckout()">
                                Claim inbound payment and checkout
                            </button>
                        </div>

                        <div v-else class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Customer phone</label>
                            <input v-model.trim="customerPhone" type="text" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none" placeholder="2547XXXXXXXX">
                            <button class="rounded-2xl border border-yellow-300 bg-yellow-300 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-slate-950 hover:bg-yellow-200 disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0 || !customerPhone" @click="submitCreditCheckout()">
                                Save as credit sale
                            </button>
                        </div>
                    </section>

                    <aside class="grid gap-3">
                        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Manager PIN</p>
                            <input v-model.trim="managerPin" type="password" maxlength="6" class="mt-3 h-12 w-full rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none" placeholder="Only needed below margin floor">
                        </section>

                        <section class="rounded-3xl border border-yellow-300 bg-yellow-300 p-4 text-slate-950 shadow-sm">
                            <p class="text-sm font-black uppercase tracking-[0.18em]">Summary</p>
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
            <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 text-slate-900 shadow-2xl">
                <p class="text-[11px] uppercase tracking-[0.25em] text-slate-500">PIN Lock</p>
                <h1 class="mt-2 text-3xl font-black">{{ overlayHeading }}</h1>
                <p v-if="blockedRole" class="mt-2 text-sm text-red-600">
                    Logged-in role "{{ blockedRole }}" cannot operate the cashier terminal.
                </p>

                <label class="mt-5 block text-[11px] font-bold uppercase tracking-[0.25em] text-slate-500">{{ overlayLabel }}</label>
                <input
                    ref="pinInput"
                    v-model.trim="pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
                    class="mt-2 h-14 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-2xl font-black tracking-[0.4em] outline-none placeholder:tracking-normal placeholder:text-slate-400"
                    placeholder="0000"
                    @keydown.enter.prevent="loginWithPin()"
                >

                <button class="mt-5 w-full rounded-2xl border border-emerald-500 bg-emerald-500 px-4 py-4 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-400 disabled:opacity-50" :disabled="pinBusy" @click="loginWithPin()">
                    {{ pinBusy ? 'Unlocking…' : 'Unlock register' }}
                </button>

                <p class="mt-4 text-xs text-slate-500">Enter a valid staff PIN to continue.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import CartTable from '../Components/Pos/CartTable.vue';
import MpesaLiveFeed from '../Components/Pos/MpesaLiveFeed.vue';
import PosActionRail from '../Components/Pos/PosActionRail.vue';
import ProductSearchPanel from '../Components/Pos/ProductSearchPanel.vue';
import TerminalHeader from '../Components/Pos/TerminalHeader.vue';
import ToastStack from '../Components/Pos/ToastStack.vue';
import { formatCurrency, roundCurrency, shortTimestamp } from '../utils/formatters';
import { useAudioFeedback } from '../composables/pos/useAudioFeedback';
import { useCart } from '../composables/pos/useCart';
import { usePosApi } from '../composables/pos/usePosApi';
import { useProductSearch } from '../composables/pos/useProductSearch';
import { useScannerFocus } from '../composables/pos/useScannerFocus';
import { useStkPolling } from '../composables/pos/useStkPolling';
import { useTerminalKeyboard } from '../composables/pos/useTerminalKeyboard';
import { useToasts } from '../composables/pos/useToasts';

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
const settings = computed(() => page.props.settings ?? {});

const scannerInput = ref(null);
const pinInput = ref(null);
const searchInput = ref(null);

const currentUser = ref(page.props.auth?.user ?? null);
const blockedRole = computed(() => page.props.auth?.blockedRole ?? null);

const clock = ref('');
const barcode = ref('');
const searchQuery = ref('');
const livePayments = ref([]);
const selectedLivePayment = ref(null);
const showSearchModal = ref(false);
const showPayModal = ref(false);
const showPinOverlay = ref(!currentUser.value);
const paymentTab = ref('cash');
const pin = ref('');
const managerPin = ref('');
const customerPhone = ref('');
const cashReceived = ref(0);
const stkPhone = ref('2547');
const checkoutBusy = ref(false);
const pinBusy = ref(false);
const liveFeedBusy = ref(false);
const transactionId = ref(generateTransactionId());

let clockTimer = null;
let liveFeedTimer = null;

const posApi = usePosApi();
const { playSuccessBeep, playErrorBuzz } = useAudioFeedback();
const { toasts, toast } = useToasts({ onError: playErrorBuzz });
const {
    cart,
    subtotal,
    tax,
    grandTotal,
    totalUnits,
    addProductToCart: addProductToCartWithoutFeedback,
    removeItem,
    changeQty,
    normalizeQuantity,
    normalizeDiscount,
    effectiveUnitPrice,
    lineTotal,
    resetCart,
} = useCart();
const {
    stkCheckoutRequestId,
    stkStatusMessage,
    startStkPolling,
    stopStkPolling,
    resetStkStatus,
} = useStkPolling(posApi, toast);
const creditSalesEnabled = computed(() => Boolean(settings.value.enable_credit_sales));
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

const {
    focusPriorityInput,
    focusPinInput,
    focusScannerInput,
} = useScannerFocus({
    scannerInput,
    pinInput,
    searchInput,
    showPinOverlay,
    showSearchModal,
    showPayModal,
});

const {
    searchResults,
    searchBusy,
    queueProductSearch,
    searchProducts,
    stopProductSearch,
    resetProductSearch,
} = useProductSearch(posApi, {
    addProduct: addProductToCart,
    focusScannerInput,
    toast,
    showSearchModal,
    barcode,
    searchQuery,
});

useTerminalKeyboard({
    closeModals,
    newSale,
    openSearchModal,
    openPayModal,
    logout,
    focusScannerInput,
    creditSalesEnabled,
    paymentTab,
});

watch([showSearchModal, showPayModal, showPinOverlay], async () => {
    await nextTick();
    focusPriorityInput();
});

watch(searchQuery, (value) => {
    if (!showSearchModal.value) {
        return;
    }

    queueProductSearch(value, {
        autoSelectExact: false,
        notifyOnEmpty: false,
        openModalOnResults: false,
    });
});

watch(barcode, (value) => {
    if (showSearchModal.value || showPayModal.value || showPinOverlay.value) {
        return;
    }

    queueProductSearch(value, {
        autoSelectExact: false,
        notifyOnEmpty: false,
        openModalOnResults: false,
    });
});

onMounted(() => {
    updateClock();
    clockTimer = window.setInterval(updateClock, 1000);
    liveFeedTimer = window.setInterval(() => {
        if (currentUser.value && !showPinOverlay.value) {
            fetchLivePayments();
        }
    }, 15000);

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

    if (liveFeedTimer) {
        window.clearInterval(liveFeedTimer);
    }

    stopProductSearch();
    stopStkPolling();

    window.removeEventListener('pos:unauthenticated', handleUnauthenticated);
});

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

async function loginWithPin() {
    if (!pin.value) {
        toast('PIN required', 'Enter a staff PIN to unlock the register.', 'error');
        return;
    }

    pinBusy.value = true;

    try {
        const response = await posApi.loginWithPin(pin.value);

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
            await posApi.logout();
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

function addProductToCart(product) {
    addProductToCartWithoutFeedback(product);

    toast('Item added', `${product.name} added to the cart.`, 'success');
    playSuccessBeep();
    barcode.value = '';
    focusScannerInput(true);
}

async function fetchLivePayments() {
    if (!currentUser.value) {
        return;
    }

    liveFeedBusy.value = true;

    try {
        const response = await posApi.fetchLivePayments();
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
        const stkResponse = await posApi.startStkPush({
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

async function submitCreditCheckout() {
    if (!creditSalesEnabled.value) {
        toast('Credit disabled', 'Credit sales are disabled for this shop.', 'error');
        return;
    }

    if (!customerPhone.value) {
        toast('Customer required', 'Enter the customer phone number before saving a credit sale.', 'error');
        return;
    }

    await finalizeCheckout({
        customer_phone: customerPhone.value,
        payments: [
            {
                method: 'credit_deni',
                amount: grandTotal.value,
                reference_number: customerPhone.value,
                status: 'pending',
            },
        ],
    });
}

async function finalizeCheckout(payload, resetAfterSuccess = true) {
    checkoutBusy.value = true;

    try {
        const response = await posApi.checkout({
            customer_phone: customerPhone.value || null,
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

function newSale(showMessage = true) {
    stopStkPolling();
    resetCart();
    barcode.value = '';
    searchQuery.value = '';
    resetProductSearch();
    cashReceived.value = 0;
    managerPin.value = '';
    customerPhone.value = '';
    selectedLivePayment.value = null;
    resetStkStatus();
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

function generateTransactionId() {
    const now = new Date();

    return `TX-${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}-${String(now.getHours()).padStart(2, '0')}${String(now.getMinutes()).padStart(2, '0')}${String(now.getSeconds()).padStart(2, '0')}`;
}
</script>
