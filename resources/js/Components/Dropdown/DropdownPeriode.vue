<template>
    <div x-data="{ dropdownOpen: false }">
        <div class="flex gap-2 items-center">
            <span class="text-[12px] font-krub-semibold"> Period : </span>
            <button
                type="button"
                class="flex items-center gap-2 e py-[6px] px-3 bg-white text-[11px] border rounded-full font-krub-semibold"
                x-on:click="dropdownOpen=!dropdownOpen"
                @click.stop="$event.preventDefault()"
                x-ref="button"
            >
                <i class="isax icon-calendar-2"></i>
                {{ label }}
                <i class="isax icon-arrow-down-1"></i>
            </button>
        </div>

        <Dropdown
            x-show="dropdownOpen"
            x-anchor.bottom-start="$refs.button"
            class="z-10 mt-1"
        >
            <DropdownMenu
                v-for="range in ranges"
                @click="$emit('update', range.value)"
            >
                {{ range.label }}
            </DropdownMenu>
        </Dropdown>
    </div>
</template>
<script setup lang="ts">
import Dropdown from "./Dropdown.vue";
import DropdownMenu from "./DropdownMenu.vue";
import { ref, watch,onMounted } from "vue";
const props = defineProps(["period"]);

const label = ref("Today");
const ranges = ref([
    {
        label: "Today",
        value: "today",
    },
    {
        label: "Last 7 Days",
        value: "last-7-days",
    },
    {
        label: "Last 30 Days",
        value: "last-30-days",
    },
]);

const changeLabel = () => {
    const find = ranges.value.find((range: any) => range.value == props.period);
    label.value = find ? find.label : "Today";
};

onMounted(()=>{
    changeLabel()
})
watch(
    () => props.period,
    (value, val) => {
        changeLabel();
    }
);
</script>
