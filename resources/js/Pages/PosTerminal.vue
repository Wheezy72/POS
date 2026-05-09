<template>
    <Head title="POS Terminal" />

    <div class="flex h-screen w-full overflow-hidden bg-zinc-950 text-zinc-100">
        <!-- LEFT: Cart + Live Feed -->
        <aside class="flex w-[340px] shrink-0 flex-col border-r border-zinc-800 bg-zinc-900">
            <header class="flex items-center justify-between border-b border-zinc-800 px-5 py-4">
                <div>
                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Register</p>
                    <p class="mt-0.5 text-sm font-medium text-zinc-100">{{ currentUser?.name ?? 'Locked' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">{{ transactionId }}</p>
                    <p class="mt-0.5 font-mono text-sm text-zinc-300 tabular-nums">{{ clock }}</p>
                </div>
            </header>

            <div class="flex items-center justify-between px-5 py-3 text-[11px] uppercase tracking-[0.2em] text-zinc-500">
                <span>Cart · {{ totalUnits }} item{{ totalUnits === 1 ? '' : 's' }}</span>
                <button
                    v-if="cart.length"
                    type="button"
                    class="font-medium text-zinc-400 hover:text-zinc-200"
                    @click="newSale(true)"
                >
                    Clear
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 pb-3">
                <ul v-if="cart.length" v-auto-animate class="space-y-1.5">
                    <li
                        v-for="item in cart"
                        :key="item.product_id"
                        class="rounded-lg border border-zinc-800/80 bg-zinc-900 px-3 py-2.5 hover:border-zinc-700"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm text-zinc-100">{{ item.name }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ item.sku }}</p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 text-zinc-500 hover:text-red-400"
                                aria-label="Remove item"
                                @click="removeItem(item.product_id)"
                            >
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <button type="button" class="h-6 w-6 rounded border border-zinc-800 text-zinc-400 hover:border-zinc-700 hover:text-zinc-100" @click="changeQty(item, -1)">−</button>
                                <input
                                    :value="item.quantity"
                                    inputmode="decimal"
                                    class="h-6 w-12 rounded border border-zinc-800 bg-zinc-950 px-2 text-center text-zinc-100 outline-none focus:border-blue-500/60 tabular-nums"
                                    @input="(event) => { item.quantity = Number(event.target.value) || 0 }"
                                    @change="normalizeQuantity(item)"
                                >
                                <button type="button" class="h-6 w-6 rounded border border-zinc-800 text-zinc-400 hover:border-zinc-700 hover:text-zinc-100" @click="changeQty(item, 1)">+</button>
                            </div>
                            <div class="text-right">
                                <p class="text-zinc-400 tabular-nums">{{ formatCurrency(effectiveUnitPrice(item)) }}</p>
                                <p class="font-medium text-zinc-100 tabular-nums">{{ formatCurrency(lineTotal(item)) }}</p>
                            </div>
                        </div>
                    </li>
                </ul>

                <div v-else class="mt-12 flex flex-col items-center text-center">
                    <ShoppingCartIcon class="h-8 w-8 text-zinc-700" />
                    <p class="mt-3 text-sm text-zinc-400">Cart is empty</p>
                    <p class="mt-1 text-xs text-zinc-600">Scan a barcode or press F2 to search.</p>
                </div>
            </div>

            <!-- Totals -->
            <div class="border-t border-zinc-800 px-5 py-4">
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-zinc-400">
                        <dt>Subtotal</dt>
                        <dd class="tabular-nums">{{ formatCurrency(subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <dt>Tax</dt>
                        <dd class="tabular-nums">{{ formatCurrency(tax) }}</dd>
                    </div>
                </dl>
                <div class="mt-3 flex items-baseline justify-between border-t border-zinc-800 pt-3">
                    <span class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Total</span>
                    <span class="text-2xl font-medium text-zinc-50 tabular-nums">{{ formatCurrency(grandTotal) }}</span>
                </div>
            </div>

            <!-- Live feed -->
            <div class="border-t border-zinc-800 bg-zinc-950/40">
                <div class="flex items-center justify-between px-5 py-2.5">
                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Live feed</p>
                    <button
                        type="button"
                        class="text-[11px] text-zinc-500 hover:text-zinc-300"
                        :disabled="liveFeedBusy"
                        @click="fetchLivePayments"
                    >
                        {{ liveFeedBusy ? 'Refreshing…' : 'Refresh' }}
                    </button>
                </div>
                <ul class="max-h-44 overflow-y-auto px-3 pb-3">
                    <li
                        v-for="event in feedItems"
                        :key="event.id"
                        class="flex items-start gap-2.5 rounded px-2 py-1.5 text-xs hover:bg-zinc-900"
                        :class="event.tone === 'error' ? 'text-red-300' : event.tone === 'success' ? 'text-emerald-300' : 'text-zinc-400'"
                    >
                        <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full" :class="event.tone === 'error' ? 'bg-red-500' : event.tone === 'success' ? 'bg-emerald-500' : 'bg-zinc-600'" />
                        <div class="min-w-0">
                            <p class="truncate">{{ event.message }}</p>
                            <p class="mt-0.5 text-[10px] uppercase tracking-[0.2em] text-zinc-600">{{ event.at }}</p>
                        </div>
                    </li>
                    <li v-if="!feedItems.length" class="px-2 py-1.5 text-xs text-zinc-600">No activity yet.</li>
                </ul>
            </div>
        </aside>

        <!-- CENTER: Search + Product Grid -->
        <main class="flex min-w-0 flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <BarcodeIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
                    <input
                        ref="scannerInput"
                        v-model="barcode"
                        type="text"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Scan barcode or type to search products"
                        class="h-11 w-full rounded-lg border border-zinc-800 bg-zinc-900 pl-10 pr-32 text-sm text-zinc-100 placeholder:text-zinc-600 outline-none focus:border-blue-500/60 focus:ring-2 focus:ring-blue-500/30"
                        @keydown.enter.prevent="searchProducts()"
                    >
                    <kbd class="absolute right-3 top-1/2 -translate-y-1/2 rounded border border-zinc-800 bg-zinc-950 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500">Enter</kbd>
                </div>
                <span class="hidden text-[11px] uppercase tracking-[0.22em] text-zinc-500 lg:inline">{{ resultCountLabel }}</span>
            </div>

            <div class="-mx-1 flex shrink-0 gap-2 overflow-x-auto px-1 pb-1">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="shrink-0 rounded-full border px-3.5 py-1.5 text-xs transition"
                    :class="activeCategoryId === category.id
                        ? 'border-zinc-100 bg-zinc-100 text-zinc-950'
                        : 'border-zinc-800 bg-zinc-900 text-zinc-400 hover:border-zinc-700 hover:text-zinc-100'"
                    @click="activeCategoryId = category.id"
                >
                    {{ category.name }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto rounded-lg border border-zinc-800 bg-zinc-900/30 p-3">
                <div v-if="searchBusy" class="px-1 pb-2 text-xs text-zinc-500">Searching…</div>

                <div v-if="visibleProducts.length" class="grid grid-cols-3 gap-4 xl:grid-cols-4 2xl:grid-cols-5">
                    <button
                        v-for="product in visibleProducts"
                        :key="product.id"
                        type="button"
                        class="group relative flex aspect-square flex-col items-center justify-between overflow-hidden rounded-2xl border border-zinc-800/80 p-4 text-center transition hover:border-zinc-600 hover:shadow-lg hover:shadow-black/40 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                        :class="paletteFor(product).cardBg"
                        @click="addProductToCart(product)"
                    >
                        <span class="text-[10px] font-medium uppercase tracking-[0.22em] opacity-70" :class="paletteFor(product).text">
                            {{ categoryLabel(product) }}
                        </span>
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-inner shadow-black/20"
                            :class="paletteFor(product).iconBg"
                        >
                            <component :is="iconFor(product)" class="h-7 w-7" />
                        </span>
                        <div class="w-full">
                            <p class="truncate text-base font-medium" :class="paletteFor(product).text">{{ product.name }}</p>
                            <p class="mt-1 text-sm font-medium tabular-nums" :class="paletteFor(product).price">{{ formatCurrency(Number(product.base_price)) }}</p>
                        </div>
                    </button>
                </div>

                <div v-else class="flex h-full flex-col items-center justify-center text-center">
                    <MagnifyingGlassIcon class="h-8 w-8 text-zinc-700" />
                    <p class="mt-3 text-sm text-zinc-400">No products match.</p>
                    <p class="mt-1 text-xs text-zinc-600">Try a different category or clear the search.</p>
                </div>
            </div>
        </main>

        <!-- RIGHT: Action rail (KBAM-friendly, touchscreen aesthetic) -->
        <aside class="flex w-[240px] shrink-0 flex-col gap-3 border-l border-zinc-800 bg-zinc-900 p-4">
            <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Tender</p>

            <button
                type="button"
                class="flex h-14 items-center gap-3 rounded-xl border border-emerald-500/40 bg-emerald-500/5 px-4 text-left text-emerald-300 transition hover:bg-emerald-500/15 focus:outline-none focus:ring-2 focus:ring-emerald-400/60 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!canCheckout"
                @click="quickCheckout('mpesa')"
            >
                <DevicePhoneMobileIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium tracking-wide">Pay · M-Pesa</span>
                <kbd class="rounded border border-emerald-500/40 bg-emerald-500/10 px-1.5 py-0.5 font-mono text-[10px] text-emerald-200">F4</kbd>
            </button>
            <button
                type="button"
                class="flex h-14 items-center gap-3 rounded-xl border border-blue-500/40 bg-blue-500/5 px-4 text-left text-blue-300 transition hover:bg-blue-500/15 focus:outline-none focus:ring-2 focus:ring-blue-400/60 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!canCheckout"
                @click="quickCheckout('cash')"
            >
                <BanknotesIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium tracking-wide">Pay · Cash</span>
                <kbd class="rounded border border-blue-500/40 bg-blue-500/10 px-1.5 py-0.5 font-mono text-[10px] text-blue-200">F5</kbd>
            </button>
            <button
                type="button"
                class="flex h-14 items-center gap-3 rounded-xl border border-purple-500/40 bg-purple-500/5 px-4 text-left text-purple-300 transition hover:bg-purple-500/15 focus:outline-none focus:ring-2 focus:ring-purple-400/60 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!canCheckout"
                @click="quickCheckout('card')"
            >
                <CreditCardIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium tracking-wide">Pay · Card</span>
                <kbd class="rounded border border-purple-500/40 bg-purple-500/10 px-1.5 py-0.5 font-mono text-[10px] text-purple-200">F6</kbd>
            </button>

            <div class="my-1 h-px bg-zinc-800" />

            <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Actions</p>

            <button
                type="button"
                class="flex h-12 items-center gap-3 rounded-xl border border-amber-500/40 bg-amber-500/5 px-4 text-left text-amber-300 transition hover:bg-amber-500/15 focus:outline-none focus:ring-2 focus:ring-amber-400/60 disabled:cursor-not-allowed disabled:opacity-40"
                @click="openDiscount"
            >
                <TagIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium">Discount</span>
                <kbd class="rounded border border-amber-500/40 bg-amber-500/10 px-1.5 py-0.5 font-mono text-[10px] text-amber-200">F3</kbd>
            </button>
            <button
                type="button"
                class="flex h-12 items-center gap-3 rounded-xl border border-rose-500/40 bg-rose-500/5 px-4 text-left text-rose-300 transition hover:bg-rose-500/15 focus:outline-none focus:ring-2 focus:ring-rose-400/60 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!cart.length"
                @click="voidLastItem"
            >
                <NoSymbolIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium">Void item</span>
            </button>
            <button
                type="button"
                class="flex h-12 items-center gap-3 rounded-xl border border-zinc-700 bg-zinc-800/40 px-4 text-left text-zinc-300 transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500"
                @click="openSearchModal"
            >
                <MagnifyingGlassIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium">Search</span>
                <kbd class="rounded border border-zinc-700 bg-zinc-950 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500">F2</kbd>
            </button>
            <button
                type="button"
                class="flex h-12 items-center gap-3 rounded-xl border border-zinc-700 bg-zinc-800/40 px-4 text-left text-zinc-300 transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500"
                @click="newSale(true)"
            >
                <ArrowPathIcon class="h-5 w-5" />
                <span class="flex-1 text-sm font-medium">New sale</span>
                <kbd class="rounded border border-zinc-700 bg-zinc-950 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500">F1</kbd>
            </button>

            <div class="mt-auto space-y-3 border-t border-zinc-800 pt-3">
                <div v-if="stkStatusMessage" class="rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-2 text-[11px] text-zinc-400">
                    {{ stkStatusMessage }}
                </div>
                <button
                    type="button"
                    class="flex h-12 w-full items-center gap-3 rounded-xl border border-zinc-700 bg-zinc-800/40 px-4 text-left text-zinc-300 transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-500"
                    @click="logout"
                >
                    <LockClosedIcon class="h-5 w-5" />
                    <span class="flex-1 text-sm font-medium">Lock</span>
                    <kbd class="rounded border border-zinc-700 bg-zinc-950 px-1.5 py-0.5 font-mono text-[10px] text-zinc-500">F10</kbd>
                </button>
            </div>
        </aside>

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
import {
    Ban as NoSymbolIcon,
    Banknote as BanknotesIcon,
    Coffee,
    CreditCard as CreditCardIcon,
    Croissant,
    GlassWater,
    Lock as LockClosedIcon,
    Package as CubeIcon,
    Popcorn,
    RefreshCw as ArrowPathIcon,
    ScanBarcode as BarcodeIcon,
    Search as MagnifyingGlassIcon,
    ShoppingBag as BuildingStorefrontIcon,
    ShoppingCart as ShoppingCartIcon,
    Smartphone as DevicePhoneMobileIcon,
    Tag as TagIcon,
    X as XMarkIcon,
} from 'lucide-vue-next';
import PaymentDialog from '../Components/Pos/PaymentDialog.vue';
import PinLockOverlay from '../Components/Pos/PinLockOverlay.vue';
import ProductSearchDialog from '../Components/Pos/ProductSearchDialog.vue';
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
    quickPay: (method) => quickCheckout(method),
    openDiscount: () => openDiscount(),
});

// ---- Mock catalog (replaced by API search results when available) ----
const categories = [
    { id: 'all', name: 'All', tone: 'slate' },
    { id: 'hot', name: 'Hot drinks', tone: 'amber' },
    { id: 'cold', name: 'Cold drinks', tone: 'blue' },
    { id: 'pastries', name: 'Pastries', tone: 'rose' },
    { id: 'snacks', name: 'Snacks', tone: 'emerald' },
    { id: 'household', name: 'Household', tone: 'purple' },
];

const mockCatalog = [
    { id: 'm-001', sku: 'HOT-ESP-1', name: 'Espresso',        base_price: 150, category: 'hot' },
    { id: 'm-002', sku: 'HOT-CAP-1', name: 'Cappuccino',      base_price: 220, category: 'hot' },
    { id: 'm-003', sku: 'HOT-LAT-1', name: 'Latte',           base_price: 250, category: 'hot' },
    { id: 'm-004', sku: 'HOT-AME-1', name: 'Americano',       base_price: 180, category: 'hot' },
    { id: 'm-005', sku: 'HOT-MOC-1', name: 'Mocha',           base_price: 280, category: 'hot' },
    { id: 'm-010', sku: 'CLD-CKE-1', name: 'Coca-Cola 500ml', base_price: 80,  category: 'cold' },
    { id: 'm-011', sku: 'CLD-FNT-1', name: 'Fanta 500ml',     base_price: 80,  category: 'cold' },
    { id: 'm-012', sku: 'CLD-WAT-1', name: 'Water 1L',        base_price: 60,  category: 'cold' },
    { id: 'm-013', sku: 'CLD-JUI-1', name: 'Mango juice',     base_price: 120, category: 'cold' },
    { id: 'm-020', sku: 'PAS-CRO-1', name: 'Croissant',       base_price: 180, category: 'pastries' },
    { id: 'm-021', sku: 'PAS-MUF-1', name: 'Blueberry muffin', base_price: 200, category: 'pastries' },
    { id: 'm-022', sku: 'PAS-DON-1', name: 'Glazed donut',    base_price: 150, category: 'pastries' },
    { id: 'm-030', sku: 'SNK-CRP-1', name: 'Crisps 50g',      base_price: 90,  category: 'snacks' },
    { id: 'm-031', sku: 'SNK-NUT-1', name: 'Roasted nuts',    base_price: 220, category: 'snacks' },
    { id: 'm-032', sku: 'SNK-CHO-1', name: 'Chocolate bar',   base_price: 130, category: 'snacks' },
    { id: 'm-040', sku: 'HSE-SOAP-1', name: 'Bar soap',       base_price: 95,  category: 'household' },
    { id: 'm-041', sku: 'HSE-DET-1',  name: 'Detergent 1kg',  base_price: 350, category: 'household' },
];

const activeCategoryId = ref('all');

const visibleProducts = computed(() => {
    const fromSearch = Array.isArray(searchResults?.value) ? searchResults.value : [];
    const haveSearchResults = fromSearch.length > 0;
    const source = haveSearchResults ? fromSearch : mockCatalog;

    if (activeCategoryId.value === 'all') {
        return source;
    }

    return source.filter((product) => (product.category ?? 'all') === activeCategoryId.value);
});

const resultCountLabel = computed(() => {
    const count = visibleProducts.value.length;
    return `${count} item${count === 1 ? '' : 's'}`;
});

const CATEGORY_PALETTE = {
    hot:        { cardBg: 'bg-amber-500/10 hover:bg-amber-500/15',   iconBg: 'bg-amber-500/20 text-amber-300', text: 'text-amber-100', price: 'text-amber-300/80' },
    cold:       { cardBg: 'bg-blue-500/10 hover:bg-blue-500/15',     iconBg: 'bg-blue-500/20 text-blue-300',   text: 'text-blue-100',  price: 'text-blue-300/80' },
    pastries:   { cardBg: 'bg-rose-500/10 hover:bg-rose-500/15',     iconBg: 'bg-rose-500/20 text-rose-300',   text: 'text-rose-100',  price: 'text-rose-300/80' },
    snacks:     { cardBg: 'bg-emerald-500/10 hover:bg-emerald-500/15', iconBg: 'bg-emerald-500/20 text-emerald-300', text: 'text-emerald-100', price: 'text-emerald-300/80' },
    household:  { cardBg: 'bg-purple-500/10 hover:bg-purple-500/15', iconBg: 'bg-purple-500/20 text-purple-300', text: 'text-purple-100', price: 'text-purple-300/80' },
    default:    { cardBg: 'bg-zinc-800/50 hover:bg-zinc-800',         iconBg: 'bg-zinc-800 text-zinc-300',      text: 'text-zinc-100',  price: 'text-zinc-400' },
};

const CATEGORY_ICON = {
    hot: Coffee,
    cold: GlassWater,
    pastries: Croissant,
    snacks: Popcorn,
    household: BuildingStorefrontIcon,
};

function paletteFor(product) {
    return CATEGORY_PALETTE[product?.category] ?? CATEGORY_PALETTE.default;
}

function iconFor(product) {
    return CATEGORY_ICON[product?.category] ?? CubeIcon;
}

function categoryLabel(product) {
    const match = categories.find((category) => category.id === product?.category);
    return match ? match.name : 'Catalog';
}

const canCheckout = computed(() => cart.value.length > 0 && !checkoutBusy.value);

const feedItems = computed(() => {
    const items = [];

    if (stkStatusMessage.value) {
        items.push({
            id: `stk-${stkCheckoutRequestId.value ?? 'pending'}`,
            message: stkStatusMessage.value,
            tone: 'info',
            at: 'STK',
        });
    }

    livePayments.value.slice(0, 6).forEach((payment) => {
        items.push({
            id: `mp-${payment.id ?? payment.transaction_code}`,
            message: `${payment.transaction_code ?? 'M-Pesa'} · ${formatCurrency(Number(payment.amount ?? 0))}`,
            tone: 'success',
            at: shortTimestamp(payment.received_at ?? payment.created_at),
        });
    });

    toasts.value.slice(-3).forEach((toastItem) => {
        items.push({
            id: `t-${toastItem.id}`,
            message: `${toastItem.title}: ${toastItem.message}`,
            tone: toastItem.variant === 'error' ? 'error' : toastItem.variant === 'success' ? 'success' : 'info',
            at: 'now',
        });
    });

    return items.slice(-8).reverse();
});

function quickCheckout(method) {
    if (!canCheckout.value) {
        toast('Cart empty', 'Add at least one item before taking payment.', 'error');
        return;
    }

    paymentTab.value = method === 'card' ? 'cash' : method === 'mpesa' ? 'stk' : 'cash';
    showPayModal.value = true;

    if (method === 'mpesa') {
        fetchLivePayments();
    }
}

function openDiscount() {
    if (!cart.value.length) {
        toast('Nothing to discount', 'Add an item before applying a discount.', 'error');
        return;
    }

    const last = cart.value[cart.value.length - 1];
    const input = window.prompt(`Discount per unit for ${last.name} (max ${formatCurrency(last.base_price - 0.01)}):`, String(last.discount ?? 0));

    if (input === null) {
        return;
    }

    last.discount = Number(input) || 0;
    normalizeDiscount(last);
    toast('Discount applied', `${last.name}: ${formatCurrency(last.discount)} off per unit.`, 'success');
}

function voidLastItem() {
    const last = cart.value[cart.value.length - 1];

    if (!last) {
        return;
    }

    removeItem(last.product_id);
    toast('Item voided', `${last.name} removed from cart.`, 'info');
}

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
