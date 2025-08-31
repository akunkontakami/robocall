<template>
    <div class="shadow border rounded-[20px] bg-white p-6">
        <h2 class="text-[#9291A5] text-lg mb-0 font-krub-regular">
            {{ label }}
        </h2>
        <h2 class="text-lg mb-4 font-krub-bold text-[#1E1B39]">
            {{ title }}
        </h2>

        <div class="bg-[#E5E5EF] w-full mb-7 h-[1px]"></div>

        <div v-for="(item, index) in data" :key="index" class="flex justify-between gap-9 items-center mb-5">
            <div class="w-[20%]">
                <span class="text-lg text-[#615E83] font-krub-medium">{{ item.label }}</span>
            </div>

            <div class="w-[70%] bg-[#F8F8FF] rounded-lg h-[22px]">
                <div
                    class="h-[22px] rounded-lg transition-all duration-300"
                    :class="item.color"
                    :style="{ width: Math.min(item.percentage, 100) + '%' , backgroundColor: item.color }"
                ></div>
            </div>

            <div class="w-[10%]">
                <span class="text-lg text-[#615E83] font-krub-medium">{{ item.count }}</span>
            </div>
        </div>

    </div>
</template>
<script setup lang="ts">
interface DataInterface {
    label: string;
    count: number | string;
    percentage: number;
    color: string;
}

defineProps<{
    label: string;
    title: string;
    data: DataInterface[];
}>();
</script>
