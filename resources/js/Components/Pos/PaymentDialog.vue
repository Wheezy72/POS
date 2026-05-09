<template>
    <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-zinc-950/85 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="payment-dialog-title">
        <div class="w-full max-w-5xl rounded-2xl border border-zinc-800 bg-zinc-900 shadow-xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-zinc-800 px-5 py-3.5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-emerald-300">
                        <BanknotesIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p id="payment-dialog-title" class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Tender sale</p>
                        <p class="mt-0.5 text-xs text-zinc-400">Choose one payment path. Buttons lock while checkout is processing.</p>
                    </div>
                </div>
                <button class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-800 disabled:opacity-50" :disabled="checkoutBusy" @click="$emit('close')">Esc</button>
            </div>

            <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section>
                    <div class="flex flex-wrap gap-2">
                        <button class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition" :class="paymentTabModel === 'cash' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'cash'">
                            <span class="rounded-md bg-blue-500/20 px-1.5 py-0.5 text-[10px] font-medium text-blue-300">KES</span>
                            Cash
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition" :class="paymentTabModel === 'stk' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'stk'">
                            <span class="rounded-md bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-medium text-emerald-300">STK</span>
                            M-PESA push
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition" :class="paymentTabModel === 'live' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'live'">
                            <span class="rounded-md bg-emerald-500/20 px-1.5 py-0.5 text-[10px] font-medium text-emerald-300">C2B</span>
                            Live feed
                        </button>
                        <button v-if="creditSalesEnabled" class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-medium transition" :class="paymentTabModel === 'credit' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'credit'">
                            <span class="rounded-md bg-amber-500/20 px-1.5 py-0.5 text-[10px] font-medium text-amber-300">IOU</span>
                            Credit · F7
                        </button>
                    </div>

                    <div v-if="paymentTabModel === 'cash'" class="mt-4 grid gap-3 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <label class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Cash received</label>
                        <input ref="cashInput" v-model.number="cashReceivedModel" type="number" min="0" step="0.01" class="h-11 rounded-lg border border-zinc-800 bg-zinc-900 px-3 text-base font-medium text-zinc-100 outline-none tabular-nums focus:border-blue-500/60 focus:ring-2 focus:ring-blue-500/30">
                        <div class="grid gap-2 sm:grid-cols-4">
                            <button v-for="preset in cashPresets" :key="preset" class="rounded-lg border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm font-medium text-zinc-200 transition hover:bg-zinc-800 disabled:opacity-50 tabular-nums" :disabled="checkoutBusy" @click="cashReceivedModel = preset">
                                {{ formatCurrency(preset) }}
                            </button>
                        </div>
                        <p class="text-sm" :class="cashChange >= 0 ? 'text-emerald-300' : 'text-rose-300'">
                            Change: <span class="tabular-nums">{{ formatCurrency(cashChange) }}</span>
                        </p>
                        <button class="flex items-center justify-between rounded-xl border border-blue-500/40 bg-blue-500/15 px-4 py-3 text-sm font-medium uppercase tracking-[0.18em] text-blue-300 transition hover:bg-blue-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="$emit('cash-checkout')">
                            <span>{{ checkoutBusy ? 'Processing…' : 'Complete cash sale' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-4 w-4 animate-spin" />
                            <CheckCircleIcon v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-else-if="paymentTabModel === 'stk'" class="mt-4 grid gap-3 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <label class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Customer phone</label>
                        <input v-model.trim="stkPhoneModel" type="text" class="h-11 rounded-lg border border-zinc-800 bg-zinc-900 px-3 text-base font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-emerald-500/60 focus:ring-2 focus:ring-emerald-500/30 tabular-nums" placeholder="2547XXXXXXXX">
                        <button class="flex items-center justify-between rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-sm font-medium uppercase tracking-[0.18em] text-emerald-300 transition hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="$emit('stk-checkout')">
                            <span>{{ checkoutBusy ? 'Sending STK…' : 'Start STK and checkout' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-4 w-4 animate-spin" />
                            <DevicePhoneMobileIcon v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-else-if="paymentTabModel === 'live'" class="mt-4 grid gap-3 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <div class="max-h-64 overflow-auto rounded-lg border border-zinc-800 bg-zinc-900">
                            <div v-if="livePayments.length === 0" class="px-4 py-6 text-sm text-zinc-500">No pending M-PESA deposits.</div>
                            <button
                                v-for="payment in livePayments"
                                :key="payment.id"
                                class="w-full border-b border-zinc-800 px-3 py-2.5 text-left transition hover:bg-zinc-800/60 disabled:opacity-50"
                                :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-500/10' : ''"
                                :disabled="checkoutBusy"
                                @click="$emit('select-live-payment', payment)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-100">{{ payment.customer_name }}</p>
                                        <p class="truncate text-[11px] uppercase tracking-[0.18em] text-zinc-500">{{ payment.transaction_code }}</p>
                                    </div>
                                    <p class="text-sm font-medium text-emerald-300 tabular-nums">{{ formatCurrency(Number(payment.amount)) }}</p>
                                </div>
                            </button>
                        </div>
                        <button class="flex items-center justify-between rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-4 py-3 text-sm font-medium uppercase tracking-[0.18em] text-emerald-300 transition hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || !selectedLivePayment || cart.length === 0" @click="$emit('live-feed-checkout')">
                            <span>{{ checkoutBusy ? 'Claiming…' : 'Claim inbound payment and checkout' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-4 w-4 animate-spin" />
                            <SignalIcon v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-else class="mt-4 grid gap-3 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <label class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Customer phone</label>
                        <input v-model.trim="customerPhoneModel" type="text" class="h-11 rounded-lg border border-zinc-800 bg-zinc-900 px-3 text-base font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-amber-500/60 focus:ring-2 focus:ring-amber-500/30 tabular-nums" placeholder="2547XXXXXXXX">
                        <button class="flex items-center justify-between rounded-xl border border-amber-500/40 bg-amber-500/15 px-4 py-3 text-sm font-medium uppercase tracking-[0.18em] text-amber-300 transition hover:bg-amber-500/25 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0 || !customerPhone" @click="$emit('credit-checkout')">
                            <span>{{ checkoutBusy ? 'Saving…' : 'Save as credit sale' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-4 w-4 animate-spin" />
                            <ClockIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                </section>

                <aside class="grid gap-3 self-start">
                    <section class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Manager PIN</p>
                        <input v-model.trim="managerPinModel" type="password" maxlength="6" class="mt-3 h-11 w-full rounded-lg border border-zinc-800 bg-zinc-900 px-3 text-base font-medium text-zinc-100 outline-none placeholder:text-zinc-600 focus:border-amber-500/60 focus:ring-2 focus:ring-amber-500/30" placeholder="Only needed below margin floor">
                    </section>

                    <section class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                        <p class="text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Summary</p>
                        <div class="mt-3 grid grid-cols-[1fr_auto] gap-y-2 text-sm">
                            <span class="text-zinc-400">Subtotal</span>
                            <span class="text-right text-zinc-200 tabular-nums">{{ formatCurrency(subtotal) }}</span>
                            <span class="text-zinc-400">Tax</span>
                            <span class="text-right text-zinc-200 tabular-nums">{{ formatCurrency(tax) }}</span>
                            <span class="border-t border-zinc-800 pt-3 text-[10px] font-medium uppercase tracking-[0.22em] text-zinc-500">Grand total</span>
                            <span class="border-t border-zinc-800 pt-3 text-right text-2xl font-medium text-zinc-50 tabular-nums">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import {
    Banknote as BanknotesIcon,
    CheckCircle as CheckCircleIcon,
    Clock as ClockIcon,
    RefreshCw as ArrowPathIcon,
    Signal as SignalIcon,
    Smartphone as DevicePhoneMobileIcon,
} from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    paymentTab: {
        type: String,
        required: true,
    },
    creditSalesEnabled: {
        type: Boolean,
        required: true,
    },
    cart: {
        type: Array,
        required: true,
    },
    cashReceived: {
        type: Number,
        required: true,
    },
    cashPresets: {
        type: Array,
        required: true,
    },
    cashChange: {
        type: Number,
        required: true,
    },
    stkPhone: {
        type: String,
        required: true,
    },
    livePayments: {
        type: Array,
        required: true,
    },
    selectedLivePayment: {
        type: Object,
        default: null,
    },
    customerPhone: {
        type: String,
        required: true,
    },
    managerPin: {
        type: String,
        required: true,
    },
    checkoutBusy: {
        type: Boolean,
        required: true,
    },
    subtotal: {
        type: Number,
        required: true,
    },
    tax: {
        type: Number,
        required: true,
    },
    grandTotal: {
        type: Number,
        required: true,
    },
    formatCurrency: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits([
    'cash-checkout',
    'close',
    'credit-checkout',
    'live-feed-checkout',
    'select-live-payment',
    'stk-checkout',
    'update:cash-received',
    'update:customer-phone',
    'update:manager-pin',
    'update:payment-tab',
    'update:stk-phone',
]);

const activeTabClass = 'border-zinc-100 bg-zinc-100 text-zinc-950';
const inactiveTabClass = 'border-zinc-800 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 disabled:opacity-50';

const cashInput = ref(null);

const paymentTabModel = computed({
    get: () => props.paymentTab,
    set: (value) => emit('update:payment-tab', value),
});

const cashReceivedModel = computed({
    get: () => props.cashReceived,
    set: (value) => emit('update:cash-received', Number(value || 0)),
});

const stkPhoneModel = computed({
    get: () => props.stkPhone,
    set: (value) => emit('update:stk-phone', String(value ?? '').trim()),
});

const customerPhoneModel = computed({
    get: () => props.customerPhone,
    set: (value) => emit('update:customer-phone', String(value ?? '').trim()),
});

const managerPinModel = computed({
    get: () => props.managerPin,
    set: (value) => emit('update:manager-pin', String(value ?? '').trim()),
});

defineExpose({
    focus() {
        cashInput.value?.focus();
    },
});
</script>
