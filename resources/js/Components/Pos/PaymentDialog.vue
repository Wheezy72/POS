<template>
    <div v-if="show" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="payment-dialog-title">
        <div class="w-full max-w-5xl rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <BanknotesIcon class="h-5 w-5" />
                    </span>
                    <div>
                        <p id="payment-dialog-title" class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Tender sale</p>
                        <p class="mt-1 text-xs text-slate-500">Choose one payment path. Buttons lock while checkout is processing.</p>
                    </div>
                </div>
                <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold hover:bg-slate-50" :disabled="checkoutBusy" @click="$emit('close')">Esc</button>
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section>
                    <div class="flex flex-wrap gap-2">
                        <button class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTabModel === 'cash' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'cash'">
                            <BanknotesIcon class="h-4 w-4" />
                            Cash
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTabModel === 'stk' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'stk'">
                            <DevicePhoneMobileIcon class="h-4 w-4" />
                            M-PESA STK
                        </button>
                        <button class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTabModel === 'live' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'live'">
                            <SignalIcon class="h-4 w-4" />
                            C2B live feed
                        </button>
                        <button v-if="creditSalesEnabled" class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold" :class="paymentTabModel === 'credit' ? activeTabClass : inactiveTabClass" :disabled="checkoutBusy" @click="paymentTabModel = 'credit'">
                            <ClockIcon class="h-4 w-4" />
                            [F7] Pay later
                        </button>
                    </div>

                    <div v-if="paymentTabModel === 'cash'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Cash received</label>
                        <input ref="cashInput" v-model.number="cashReceivedModel" type="number" min="0" step="0.01" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-xl font-black outline-none focus:border-emerald-500">
                        <div class="grid gap-2 sm:grid-cols-4">
                            <button v-for="preset in cashPresets" :key="preset" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-bold hover:bg-slate-100 disabled:opacity-50" :disabled="checkoutBusy" @click="cashReceivedModel = preset">
                                {{ formatCurrency(preset) }}
                            </button>
                        </div>
                        <p class="text-sm" :class="cashChange >= 0 ? 'text-emerald-600' : 'text-red-600'">
                            Change: {{ formatCurrency(cashChange) }}
                        </p>
                        <button class="inline-flex items-center justify-between rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="$emit('cash-checkout')">
                            <span>{{ checkoutBusy ? 'Processing…' : 'Complete cash sale' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-5 w-5 animate-spin" />
                            <CheckCircleIcon v-else class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-else-if="paymentTabModel === 'stk'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Customer phone</label>
                        <input v-model.trim="stkPhoneModel" type="text" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none focus:border-emerald-500" placeholder="2547XXXXXXXX">
                        <button class="inline-flex items-center justify-between rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0" @click="$emit('stk-checkout')">
                            <span>{{ checkoutBusy ? 'Sending STK…' : 'Start STK and checkout' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-5 w-5 animate-spin" />
                            <DevicePhoneMobileIcon v-else class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-else-if="paymentTabModel === 'live'" class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <div class="max-h-64 overflow-auto rounded-2xl border border-slate-200 bg-white">
                            <div v-if="livePayments.length === 0" class="px-4 py-6 text-sm text-slate-500">No pending M-PESA deposits.</div>
                            <button
                                v-for="payment in livePayments"
                                :key="payment.id"
                                class="w-full border-b border-slate-200 px-3 py-2 text-left hover:bg-slate-50 disabled:opacity-50"
                                :class="selectedLivePayment?.id === payment.id ? 'bg-emerald-50' : ''"
                                :disabled="checkoutBusy"
                                @click="$emit('select-live-payment', payment)"
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
                        <button class="inline-flex items-center justify-between rounded-2xl border border-emerald-600 bg-emerald-600 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || !selectedLivePayment || cart.length === 0" @click="$emit('live-feed-checkout')">
                            <span>{{ checkoutBusy ? 'Claiming…' : 'Claim inbound payment and checkout' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-5 w-5 animate-spin" />
                            <SignalIcon v-else class="h-5 w-5" />
                        </button>
                    </div>

                    <div v-else class="mt-4 grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <label class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Customer phone</label>
                        <input v-model.trim="customerPhoneModel" type="text" class="h-12 rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none focus:border-yellow-500" placeholder="2547XXXXXXXX">
                        <button class="inline-flex items-center justify-between rounded-2xl border border-yellow-300 bg-yellow-300 px-4 py-3 text-left font-black uppercase tracking-[0.18em] text-slate-950 hover:bg-yellow-200 disabled:cursor-not-allowed disabled:opacity-50" :disabled="checkoutBusy || cart.length === 0 || !customerPhone" @click="$emit('credit-checkout')">
                            <span>{{ checkoutBusy ? 'Saving…' : 'Save as credit sale' }}</span>
                            <ArrowPathIcon v-if="checkoutBusy" class="h-5 w-5 animate-spin" />
                            <ClockIcon v-else class="h-5 w-5" />
                        </button>
                    </div>
                </section>

                <aside class="grid gap-3">
                    <section class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Manager PIN</p>
                        <input v-model.trim="managerPinModel" type="password" maxlength="6" class="mt-3 h-12 w-full rounded-2xl border border-slate-300 bg-white px-3 text-lg font-bold outline-none focus:border-yellow-500" placeholder="Only needed below margin floor">
                    </section>

                    <section class="rounded-3xl border border-yellow-300 bg-yellow-300 p-4 text-slate-950 shadow-sm">
                        <p class="text-sm font-black uppercase tracking-[0.18em]">Summary</p>
                        <div class="mt-4 grid grid-cols-[1fr_auto] gap-y-2">
                            <span class="font-bold uppercase tracking-[0.16em]">Subtotal</span>
                            <span class="text-right font-black">{{ formatCurrency(subtotal) }}</span>
                            <span class="font-bold uppercase tracking-[0.16em]">Tax</span>
                            <span class="text-right font-black">{{ formatCurrency(tax) }}</span>
                            <span class="border-t border-slate-950 pt-2 text-lg font-black uppercase tracking-[0.18em]">Grand total</span>
                            <span class="border-t border-slate-950 pt-2 text-right text-4xl font-black">{{ formatCurrency(grandTotal) }}</span>
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
    ArrowPathIcon,
    BanknotesIcon,
    CheckCircleIcon,
    ClockIcon,
    DevicePhoneMobileIcon,
    SignalIcon,
} from '@heroicons/vue/24/outline';

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

const activeTabClass = 'border-yellow-300 bg-yellow-300 text-slate-950';
const inactiveTabClass = 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-50';

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
