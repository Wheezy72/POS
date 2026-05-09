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

            <TotalsFooter
                :format-currency="formatCurrency"
                :grand-total="grandTotal"
                :subtotal="subtotal"
                :tax="tax"
            />
        </div>

        <ToastStack :toasts="toasts" />

        <ProductSearchDialog
            ref="searchInput"
            v-model:search-query="searchQuery"
            :format-currency="formatCurrency"
            :search-busy="searchBusy"
            :search-results="searchResults"
            :show="showSearchModal"
            @close="closeModals"
            @search="searchProducts"
            @select-product="selectSearchProduct"
        />

        <PaymentDialog
            ref="paymentInput"
            v-model:cash-received="cashReceived"
            v-model:customer-phone="customerPhone"
            v-model:manager-pin="managerPin"
            v-model:payment-tab="paymentTab"
            v-model:stk-phone="stkPhone"
            :cart="cart"
            :cash-change="cashChange"
            :cash-presets="cashPresets"
            :checkout-busy="checkoutBusy"
            :credit-sales-enabled="creditSalesEnabled"
            :format-currency="formatCurrency"
            :grand-total="grandTotal"
            :live-payments="livePayments"
            :selected-live-payment="selectedLivePayment"
            :show="showPayModal"
            :subtotal="subtotal"
            :tax="tax"
            @cash-checkout="submitCashCheckout"
            @close="closeModals"
            @credit-checkout="submitCreditCheckout"
            @live-feed-checkout="submitLiveFeedCheckout"
            @select-live-payment="selectLivePayment"
            @stk-checkout="submitStkCheckout"
        />

        <PinLockOverlay
            ref="pinInput"
            v-model:pin="pin"
            :blocked-role="blockedRole"
            :busy="pinBusy"
            :heading="props.overlayHeading"
            :label="props.overlayLabel"
            :show="showPinOverlay"
            @submit="loginWithPin"
        />
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import CartTable from '../Components/Pos/CartTable.vue';
import MpesaLiveFeed from '../Components/Pos/MpesaLiveFeed.vue';
import PaymentDialog from '../Components/Pos/PaymentDialog.vue';
import PinLockOverlay from '../Components/Pos/PinLockOverlay.vue';
import PosActionRail from '../Components/Pos/PosActionRail.vue';
import ProductSearchDialog from '../Components/Pos/ProductSearchDialog.vue';
import ProductSearchPanel from '../Components/Pos/ProductSearchPanel.vue';
import TerminalHeader from '../Components/Pos/TerminalHeader.vue';
import ToastStack from '../Components/Pos/ToastStack.vue';
import TotalsFooter from '../Components/Pos/TotalsFooter.vue';
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
const paymentInput = ref(null);

const initialUser = page.props.auth?.user;
const currentUser = ref(initialUser?.role === 'cashier' ? initialUser : null);
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
const checkoutId = ref(generateCheckoutId());

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
    paymentInput,
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

function selectSearchProduct(product) {
    addProductToCart(product);
    showSearchModal.value = false;
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
            client_checkout_id: checkoutId.value,
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
    checkoutId.value = generateCheckoutId();
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

function generateCheckoutId() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}
</script>
