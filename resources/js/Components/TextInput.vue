<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-lg border-slate-300 bg-white text-sm shadow-sm transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-blue-600"
        v-model="model"
        ref="input"
    />
</template>
