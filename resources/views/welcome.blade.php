<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'POS System') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <div class="mx-auto flex min-h-screen max-w-7xl items-center px-6 py-10">
            <main class="grid w-full gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-[0_24px_80px_rgba(15,23,42,0.08)] lg:p-10">
                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        Retail operating system
                    </div>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-950 lg:text-5xl">
                        Built for fast checkout and clear back-office control.
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">
                        The terminal is optimized for cashiers. The admin side is for products, pricing, inventory, payments, and reporting.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="/pos"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-6 py-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Open terminal
                        </a>
                        <a
                            href="/admin"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                        >
                            Open admin
                        </a>
                        <a
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                        >
                            Open analytics
                        </a>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">What you can do here</p>
                        <ul class="mt-5 space-y-4 text-sm text-slate-600">
                            <li>Run fast barcode checkout</li>
                            <li>Manage products and stock</li>
                            <li>Track M-PESA payments</li>
                            <li>Review sales and profit trends</li>
                        </ul>
                    </section>

                    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Recommended flow</p>
                        <div class="mt-5 space-y-3 text-sm text-slate-600">
                            <p>1. Finish setup if this is a new store.</p>
                            <p>2. Load products in the admin area.</p>
                            <p>3. Start selling from the terminal.</p>
                        </div>

                        <a
                            href="/setup"
                            class="mt-6 inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"
                        >
                            Open setup
                        </a>
                    </section>
                </aside>
            </main>
        </div>
    </body>
</html>
