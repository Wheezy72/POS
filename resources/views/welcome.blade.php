<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'POS System') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#0f172a] text-slate-100 antialiased">
        <div class="mx-auto flex min-h-screen max-w-7xl items-center px-6 py-8">
            <main class="w-full">
                <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.24em] text-sky-300">Duka POS</p>
                        <h1 class="mt-2 text-3xl font-medium tracking-tight text-white sm:text-4xl">Choose where you want to work.</h1>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-slate-400">
                        Cashiers use the terminal. Owners use the admin dashboard. Each area unlocks with the right PIN so sessions stay clear.
                    </p>
                </header>

                <section class="grid gap-4 lg:grid-cols-[1.1fr_1fr_0.8fr]">
                    <a
                        href="/pos"
                        class="group rounded-2xl border border-sky-400/50 bg-sky-400/10 p-6 transition hover:bg-sky-400/15"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-sky-200">Cashier mode</p>
                            <span class="rounded border border-sky-400/40 bg-slate-950 px-2 py-1 font-mono text-[10px] text-sky-200">PIN</span>
                        </div>
                        <h2 class="mt-8 text-3xl font-medium tracking-tight text-white">Open terminal</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Fast checkout, barcode search, M-Pesa, cash, card, and receipt actions.</p>
                        <div class="mt-8 flex items-center justify-between border-t border-sky-400/20 pt-4">
                            <span class="text-sm font-medium text-sky-200">Start selling</span>
                            <span class="font-mono text-xs text-sky-300">/pos</span>
                        </div>
                    </a>

                    <a
                        href="/dashboard"
                        class="group rounded-2xl border border-amber-300/50 bg-amber-300/10 p-6 transition hover:bg-amber-300/15"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-amber-200">Owner mode</p>
                            <span class="rounded border border-amber-300/40 bg-slate-950 px-2 py-1 font-mono text-[10px] text-amber-200">Admin PIN</span>
                        </div>
                        <h2 class="mt-8 text-3xl font-medium tracking-tight text-white">Open admin</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Reports, stock pressure, margin signals, products, and cashier management.</p>
                        <div class="mt-8 flex items-center justify-between border-t border-amber-300/20 pt-4">
                            <span class="text-sm font-medium text-amber-200">Manage shop</span>
                            <span class="font-mono text-xs text-amber-200">/dashboard</span>
                        </div>
                    </a>

                    <section class="rounded-2xl border border-slate-700 bg-slate-900/80 p-6">
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-slate-500">Session flow</p>
                        <div class="mt-6 space-y-4 text-sm text-slate-300">
                            <div class="rounded-xl border border-slate-700 bg-slate-950/60 p-4">
                                <p class="font-medium text-slate-100">Switch users cleanly</p>
                                <p class="mt-2 text-slate-400">Logout from POS returns here. Then choose Admin and enter the admin PIN.</p>
                            </div>
                            <div class="rounded-xl border border-slate-700 bg-slate-950/60 p-4">
                                <p class="font-medium text-slate-100">New shop?</p>
                                <p class="mt-2 text-slate-400">Run setup once, then use the terminal and admin dashboard daily.</p>
                            </div>
                        </div>

                        <a
                            href="/setup"
                            class="mt-5 inline-flex w-full items-center justify-center rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm font-medium text-slate-300 transition hover:border-slate-600 hover:bg-slate-800"
                        >
                            Open setup
                        </a>
                    </section>
                </section>
            </main>
        </div>
    </body>
</html>
