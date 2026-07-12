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
        ? 'inline-flex items-center rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold leading-5 text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition duration-150 ease-in-out'
        : 'inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold leading-5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
