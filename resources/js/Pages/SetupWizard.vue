<template>
    <Head title="Store Setup" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="mx-auto flex min-h-screen max-w-6xl items-center px-6 py-10">
            <div class="w-full rounded-2xl border border-zinc-800 bg-zinc-900">
                <template v-if="justConfigured">
                    <section class="grid gap-8 p-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:p-10">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-emerald-400">Setup complete</p>
                            <h1 class="mt-3 text-4xl font-medium tracking-tight text-zinc-50">Your store is ready.</h1>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-400">
                                Choose where you want to go next. The terminal is best for checkout. The admin area is for products, reports, and settings.
                            </p>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="/pos"
                                    class="inline-flex items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-6 py-3.5 text-sm font-medium text-emerald-300 transition hover:bg-emerald-500/25"
                                >
                                    Open terminal
                                </a>
                                <a
                                    href="/admin"
                                    class="inline-flex items-center justify-center rounded-xl border border-zinc-700 bg-zinc-900 px-6 py-3.5 text-sm font-medium text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800"
                                >
                                    Open admin
                                </a>
                            </div>
                        </div>

                        <aside class="rounded-2xl border border-zinc-800 bg-zinc-950 p-6">
                            <p class="text-sm font-medium text-zinc-100">What was saved</p>
                            <ul class="mt-4 space-y-2 text-sm text-zinc-400">
                                <li>Business profile</li>
                                <li>Credit sales setting</li>
                                <li>Wholesale setting</li>
                                <li>Sales hours lock</li>
                            </ul>
                        </aside>
                    </section>
                </template>

                <template v-else>
                    <section class="grid gap-10 p-8 lg:grid-cols-[20rem_minmax(0,1fr)] lg:p-10">
                        <div class="space-y-6">
                            <div>
                                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-zinc-500">Store setup</p>
                                <h1 class="mt-3 text-4xl font-medium tracking-tight text-zinc-50">Choose a business type</h1>
                                <p class="mt-4 text-base leading-7 text-zinc-400">
                                    Pick the option that matches this shop. You can change these settings later in the admin area.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-zinc-800 bg-zinc-950 p-5">
                                <p class="text-sm font-medium text-zinc-100">This controls</p>
                                <ul class="mt-4 space-y-2 text-sm text-zinc-400">
                                    <li>Fractional quantity sales</li>
                                    <li>Credit sales</li>
                                    <li>Wholesale pricing</li>
                                    <li>Sales hours lock</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <div class="grid gap-4 xl:grid-cols-3">
                                <button
                                    v-for="profile in profiles"
                                    :key="profile.id"
                                    type="button"
                                    class="rounded-2xl border p-5 text-left transition focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                                    :class="selectedProfile === profile.id
                                        ? 'border-emerald-500/60 bg-emerald-500/10 text-zinc-50'
                                        : 'border-zinc-800 bg-zinc-950 text-zinc-200 hover:border-zinc-700 hover:bg-zinc-900'"
                                    @click="selectedProfile = profile.id"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-lg font-medium">{{ profile.name }}</p>
                                            <p
                                                class="mt-3 text-sm leading-6"
                                                :class="selectedProfile === profile.id ? 'text-zinc-300' : 'text-zinc-400'"
                                            >
                                                {{ profile.description }}
                                            </p>
                                        </div>

                                        <div
                                            class="mt-1 h-4 w-4 rounded-full border"
                                            :class="selectedProfile === profile.id ? 'border-emerald-400 bg-emerald-400' : 'border-zinc-700 bg-transparent'"
                                        />
                                    </div>

                                    <div class="mt-6 space-y-2">
                                        <div
                                            v-for="(value, key) in profile.toggles"
                                            :key="key"
                                            class="flex items-center justify-between rounded-lg px-3 py-2 text-sm"
                                            :class="selectedProfile === profile.id ? 'bg-emerald-500/10' : 'bg-zinc-900'"
                                        >
                                            <span class="text-zinc-300">{{ labelFor(key) }}</span>
                                            <span
                                                class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                                                :class="value
                                                    ? 'bg-emerald-500/20 text-emerald-300'
                                                    : 'bg-zinc-800 text-zinc-500'"
                                            >
                                                {{ value ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <form class="mt-8 flex flex-col gap-4 border-t border-zinc-800 pt-6 md:flex-row md:items-center md:justify-between" @submit.prevent="submit">
                                <div>
                                    <p class="text-sm font-medium text-zinc-100">Selected business type</p>
                                    <p class="mt-1 text-sm text-zinc-400">{{ selectedProfileLabel }}</p>
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl border border-emerald-500/40 bg-emerald-500/15 px-6 py-3.5 text-sm font-medium text-emerald-300 transition hover:bg-emerald-500/25 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="form.processing || !selectedProfile"
                                >
                                    {{ form.processing ? 'Saving…' : 'Save and continue' }}
                                </button>
                            </form>
                        </div>
                    </section>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    justConfigured: {
        type: Boolean,
        default: false,
    },
    profiles: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    business_type: props.profiles[0]?.id ?? '',
});

const selectedProfile = computed({
    get: () => form.business_type,
    set: (value) => {
        form.business_type = value;
    },
});

const selectedProfileLabel = computed(() => {
    const match = props.profiles.find((profile) => profile.id === selectedProfile.value);

    return match ? match.name : 'Nothing selected yet.';
});

watch(
    () => props.profiles,
    (profiles) => {
        if (!form.business_type && profiles[0]) {
            form.business_type = profiles[0].id;
        }
    },
    { immediate: true },
);

function submit() {
    form.post('/setup');
}

function labelFor(key) {
    const labels = {
        enable_fractional_stock: 'Fractional quantity',
        enable_credit_sales: 'Credit sales',
        enable_wholesale: 'Wholesale pricing',
        enable_sales_hours_lock: 'Sales hours lock',
    };

    return labels[key] ?? key.replace('enable_', '').replaceAll('_', ' ');
}
</script>
