<template>
    <Head title="Duka-App Setup Wizard" />

    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(250,204,21,0.18),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.22),_transparent_28%)]" />

        <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-6 py-12">
            <div class="grid w-full gap-8 lg:grid-cols-[24rem_minmax(0,1fr)]">
                <section class="rounded-[2rem] border border-white/15 bg-white/10 p-8 shadow-2xl backdrop-blur-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.35em] text-amber-300">Turnkey Profiling Engine</p>
                    <h1 class="mt-4 text-4xl font-black tracking-tight text-white">First-Boot Setup Wizard</h1>
                    <p class="mt-4 text-sm leading-6 text-slate-200">
                        Choose the business profile that matches this shop. Duka-App will apply the required retail controls
                        and then unlock the admin command center.
                    </p>

                    <div class="mt-8 space-y-4 text-sm text-slate-200">
                        <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-4">
                            <p class="font-bold text-white">What this configures</p>
                            <ul class="mt-3 space-y-2 text-slate-300">
                                <li>Fractional stock behavior</li>
                                <li>Credit-sales availability</li>
                                <li>Wholesale readiness</li>
                                <li>Mututho compliance lock</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-4 text-red-100">
                            <p class="font-bold uppercase tracking-[0.2em]">Important</p>
                            <p class="mt-2 text-sm">
                                Until this profile is selected, both the POS terminal and the Filament admin panel stay locked behind setup.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-white/15 bg-white/10 p-8 shadow-2xl backdrop-blur-2xl">
                    <div class="flex items-end justify-between gap-4 border-b border-white/10 pb-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.35em] text-sky-300">Business Profile Matrix</p>
                            <h2 class="mt-3 text-3xl font-black text-white">Select your operating model</h2>
                        </div>
                        <p class="text-sm text-slate-300">Dark mode · glassmorphism · first boot only</p>
                    </div>

                    <div class="mt-8 grid gap-5 xl:grid-cols-3">
                        <button
                            v-for="profile in profiles"
                            :key="profile.id"
                            type="button"
                            class="group rounded-[1.75rem] border p-6 text-left transition"
                            :class="selectedProfile === profile.id
                                ? 'border-amber-300 bg-amber-300/15 shadow-[0_0_0_1px_rgba(252,211,77,0.45)]'
                                : 'border-white/10 bg-black/20 hover:border-sky-300/40 hover:bg-white/10'"
                            @click="selectedProfile = profile.id"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-2xl font-black text-white">{{ profile.name }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ profile.description }}</p>
                                </div>

                                <div
                                    class="h-5 w-5 rounded-full border"
                                    :class="selectedProfile === profile.id ? 'border-amber-300 bg-amber-300' : 'border-white/25 bg-transparent'"
                                />
                            </div>

                            <div class="mt-6 grid gap-3">
                                <div
                                    v-for="(value, key) in profile.toggles"
                                    :key="key"
                                    class="flex items-center justify-between rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm"
                                >
                                    <span class="font-semibold capitalize text-slate-200">{{ labelFor(key) }}</span>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.18em]"
                                        :class="value ? 'bg-emerald-400/20 text-emerald-200' : 'bg-slate-500/20 text-slate-300'"
                                    >
                                        {{ value ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <form class="mt-8 flex flex-col gap-4 border-t border-white/10 pt-6 md:flex-row md:items-center md:justify-between" @submit.prevent="submit">
                        <div>
                            <p class="text-sm font-semibold text-white">Selected profile</p>
                            <p class="mt-1 text-sm text-slate-300">{{ selectedProfileLabel }}</p>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-2xl border border-amber-300 bg-amber-300 px-6 py-4 text-sm font-black uppercase tracking-[0.18em] text-slate-950 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="form.processing || !selectedProfile"
                        >
                            {{ form.processing ? 'Applying profile…' : 'Apply profile and continue' }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
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

    return match ? `${match.name} profile selected.` : 'No profile selected.';
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
    return key
        .replace('enable_', '')
        .replaceAll('_', ' ');
}
</script>
