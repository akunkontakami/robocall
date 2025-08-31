<template>
    <div class="shadow border rounded-[20px] bg-white p-6">
        <h2 class="text-[#9291A5] text-sm mb-0 font-krub-regular">
            {{ label }}
        </h2>
        <h2 class="text-lg mb-4 font-krub-bold text-[#1E1B39]">
            {{ title }}
        </h2>
        <h1 class="font-krub-bold text-3xl xl:text-[44px] text-[#1E1B39] mb-0">
            {{ count }}
        </h1>
    </div>
</template>
<script setup lang="ts">
defineProps<{
    label: string;
    title: string;
    count: number | string;
}>();
</script>
