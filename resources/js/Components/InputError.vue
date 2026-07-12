<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="mt-1.5 rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-sm font-medium text-rose-700">
            {{ message }}
        </p>
    </div>
</template>
