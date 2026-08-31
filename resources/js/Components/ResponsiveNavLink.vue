<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-start text-base font-semibold text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition duration-150 ease-in-out dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-100 dark:focus:ring-offset-slate-950'
        : 'block w-full rounded-lg border border-transparent px-3 py-2 text-start text-base font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition duration-150 ease-in-out dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white dark:focus:ring-offset-slate-950',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
