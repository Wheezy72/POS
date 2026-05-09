<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'POS System') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <div class="mx-auto flex min-h-screen max-w-7xl items-center px-6 py-10">
            <main class="grid w-full gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-8 lg:p-10">
                    <div class="inline-flex items-center rounded-full border border-zinc-800 bg-zinc-950 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.22em] text-zinc-500">
                        Retail operating system
                    </div>

                    <h1 class="mt-6 text-4xl font-medium tracking-tight text-zinc-50 lg:text-5xl">
                        Built for fast checkout and clear back-office control.
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-7 text-zinc-400">
                        The terminal is optimized for cashiers. The admin side is for products, pricing, inventory, payments, and reporting.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a
                            href="/pos"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-500/15 border border-emerald-500/40 px-6 py-3.5 text-sm font-medium text-emerald-300 transition hover:bg-emerald-500/25"
                        >
                            Open terminal
                        </a>
                        <a
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-xl border border-zinc-700 bg-zinc-900 px-6 py-3.5 text-sm font-medium text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800"
                        >
                            Open admin
                        </a>
                        <a
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-xl border border-zinc-700 bg-zinc-900 px-6 py-3.5 text-sm font-medium text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800"
                        >
                            Open analytics
                        </a>
                    </div>
                </section>

                <aside class="grid gap-6">
                    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                        <p class="text-sm font-medium text-zinc-100">What you can do here</p>
                        <ul class="mt-5 space-y-3 text-sm text-zinc-400">
                            <li>Run fast barcode checkout</li>
                            <li>Manage products and stock</li>
                            <li>Track M-PESA payments</li>
                            <li>Review sales and profit trends</li>
                        </ul>
                    </section>

                    <section class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6">
                        <p class="text-sm font-medium text-zinc-100">Recommended flow</p>
                        <div class="mt-5 space-y-3 text-sm text-zinc-400">
                            <p>1. Finish setup if this is a new store.</p>
                            <p>2. Load products in the admin area.</p>
                            <p>3. Start selling from the terminal.</p>
                        </div>

                        <a
                            href="/setup"
                            class="mt-6 inline-flex items-center justify-center rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-2.5 text-sm font-medium text-zinc-300 transition hover:border-zinc-700 hover:bg-zinc-900"
                        >
                            Open setup
                        </a>
                    </section>
                </aside>
            </main>
        </div>
    </body>
</html>
