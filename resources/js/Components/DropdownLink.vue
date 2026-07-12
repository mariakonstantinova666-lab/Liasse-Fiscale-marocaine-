<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-sm font-medium leading-5 text-slate-700 transition duration-150 hover:bg-indigo-50 hover:text-indigo-700 focus:bg-indigo-50 focus:outline-none"
    >
        <slot />
    </Link>
</template>
