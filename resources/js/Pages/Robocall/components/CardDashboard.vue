<template>
    <div class="shadow-md border rounded-md bg-white px-4 py-3 pb-2">
        <h2 class="text-[#4D4D4D] text-[13px] mb-0 font-krub-medium">
            {{ label }}
        </h2>
        <span class="block h-[4px] rounded-md w-[20px] mb-1" :style="`background-color: ${color}`"></span>
        <h1 class="font-krub-bold text-[26px] text-[#2C2C2C] mb-0">
            {{ count }}
        </h1>
    </div>
</template>
<script setup lang="ts">
defineProps<{
    label: string;
    color: string;
    count: number | string;
}>();
</script>
