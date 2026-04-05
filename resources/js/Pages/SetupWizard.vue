<template>
    <Head title="Store Setup" />

    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div class="mx-auto flex min-h-screen max-w-6xl items-center px-6 py-10">
            <div class="w-full rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.08)]">
                <template v-if="justConfigured">
                    <section class="grid gap-8 p-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:p-10">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Setup complete</p>
                            <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950">Your store is ready.</h1>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                                Choose where you want to go next. The terminal is best for checkout. The admin area is for products, reports, and settings.
                            </p>

                            <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                                <a
                                    href="/pos"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-6 py-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Open terminal
                                </a>
                                <a
                                    href="/admin"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                                >
                                    Open admin
                                </a>
                            </div>
                        </div>

                        <aside class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-6">
                            <p class="text-sm font-semibold text-slate-900">What was saved</p>
                            <ul class="mt-4 space-y-3 text-sm text-slate-600">
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
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Store setup</p>
                                <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950">Choose a business type</h1>
                                <p class="mt-4 text-base leading-7 text-slate-600">
                                    Pick the option that matches this shop. You can change these settings later in the admin area.
                                </p>
                            </div>

                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                <p class="text-sm font-semibold text-slate-900">This controls</p>
                                <ul class="mt-4 space-y-3 text-sm text-slate-600">
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
                                    class="rounded-[1.5rem] border p-5 text-left transition"
                                    :class="selectedProfile === profile.id
                                        ? 'border-slate-950 bg-slate-950 text-white shadow-lg'
                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                    @click="selectedProfile = profile.id"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xl font-semibold">{{ profile.name }}</p>
                                            <p
                                                class="mt-3 text-sm leading-6"
                                                :class="selectedProfile === profile.id ? 'text-slate-300' : 'text-slate-600'"
                                            >
                                                {{ profile.description }}
                                            </p>
                                        </div>

                                        <div
                                            class="mt-1 h-4 w-4 rounded-full border"
                                            :class="selectedProfile === profile.id ? 'border-white bg-white' : 'border-slate-300 bg-transparent'"
                                        />
                                    </div>

                                    <div class="mt-6 space-y-2">
                                        <div
                                            v-for="(value, key) in profile.toggles"
                                            :key="key"
                                            class="flex items-center justify-between rounded-xl px-3 py-2 text-sm"
                                            :class="selectedProfile === profile.id ? 'bg-white/10' : 'bg-slate-100'"
                                        >
                                            <span :class="selectedProfile === profile.id ? 'text-slate-100' : 'text-slate-700'">
                                                {{ labelFor(key) }}
                                            </span>
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="value
                                                    ? (selectedProfile === profile.id ? 'bg-white text-slate-950' : 'bg-emerald-100 text-emerald-700')
                                                    : (selectedProfile === profile.id ? 'bg-slate-700 text-slate-200' : 'bg-slate-200 text-slate-600')"
                                            >
                                                {{ value ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <form class="mt-8 flex flex-col gap-4 border-t border-slate-200 pt-6 md:flex-row md:items-center md:justify-between" @submit.prevent="submit">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Selected business type</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ selectedProfileLabel }}</p>
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-6 py-4 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
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
