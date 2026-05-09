<template>
    <Head title="POS Terminal" />

    <div class="grid h-screen w-full grid-cols-[35%_45%_20%] grid-rows-[4.75rem_minmax(0,1fr)] overflow-hidden bg-[#0f172a] text-slate-100">
        <aside class="row-span-2 flex min-w-0 flex-col border-r border-slate-700/70 bg-[#111827]">
            <header class="flex h-[4.75rem] items-center justify-between border-b border-slate-700/70 px-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-slate-500">Active cart</p>
                    <p class="mt-1 truncate text-sm font-medium text-slate-100">{{ currentUser?.name ?? 'Register locked' }}</p>
                </div>
                <div class="text-right font-mono">
                    <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">{{ transactionId }}</p>
                    <p class="mt-1 text-sm text-slate-300 tabular-nums">{{ clock }}</p>
                </div>
            </header>

            <div class="grid grid-cols-[minmax(0,1fr)_4.75rem_7.5rem_2rem] border-b border-slate-700/70 bg-slate-950/40 px-3 py-2 text-[10px] font-medium uppercase tracking-[0.18em] text-slate-500">
                <span>Name</span>
                <span class="text-center">Qty</span>
                <span class="text-right">Price</span>
                <span></span>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <ul v-if="cart.length" v-auto-animate class="divide-y divide-slate-800/80">
                    <li
                        v-for="item in cart"
                        :key="item.product_id"
                        class="grid grid-cols-[minmax(0,1fr)_4.75rem_7.5rem_2rem] items-center gap-2 px-3 py-2 text-sm hover:bg-slate-800/45"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-100">{{ item.name }}</p>
                            <p class="mt-0.5 truncate font-mono text-[11px] text-slate-500">{{ item.sku }}</p>
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <button type="button" class="qty-button" aria-label="Reduce quantity" @click="changeQty(item, -1)">−</button>
                            <input
                                :value="item.quantity"
                                inputmode="decimal"
                                class="h-7 w-10 rounded border border-slate-700 bg-slate-950 px-1 text-center font-mono text-xs text-slate-100 outline-none focus:border-sky-400/70"
                                @input="(event) => { item.quantity = Number(event.target.value) || 0 }"
                                @change="normalizeQuantity(item)"
                            >
                            <button type="button" class="qty-button" aria-label="Increase quantity" @click="changeQty(item, 1)">+</button>
                        </div>
                        <div class="text-right font-mono">
                            <p class="text-[11px] text-slate-500 tabular-nums">{{ formatKsh(effectiveUnitPrice(item)) }}</p>
                            <p class="font-medium text-amber-300 tabular-nums">{{ formatKsh(lineTotal(item)) }}</p>
                        </div>
                        <button type="button" class="text-sm text-slate-500 hover:text-rose-300" aria-label="Remove item" @click="removeItem(item.product_id)">×</button>
                    </li>
                </ul>

                <div v-else class="flex h-full flex-col items-center justify-center px-8 text-center">
                    <p class="text-sm font-medium text-slate-300">Cart is empty</p>
                    <p class="mt-2 text-xs text-slate-500">Scan a barcode, search with <kbd class="shortcut">F2</kbd>, or tap a product card.</p>
                </div>
            </div>

            <div class="border-t border-slate-700/70 bg-slate-950 px-4 py-4">
                <dl class="grid gap-1.5 text-sm">
                    <div class="flex justify-between text-slate-400">
                        <dt>Subtotal</dt>
                        <dd class="font-mono tabular-nums">{{ formatKsh(subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <dt>Tax</dt>
                        <dd class="font-mono tabular-nums">{{ formatKsh(tax) }}</dd>
                    </div>
                </dl>
                <div class="mt-3 rounded-xl border border-amber-400/50 bg-amber-400/10 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-[11px] font-medium uppercase tracking-[0.22em] text-amber-200">Grand total</span>
                        <span class="font-mono text-3xl font-medium text-amber-300 tabular-nums">{{ formatKsh(grandTotal) }}</span>
                    </div>
                </div>
            </div>
        </aside>

        <header class="col-span-2 flex items-center gap-3 border-b border-slate-700/70 bg-[#111827] px-4">
            <div class="relative flex-1">
                <input
                    ref="scannerInput"
                    v-model="barcode"
                    type="text"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="Scan barcode or search product name"
                    class="h-12 w-full rounded-lg border border-sky-400/60 bg-slate-950 px-4 pr-28 text-base font-medium text-slate-100 outline-none placeholder:text-slate-500 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/25"
                    @keydown.enter.prevent="searchProducts()"
                >
                <kbd class="absolute right-3 top-1/2 -translate-y-1/2 shortcut border-sky-400/50 text-sky-200">Enter</kbd>
            </div>
            <button type="button" class="top-action" @click="openSearchModal">
                Search <kbd class="shortcut">F2</kbd>
            </button>
            <div class="hidden text-right font-mono text-[11px] uppercase tracking-[0.18em] text-slate-500 xl:block">
                {{ resultCountLabel }}
            </div>
        </header>

        <main class="flex min-w-0 flex-col gap-3 bg-[#0f172a] p-3">
            <div class="flex shrink-0 gap-2 overflow-x-auto pb-1">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    type="button"
                    class="category-tab"
                    :class="activeCategoryId === category.id ? 'category-tab-active' : ''"
                    @click="activeCategoryId = category.id"
                >
                    {{ category.name }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto rounded-lg border border-slate-700/70 bg-slate-950/35 p-2">
                <div v-if="searchBusy" class="px-1 pb-2 text-xs text-sky-300">Searching catalog…</div>

                <div v-if="visibleProducts.length" class="grid grid-cols-3 gap-2 2xl:grid-cols-4">
                    <button
                        v-for="product in visibleProducts"
                        :key="product.id"
                        type="button"
                        class="product-card"
                        @click="addProductToCart(product)"
                    >
                        <span class="min-w-0 truncate text-left text-sm font-medium text-slate-100">{{ product.name }}</span>
                        <span class="font-mono text-sm font-medium text-amber-300 tabular-nums">{{ formatKsh(Number(product.base_price)) }}</span>
                    </button>
                </div>

                <div v-else class="flex h-full flex-col items-center justify-center text-center">
                    <p class="text-sm font-medium text-slate-300">No products match.</p>
                    <p class="mt-1 text-xs text-slate-500">Try another category or scan/search again.</p>
                </div>
            </div>
        </main>

        <aside class="flex min-w-0 flex-col gap-3 overflow-y-auto border-l border-slate-700/70 bg-[#111827] p-3">
            <section class="control-section">
                <p class="control-heading">Tender</p>
                <div class="grid gap-2">
                    <button type="button" class="control-button control-primary" :disabled="!canCheckout" @click="quickCheckout('mpesa')">
                        <span>M-Pesa</span><kbd class="shortcut">F4</kbd>
                    </button>
                    <button type="button" class="control-button control-primary" :disabled="!canCheckout" @click="quickCheckout('cash')">
                        <span>Cash</span><kbd class="shortcut">F5</kbd>
                    </button>
                    <button type="button" class="control-button control-primary" :disabled="!canCheckout" @click="quickCheckout('card')">
                        <span>Card</span><kbd class="shortcut">F6</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!canCheckout" @click="openPayModal">
                        <span>Split pay</span><kbd class="shortcut">F8</kbd>
                    </button>
                </div>
            </section>

            <section class="control-section">
                <p class="control-heading">Sale controls</p>
                <div class="grid gap-2">
                    <button type="button" class="control-button control-warn" @click="openDiscount">
                        <span>Discount</span><kbd class="shortcut">F3</kbd>
                    </button>
                    <button type="button" class="control-button control-danger" :disabled="!cart.length" @click="voidLastItem">
                        <span>Void item</span><kbd class="shortcut">Del</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!cart.length" @click="holdSale">
                        <span>Hold sale</span><kbd class="shortcut">F7</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!heldSales.length" @click="recallHeldSale">
                        <span>Recall held</span><span class="font-mono text-xs text-slate-400">{{ heldSales.length }}</span>
                    </button>
                    <button type="button" class="control-button control-danger" :disabled="!cart.length" @click="cancelAll">
                        <span>Cancel all</span><kbd class="shortcut">Ctrl+X</kbd>
                    </button>
                </div>
            </section>

            <section class="control-section">
                <p class="control-heading">Customer · drawer</p>
                <div class="grid gap-2">
                    <button type="button" class="control-button" @click="promptAssignCustomer">
                        <span>Assign customer</span><kbd class="shortcut">F9</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!cart.length" @click="promptPriceOverride">
                        <span>Price override</span><kbd class="shortcut">Ctrl+P</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!cart.length" @click="promptQuantity">
                        <span>Set qty</span><kbd class="shortcut">Ctrl+Q</kbd>
                    </button>
                    <button type="button" class="control-button" @click="addMiscItem">
                        <span>Misc item</span><kbd class="shortcut">Ctrl+M</kbd>
                    </button>
                    <button type="button" class="control-button" @click="recordDrawerOpen">
                        <span>Open drawer</span><kbd class="shortcut">Ctrl+D</kbd>
                    </button>
                    <button type="button" class="control-button" @click="recordCashDrop">
                        <span>Cash drop</span><kbd class="shortcut">Alt+D</kbd>
                    </button>
                    <button type="button" class="control-button" @click="recordPayout">
                        <span>Payout</span><kbd class="shortcut">Alt+P</kbd>
                    </button>
                </div>
            </section>

            <section class="control-section">
                <p class="control-heading">System</p>
                <div v-if="stkStatusMessage" class="mb-2 rounded border border-sky-400/30 bg-sky-400/10 px-3 py-2 text-xs text-sky-200">
                    {{ stkStatusMessage }}
                </div>
                <div class="grid gap-2">
                    <button type="button" class="control-button" @click="newSale(true)">
                        <span>New sale</span><kbd class="shortcut">F1</kbd>
                    </button>
                    <button type="button" class="control-button" :disabled="!lastReceiptNumber" @click="reprintReceipt">
                        <span>Reprint</span><kbd class="shortcut">Ctrl+R</kbd>
                    </button>
                    <button type="button" class="control-button control-danger" :disabled="!lastSaleId" @click="voidLastSalePrompt">
                        <span>Void sale</span><kbd class="shortcut">Alt+V</kbd>
                    </button>
                    <button type="button" class="control-button" @click="openSettings">
                        <span>Settings</span><kbd class="shortcut">F12</kbd>
                    </button>
                    <button type="button" class="control-button control-danger" @click="logout">
                        <span>Logout</span><kbd class="shortcut">F10</kbd>
                    </button>
                </div>
            </section>
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

<style scoped>
.shortcut { border:1px solid rgb(51 65 85); border-radius:0.25rem; background:rgb(15 23 42); padding:0.05rem 0.35rem; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-size:10px; font-weight:500; color:rgb(148 163 184); white-space:nowrap; }
.qty-button { display:flex; height:1.75rem; width:1.5rem; align-items:center; justify-content:center; border-radius:0.25rem; border:1px solid rgb(51 65 85); background:rgb(15 23 42); color:rgb(203 213 225); }
.qty-button:hover { border-color:rgb(56 189 248); color:rgb(125 211 252); }
.top-action { display:flex; height:3rem; align-items:center; gap:0.5rem; border-radius:0.5rem; border:1px solid rgba(56, 189, 248, 0.45); background:rgba(56, 189, 248, 0.10); padding:0 0.9rem; font-size:13px; font-weight:500; color:rgb(125 211 252); }
.top-action:hover { background:rgba(56, 189, 248, 0.18); }
.category-tab { flex-shrink:0; border-radius:0.5rem; border:1px solid rgb(51 65 85); background:rgb(17 24 39); padding:0.55rem 0.8rem; font-size:12px; font-weight:500; color:rgb(203 213 225); transition:background-color 150ms, border-color 150ms, color 150ms; }
.category-tab:hover { border-color:rgb(71 85 105); background:rgb(30 41 59); color:rgb(241 245 249); }
.category-tab-active { border-color:rgba(56, 189, 248, 0.7); background:rgba(56, 189, 248, 0.12); color:rgb(125 211 252); }
.product-card { display:flex; min-height:4.4rem; flex-direction:column; justify-content:space-between; gap:0.75rem; border-radius:0.5rem; border:1px solid rgb(51 65 85); background:rgb(17 24 39); padding:0.75rem; text-align:left; transition:background-color 150ms, border-color 150ms; }
.product-card:hover { border-color:rgba(56, 189, 248, 0.65); background:rgb(30 41 59); }
.product-card:focus { outline:2px solid rgba(56, 189, 248, 0.5); outline-offset:2px; }
.control-section { border-radius:0.75rem; border:1px solid rgba(51, 65, 85, 0.9); background:rgba(15, 23, 42, 0.65); padding:0.75rem; }
.control-heading { margin-bottom:0.55rem; font-size:10px; font-weight:500; letter-spacing:0.22em; text-transform:uppercase; color:rgb(100 116 139); }
.control-button { display:flex; min-height:2.45rem; align-items:center; justify-content:space-between; gap:0.65rem; border-radius:0.45rem; border:1px solid rgb(51 65 85); background:rgb(17 24 39); padding:0.5rem 0.65rem; text-align:left; font-size:12px; font-weight:500; color:rgb(226 232 240); transition:background-color 150ms, border-color 150ms, color 150ms; }
.control-button:hover:not(:disabled) { border-color:rgb(71 85 105); background:rgb(30 41 59); }
.control-button:disabled { cursor:not-allowed; opacity:0.4; }
.control-primary { border-color:rgba(56, 189, 248, 0.55); background:rgba(56, 189, 248, 0.10); color:rgb(125 211 252); }
.control-primary:hover:not(:disabled) { background:rgba(56, 189, 248, 0.18); }
.control-warn { border-color:rgba(251, 191, 36, 0.55); background:rgba(251, 191, 36, 0.10); color:rgb(252 211 77); }
.control-danger { border-color:rgba(244, 63, 94, 0.5); background:rgba(244, 63, 94, 0.08); color:rgb(253 164 175); }
</style>


<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Archive as ArchiveIcon,
    ArrowDown as ArrowDownIcon,
    ArrowUp as ArrowUpIcon,
    Ban as NoSymbolIcon,
    Banknote as BanknotesIcon,
    Coffee,
    CreditCard as CreditCardIcon,
    Croissant,
    GlassWater,
    Hash as HashIcon,
    List as ListIcon,
    Lock as LockClosedIcon,
    Package as CubeIcon,
    PackagePlus as PackagePlusIcon,
    Pause as PauseIcon,
    Pencil as PencilIcon,
    Popcorn,
    Printer as PrinterIcon,
    RefreshCw as ArrowPathIcon,
    RotateCcw as UndoIcon,
    ScanBarcode as BarcodeIcon,
    Search as MagnifyingGlassIcon,
    ShoppingBag as BuildingStorefrontIcon,
    ShoppingCart as ShoppingCartIcon,
    Smartphone as DevicePhoneMobileIcon,
    Tag as TagIcon,
    User as UserIcon,
    X as XMarkIcon,
} from 'lucide-vue-next';
import PaymentDialog from '../Components/Pos/PaymentDialog.vue';
import PinLockOverlay from '../Components/Pos/PinLockOverlay.vue';
import ProductSearchDialog from '../Components/Pos/ProductSearchDialog.vue';
import ToastStack from '../Components/Pos/ToastStack.vue';
import { formatCurrency, roundCurrency } from '../utils/formatters';
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
const heldSales = ref([]);
const lastSaleId = ref(null);
const lastReceiptNumber = ref(null);
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

const cashPresetButtons = [50, 100, 200, 500, 1000, 1500, 2000, 5000];

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
    holdSale,
    voidLastItem,
    cancelAll,
    promptAssignCustomer,
    promptPriceOverride,
    promptQuantity,
    addMiscItem,
    recordDrawerOpen,
    recordCashDrop,
    recordPayout,
    reprintReceipt,
    voidLastSalePrompt,
    openSettings,
});

// ---- Mock catalog (replaced by API search results when available) ----
const categories = [
    { id: 'all', name: 'All' },
    { id: 'essentials', name: 'Essentials/Unga' },
    { id: 'dairy', name: 'Dairy/Bread' },
    { id: 'drinks', name: 'Drinks' },
    { id: 'deli', name: 'Deli/Snacks' },
    { id: 'household', name: 'Household' },
];

const mockCatalog = [
    { id: 'm-001', sku: 'HOT-ESP-1', name: 'Espresso',        base_price: 150, category: 'drinks',    logo_url: '/logos/espresso.svg' },
    { id: 'm-002', sku: 'HOT-CAP-1', name: 'Cappuccino',      base_price: 220, category: 'drinks',    logo_url: '/logos/cappuccino.svg' },
    { id: 'm-003', sku: 'HOT-LAT-1', name: 'Latte',           base_price: 250, category: 'drinks',    logo_url: '/logos/latte.svg' },
    { id: 'm-004', sku: 'HOT-AME-1', name: 'Americano',       base_price: 180, category: 'drinks',    logo_url: '/logos/americano.svg' },
    { id: 'm-005', sku: 'HOT-MOC-1', name: 'Mocha',           base_price: 280, category: 'drinks',    logo_url: '/logos/mocha.svg' },
    { id: 'm-010', sku: 'CLD-CKE-1', name: 'Coca-Cola 500ml', base_price: 80,  category: 'drinks',    logo_url: '/logos/coca-cola.svg' },
    { id: 'm-011', sku: 'CLD-FNT-1', name: 'Fanta 500ml',     base_price: 80,  category: 'drinks',    logo_url: '/logos/fanta.svg' },
    { id: 'm-012', sku: 'CLD-WAT-1', name: 'Water 1L',        base_price: 60,  category: 'drinks',    logo_url: '/logos/water.svg' },
    { id: 'm-013', sku: 'CLD-JUI-1', name: 'Mango juice',     base_price: 120, category: 'drinks',    logo_url: '/logos/juice.svg' },
    { id: 'm-014', sku: 'CLD-MLK-1', name: 'Milk 500ml',      base_price: 70,  category: 'dairy',     logo_url: '/logos/milk.svg' },
    { id: 'm-020', sku: 'PAS-CRO-1', name: 'Croissant',       base_price: 180, category: 'dairy',     logo_url: '/logos/croissant.svg' },
    { id: 'm-021', sku: 'PAS-MUF-1', name: 'Blueberry muffin', base_price: 200, category: 'deli',    logo_url: '/logos/muffin.svg' },
    { id: 'm-022', sku: 'PAS-DON-1', name: 'Glazed donut',    base_price: 150, category: 'deli',      logo_url: '/logos/donut.svg' },
    { id: 'm-023', sku: 'PAS-BRD-1', name: 'White bread',     base_price: 70,  category: 'dairy',     logo_url: '/logos/bread.svg' },
    { id: 'm-030', sku: 'SNK-CRP-1', name: 'Crisps 50g',      base_price: 90,  category: 'deli',      logo_url: '/logos/crisps.svg' },
    { id: 'm-031', sku: 'SNK-NUT-1', name: 'Roasted nuts',    base_price: 220, category: 'deli',      logo_url: '/logos/nuts.svg' },
    { id: 'm-032', sku: 'SNK-CHO-1', name: 'Chocolate bar',   base_price: 130, category: 'deli',      logo_url: '/logos/chocolate.svg' },
    { id: 'm-040', sku: 'HSE-SOAP-1', name: 'Bar soap',       base_price: 95,  category: 'household', logo_url: '/logos/soap.svg' },
    { id: 'm-041', sku: 'HSE-DET-1',  name: 'Detergent 1kg',  base_price: 350, category: 'household', logo_url: '/logos/detergent.svg' },
];

const activeCategoryId = ref('all');

const visibleProducts = computed(() => {
    const fromSearch = Array.isArray(searchResults?.value) ? searchResults.value : [];
    const haveSearchResults = fromSearch.length > 0;
    const source = haveSearchResults ? fromSearch : mockCatalog;

    if (activeCategoryId.value === 'all') {
        return source;
    }

    return source.filter((product) => categoryKey(product) === activeCategoryId.value);
});

const resultCountLabel = computed(() => {
    const count = visibleProducts.value.length;
    return `${count} item${count === 1 ? '' : 's'}`;
});

const canCheckout = computed(() => cart.value.length > 0 && !checkoutBusy.value);

function categoryKey(product) {
    const category = product?.category?.slug ?? product?.category ?? '';
    const value = `${category} ${product?.name ?? ''} ${product?.sku ?? ''}`.toLowerCase();

    if (value.includes('unga') || value.includes('flour') || value.includes('sugar') || value.includes('rice') || value.includes('beans') || value.includes('essentials') || value.includes('staples')) {
        return 'essentials';
    }

    if (value.includes('milk') || value.includes('bread') || value.includes('dairy') || value.includes('mala') || value.includes('yoghurt')) {
        return 'dairy';
    }

    if (value.includes('drink') || value.includes('water') || value.includes('juice') || value.includes('soda') || value.includes('coke') || value.includes('fanta') || value.includes('coffee') || value.includes('tea')) {
        return 'drinks';
    }

    if (value.includes('snack') || value.includes('deli') || value.includes('crisps') || value.includes('biscuit') || value.includes('muffin') || value.includes('donut') || value.includes('nuts')) {
        return 'deli';
    }

    if (value.includes('household') || value.includes('soap') || value.includes('detergent') || value.includes('tissue') || value.includes('cleaner')) {
        return 'household';
    }

    return product?.category && categories.some((item) => item.id === product.category) ? product.category : 'all';
}

function formatKsh(value) {
    return `Ksh ${new Intl.NumberFormat('en-KE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0))}`;
}

function onLogoError(event) {
    const target = event?.target;
    if (!target) return;
    target.classList.add('hidden');
    const fallback = target.parentElement?.querySelector('.logo-fallback');
    if (fallback) {
        fallback.classList.remove('hidden');
    }
}

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

    window.addEventListener('pos:unauthenticated', handleUnauthenticated);

    if (currentUser.value) {
        ensureOpenShift();
    } else {
        focusPinInput();
    }
});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
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
        await refreshSharedAuth();
        await ensureOpenShift();
        focusPriorityInput();
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Unable to unlock the register.';
        toast('Access denied', message, 'error');
        focusPinInput();
    } finally {
        pinBusy.value = false;
    }
}

