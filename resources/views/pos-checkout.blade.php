<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duka-App POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-900">
    <div
        x-data="posEngine()"
        x-init="init()"
        @keydown.window="handleGlobalKeydown($event)"
        class="h-screen w-screen overflow-hidden"
    >
        <div class="grid h-full w-full grid-cols-10 gap-6 p-6">
            <section class="col-span-7 flex h-full min-h-0 flex-col rounded-3xl border border-slate-200 bg-slate-50">
                <header class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">
                        <div class="flex-1">
                            <label for="product-search" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-slate-500">
                                Barcode / Search
                            </label>
                            <input
                                id="product-search"
                                x-ref="searchInput"
                                x-model.trim="searchTerm"
                                @keydown.enter.prevent="searchProduct()"
                                type="text"
                                placeholder="Scan barcode or search by SKU / name"
                                class="h-16 w-full rounded-2xl border border-slate-300 bg-white px-5 text-xl shadow-sm outline-none transition focus:border-green-500 focus:ring-4 focus:ring-green-100"
                                autocomplete="off"
                            >
                        </div>
                        <button
                            type="button"
                            @click="searchProduct()"
                            class="mt-7 h-16 rounded-2xl bg-slate-900 px-8 text-lg font-semibold text-white shadow-sm transition hover:bg-slate-800"
                        >
                            Add
                        </button>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <p class="text-slate-500">
                            Space focuses search, F2 checks out, F4 clears the cart.
                        </p>
                        <p class="font-medium" :class="errorMessage ? 'text-red-600' : 'text-slate-500'" x-text="errorMessage || statusMessage"></p>
                    </div>
                </header>

                <div class="min-h-0 flex-1 overflow-hidden px-6 py-5">
                    <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="grid grid-cols-12 gap-3 border-b border-slate-200 px-5 py-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                            <div class="col-span-5">Item</div>
                            <div class="col-span-2 text-center">Qty</div>
                            <div class="col-span-2 text-right">Price</div>
                            <div class="col-span-2 text-right">Total</div>
                            <div class="col-span-1 text-right">Remove</div>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <template x-if="cart.length === 0">
                                <div class="flex h-full items-center justify-center px-6 text-center text-lg text-slate-400">
                                    Search for a product or use a quick-add tile to begin a sale.
                                </div>
                            </template>

                            <template x-for="item in cart" :key="item.product_id">
                                <div class="grid grid-cols-12 gap-3 border-b border-slate-100 px-5 py-4 text-base">
                                    <div class="col-span-5 flex flex-col justify-center">
                                        <span class="font-semibold text-slate-900" x-text="item.name"></span>
                                        <span class="text-sm text-slate-500" x-text="item.sku || 'Manual entry'"></span>
                                    </div>

                                    <div class="col-span-2 flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click="decrementQty(item.product_id)"
                                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            −
                                        </button>
                                        <span class="min-w-12 text-center text-lg font-semibold" x-text="formatQuantity(item.quantity)"></span>
                                        <button
                                            type="button"
                                            @click="incrementQty(item.product_id)"
                                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div class="col-span-2 flex items-center justify-end font-medium text-slate-700" x-text="formatCurrency(item.unit_price)"></div>
                                    <div class="col-span-2 flex items-center justify-end font-semibold text-slate-900" x-text="formatCurrency(item.quantity * item.unit_price)"></div>

                                    <div class="col-span-1 flex items-center justify-end">
                                        <button
                                            type="button"
                                            @click="removeItem(item.product_id)"
                                            class="rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-100"
                                        >
                                            X
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <footer class="border-t border-slate-200 bg-white px-6 py-5 shadow-sm">
                    <div class="grid gap-5 lg:grid-cols-[1fr_auto]">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-lg text-slate-600">
                                <span>Subtotal</span>
                                <span class="font-semibold" x-text="formatCurrency(subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between text-lg text-slate-600">
                                <span>Tax (16%)</span>
                                <span class="font-semibold" x-text="formatCurrency(tax)"></span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl bg-slate-900 px-5 py-4 text-white shadow-sm">
                                <span class="text-xl font-semibold uppercase tracking-wide">Grand Total</span>
                                <span class="text-4xl font-black tracking-tight" x-text="formatCurrency(grandTotal)"></span>
                            </div>
                        </div>

                        <div class="flex flex-col justify-end gap-3 lg:w-72">
                            <button
                                type="button"
                                @click="checkout()"
                                :disabled="isBusy || cart.length === 0"
                                class="flex h-24 items-center justify-center rounded-3xl bg-green-600 px-6 text-3xl font-black uppercase tracking-wide text-white shadow-lg transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-green-300"
                            >
                                <span x-text="isBusy ? 'Processing...' : 'Pay'"></span>
                            </button>
                            <button
                                type="button"
                                @click="clearCart()"
                                class="flex h-16 items-center justify-center rounded-2xl bg-red-600 px-6 text-xl font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-red-700"
                            >
                                Void
                            </button>
                        </div>
                    </div>
                </footer>
            </section>

            <aside class="col-span-3 flex h-full min-h-0 flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-lg font-bold uppercase tracking-wide text-slate-900">Quick Add</h2>
                    <p class="mt-1 text-sm text-slate-500">Tap a common item to search and add it instantly.</p>
                </div>

                <div class="grid min-h-0 flex-1 grid-cols-2 gap-4 overflow-y-auto">
                    <template x-for="item in quickAddItems" :key="item">
                        <button
                            type="button"
                            @click="quickAdd(item)"
                            class="flex min-h-28 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center text-lg font-semibold text-slate-700 shadow-sm transition hover:border-green-400 hover:bg-green-50 hover:text-green-700"
                            x-text="item"
                        ></button>
                    </template>
                </div>
            </aside>
        </div>
    </div>

    <script>
        function posEngine() {
            return {
                searchTerm: '',
                cart: [],
                isBusy: false,
                errorMessage: '',
                statusMessage: 'Ready for checkout.',
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

                init() {
                    this.$nextTick(() => {
                        this.$refs.searchInput.focus();
                    });
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
                },

                get tax() {
                    return this.cart.reduce((sum, item) => {
                        return sum + ((item.quantity * item.unit_price) * ((item.tax_rate ?? 16) / 100));
                    }, 0);
                },

                get grandTotal() {
                    return this.subtotal + this.tax;
                },

                handleGlobalKeydown(event) {
                    if (event.key === 'F2') {
                        event.preventDefault();
                        this.checkout();
                        return;
                    }

                    if (event.key === 'F4') {
                        event.preventDefault();
                        this.clearCart();
                        return;
                    }

                    if (event.code === 'Space' && !this.shouldIgnoreSpaceShortcut(event.target)) {
                        event.preventDefault();
                        this.$refs.searchInput.focus();
                        this.$refs.searchInput.select();
                    }
                },

                shouldIgnoreSpaceShortcut(target) {
                    if (!target) {
                        return false;
                    }

                    const tagName = target.tagName ? target.tagName.toLowerCase() : '';

                    return tagName === 'input' || tagName === 'textarea' || target.isContentEditable;
                },

                async searchProduct(forcedQuery = null) {
                    const query = (forcedQuery ?? this.searchTerm).trim();

                    if (!query || this.isBusy) {
                        return;
                    }

                    this.errorMessage = '';
                    this.statusMessage = 'Searching products...';

                    try {
                        const response = await fetch('/api/pos/search', {
                            method: 'POST',
                            headers: this.requestHeaders(),
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
                        this.statusMessage = `${payload[0].name} added to cart.`;
                        this.$nextTick(() => this.$refs.searchInput.focus());
                    } catch (error) {
                        this.errorMessage = error.message || 'Product search failed.';
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
                        unit_price: Number(product.base_price),
                        tax_rate: Number(product.tax_category?.rate ?? 16),
                    });
                },

                incrementQty(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);

                    if (!item) {
                        return;
                    }

                    item.quantity = this.roundMoney(item.quantity + 1);
                },

                decrementQty(productId) {
                    const item = this.cart.find((entry) => entry.product_id === productId);

                    if (!item) {
                        return;
                    }

                    if (item.quantity <= 1) {
                        this.removeItem(productId);
                        return;
                    }

                    item.quantity = this.roundMoney(item.quantity - 1);
                },

                removeItem(productId) {
                    this.cart = this.cart.filter((item) => item.product_id !== productId);

                    if (this.cart.length === 0) {
                        this.statusMessage = 'Cart cleared.';
                    }
                },

                clearCart() {
                    this.cart = [];
                    this.errorMessage = '';
                    this.statusMessage = 'Cart cleared.';
                    this.$nextTick(() => this.$refs.searchInput.focus());
                },

                async checkout() {
                    if (this.cart.length === 0 || this.isBusy) {
                        return;
                    }

                    this.isBusy = true;
                    this.errorMessage = '';
                    this.statusMessage = 'Submitting checkout...';

                    try {
                        const payload = {
                            cart: this.cart.map((item) => ({
                                product_id: item.product_id,
                                quantity: this.roundMoney(item.quantity),
                            })),
                            payments: [
                                {
                                    method: 'cash',
                                    amount: this.roundMoney(this.grandTotal),
                                    status: 'completed',
                                },
                            ],
                        };

                        const response = await fetch('/api/pos/checkout', {
                            method: 'POST',
                            headers: this.requestHeaders(),
                            body: JSON.stringify(payload),
                            credentials: 'same-origin',
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Checkout failed.');
                        }

                        alert(`Payment successful. Receipt: ${data.receipt_number}`);
                        this.clearCart();
                        this.statusMessage = 'Checkout completed successfully.';
                    } catch (error) {
                        this.errorMessage = error.message || 'Checkout failed.';
                    } finally {
                        this.isBusy = false;
                    }
                },

                requestHeaders() {
                    return {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    };
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-KE', {
                        style: 'currency',
                        currency: 'KES',
                    }).format(this.roundMoney(value));
                },

                formatQuantity(value) {
                    return Number.isInteger(value) ? String(value) : this.roundMoney(value).toFixed(2);
                },

                roundMoney(value) {
                    return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
                },
            };
        }
    </script>
</body>
</html>