async function refreshSharedAuth() {
    try {
        const response = await posApi.fetchMe();
        if (response.data?.csrf_token) {
            updateCsrfToken(response.data.csrf_token);
        }
        if (!response.data?.authenticated || response.data?.user?.role !== 'cashier') {
            currentUser.value = null;
            showPinOverlay.value = true;
        }
    } catch (error) {
        // /api/auth/me is best-effort; failure should not crash the terminal.
    }

    try {
        await router.reload({ only: ['auth', 'csrfToken'], preserveScroll: true, preserveState: true });
    } catch (error) {
        // Inertia reload is also best-effort.
    }
}

async function logout() {
    stopStkPolling();

    try {
        const response = await posApi.logout();
        if (response?.data?.csrf_token) {
            updateCsrfToken(response.data.csrf_token);
        }
    } catch (error) {
        // Keep the terminal lock-first even if logout request fails.
    }

    currentUser.value = null;
    showPinOverlay.value = true;
    showSearchModal.value = false;
    showPayModal.value = false;
    newSale(false);
    window.location.assign('/');
}

async function ensureOpenShift() {
    try {
        await posApi.openShift(0);
    } catch (error) {
        const message = error?.response?.data?.message ?? '';

        if (message !== 'An open shift already exists for this user.') {
            toast('Shift not opened', message || 'Unable to open a cashier shift.', 'error');
        }
    }
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

        lastSaleId.value = response.data.sale?.id ?? response.data.sale_id ?? null;
        lastReceiptNumber.value = response.data.receipt_number ?? null;

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

function openCreditTender() {
    if (!creditSalesEnabled.value) {
        toast('Credit disabled', 'Credit sales are disabled in settings.', 'error');
        return;
    }
    if (!canCheckout.value) {
        toast('Cart empty', 'Add at least one item before taking payment.', 'error');
        return;
    }
    paymentTab.value = 'credit';
    showPayModal.value = true;
}

function openLiveFeedTender() {
    if (!canCheckout.value) {
        toast('Cart empty', 'Add at least one item before taking payment.', 'error');
        return;
    }
    paymentTab.value = 'live';
    showPayModal.value = true;
    fetchLivePayments();
}

function cancelAll() {
    if (!cart.value.length) {
        return;
    }
    if (!window.confirm('Cancel all items from the current sale?')) {
        return;
    }
    resetCart();
    customerPhone.value = '';
    selectedLivePayment.value = null;
    toast('Sale cleared', 'All items removed from the current sale.', 'info');
    focusScannerInput(true);
}

function holdSale() {
    if (!cart.value.length) {
        return;
    }
    heldSales.value.push({
        id: `H-${Date.now()}`,
        cart: JSON.parse(JSON.stringify(cart.value)),
        customerPhone: customerPhone.value,
        savedAt: new Date().toISOString(),
        total: grandTotal.value,
    });
    resetCart();
    customerPhone.value = '';
    toast('Sale held', `Sale parked. ${heldSales.value.length} held sale(s) waiting.`, 'success');
    focusScannerInput(true);
}

function recallHeldSale() {
    if (!heldSales.value.length) {
        return;
    }
    const recalled = heldSales.value.shift();
    if (cart.value.length && !window.confirm('Replace current cart with the held sale?')) {
        // user cancelled; put it back at front
        heldSales.value.unshift(recalled);
        return;
    }
    resetCart();
    recalled.cart.forEach((line) => cart.value.push({ ...line }));
    customerPhone.value = recalled.customerPhone || '';
    toast('Sale recalled', `Restored sale of ${formatCurrency(recalled.total)}.`, 'success');
    focusScannerInput(true);
}

function promptPriceOverride() {
    const last = cart.value[cart.value.length - 1];
    if (!last) return;
    const input = window.prompt(`Override unit price for ${last.name} (base ${formatCurrency(last.base_price)}):`, String(last.base_price));
    if (input === null) return;
    const next = Number(input);
    if (!Number.isFinite(next) || next <= 0) {
        toast('Invalid price', 'Override price must be a positive number.', 'error');
        return;
    }
    last.discount = Math.max(0, last.base_price - next);
    normalizeDiscount(last);
    toast('Price overridden', `${last.name} now ${formatCurrency(effectiveUnitPrice(last))}/unit.`, 'success');
}

function promptQuantity() {
    const last = cart.value[cart.value.length - 1];
    if (!last) return;
    const input = window.prompt(`Quantity for ${last.name}:`, String(last.quantity));
    if (input === null) return;
    const next = Number(input);
    if (!Number.isFinite(next) || next <= 0) {
        toast('Invalid quantity', 'Quantity must be greater than zero.', 'error');
        return;
    }
    last.quantity = next;
    normalizeQuantity(last);
    toast('Quantity updated', `${last.name} qty set to ${last.quantity}.`, 'success');
}

function addMiscItem() {
    const name = window.prompt('Misc item name:', 'Misc item');
    if (!name) return;
    const priceInput = window.prompt('Misc item price (KES):', '0');
    if (priceInput === null) return;
    const price = Number(priceInput);
    if (!Number.isFinite(price) || price <= 0) {
        toast('Invalid price', 'Misc item price must be a positive number.', 'error');
        return;
    }
    cart.value.push({
        product_id: `MISC-${Date.now()}`,
        name,
        sku: 'MISC',
        quantity: 1,
        base_price: price,
        discount: 0,
        tax_rate: 0,
        allow_fractional_sales: false,
    });
    toast('Misc item added', `${name} added at ${formatCurrency(price)}.`, 'success');
    focusScannerInput(true);
}

function promptAssignCustomer() {
    const input = window.prompt('Customer phone (2547XXXXXXXX):', customerPhone.value || '');
    if (input === null) return;
    customerPhone.value = String(input).trim();
    toast('Customer assigned', customerPhone.value || 'Customer cleared.', 'info');
}

async function recordDrawerOpen() {
    try {
        await posApi.recordCashDrawer({ type: 'pay_in', amount: 0.01, reason: 'Drawer opened (no movement)' });
        toast('Drawer opened', 'Cash drawer event recorded.', 'success');
    } catch (error) {
        toast('Drawer error', error?.response?.data?.message ?? 'Unable to record drawer event.', 'error');
    }
}

async function recordCashDrop() {
    const amountInput = window.prompt('Cash drop amount (KES):', '0');
    if (amountInput === null) return;
    const amount = Number(amountInput);
    if (!Number.isFinite(amount) || amount <= 0) {
        toast('Invalid amount', 'Cash drop amount must be greater than zero.', 'error');
        return;
    }
    const reason = window.prompt('Reason for cash drop:', 'Cash drop to safe') || 'Cash drop';
    try {
        await posApi.recordCashDrawer({ type: 'pay_out', amount, reason });
        toast('Cash drop recorded', `${formatCurrency(amount)} dropped to safe.`, 'success');
    } catch (error) {
        toast('Drawer error', error?.response?.data?.message ?? 'Unable to record cash drop.', 'error');
    }
}

async function recordPayout() {
    const amountInput = window.prompt('Payout amount (KES):', '0');
    if (amountInput === null) return;
    const amount = Number(amountInput);
    if (!Number.isFinite(amount) || amount <= 0) {
        toast('Invalid amount', 'Payout amount must be greater than zero.', 'error');
        return;
    }
    const reason = window.prompt('Reason for payout:', 'Supplier payout') || 'Payout';
    try {
        await posApi.recordCashDrawer({ type: 'pay_out', amount, reason });
        toast('Payout recorded', `${formatCurrency(amount)} paid out.`, 'success');
    } catch (error) {
        toast('Drawer error', error?.response?.data?.message ?? 'Unable to record payout.', 'error');
    }
}

function reprintReceipt() {
    if (!lastReceiptNumber.value) {
        toast('Nothing to reprint', 'Complete a sale first.', 'error');
        return;
    }
    window.dispatchEvent(new CustomEvent('pos:reprint-receipt', {
        detail: { sale_id: lastSaleId.value, receipt_number: lastReceiptNumber.value },
    }));
    toast('Reprint queued', `Receipt ${lastReceiptNumber.value} re-emitted to printer.`, 'success');
}

async function voidLastSalePrompt() {
    if (!lastSaleId.value) {
        toast('Nothing to void', 'No completed sale to void in this session.', 'error');
        return;
    }
    const managerPinInput = window.prompt('Manager PIN required to void the last sale:');
    if (managerPinInput === null) return;
    try {
        await posApi.voidSale({ sale_id: lastSaleId.value, manager_pin: String(managerPinInput).trim() });
        toast('Sale voided', `Sale ${lastReceiptNumber.value} voided. Stock restored.`, 'success');
        lastSaleId.value = null;
        lastReceiptNumber.value = null;
    } catch (error) {
        toast('Void blocked', error?.response?.data?.message ?? 'Unable to void sale.', 'error');
    }
}

function openSettings() {
    toast('Settings', 'Settings are available from the owner dashboard.', 'info');
}

async function tenderExactCash(amount) {
    if (!canCheckout.value) {
        return;
    }
    cashReceived.value = Number(amount);
    paymentTab.value = 'cash';
    if (cashReceived.value < grandTotal.value) {
        toast('Cash shortfall', 'Tendered amount is less than total. Opening payment dialog.', 'error');
        showPayModal.value = true;
        return;
    }
    await submitCashCheckout();
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
