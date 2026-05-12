<template>
    <div x-data="{selectOpen:false}" class="mb-3 relative">
        <label
            :for="id"
            class="text-[12px] text-dark font-krub-medium mb-1 block pre-text-content"
            v-bind:class="{
                'text-red': error,
            }"
            v-if="label"
        >
            {{ label }}
            <span class="text-red" v-if="$attrs.required">*</span>
        </label>
        <div
            class="flex items-center justify-between border bg-white rounded-lg text-[12px] px-4 py-3 cursor-pointer"
            v-bind:class="{
                'border-red': error,
                '!bg-[#F3F3F3] !cursor-default': disabled,
            }"
            x-on:click="selectOpen=!selectOpen"
            x-ref="selectContainer"
        >
            <span class="pre-text-content max-w-[95%]">
                {{ selected || placeholder }}</span
            >
            <i class="isax icon-arrow-down-1" v-if="!disabled"></i>
        </div>
        <div
            class="bg-white border rounded-lg absolute w-full max-h-60 p-2 flex flex-col z-10 mb-2 mt-1"
            x-transition:enter="transition ease-out duration-50"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100"
            x-show="selectOpen"
            x-on:click.away="selectOpen = false"
            x-anchor.bottom="$refs.selectContainer"
            v-if="!disabled"
        >
            <div>
                <input
                    type="text"
                    v-model="search"
                    class="border w-full text-[11px] mb-2 px-3 py-1 outline-none shadow-none rounded-lg"
                    :placeholder="`Search ${label}`"
                />
            </div>
            <ul class="flex-1 overflow-auto">
                <li v-for="item in itemsList" :key="item.value">
                    <span
                        class="block py-1 px-1 text-[13px] mb-[1px] cursor-pointer font-krub-medium hover:bg-[#dddddd52] w-full rounded-md"
                        @click="choose(item)"
                        x-on:click="selectOpen=false"
                    >
                        {{ item.label }}
                    </span>
                </li>
                <li v-if="!itemsList.length">
                    <span
                        class="block py-1 px-1 text-[13px] mb-[1px] cursor-pointer font-krub-light italic"
                    >
                        No Data Available
                    </span>
                </li>
            </ul>
        </div>
        <small
            v-if="error"
            class="text-red error-text mb-4 block text-[11px]"
            >{{ error }}</small
        >
        <small class="text-[10px] mb-4 text-[#A3A3A3]" v-if="help">{{
            help
        }}</small>
    </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from "vue";

const emit = defineEmits(["update:modelValue", "changeValue"]);
const props = defineProps([
    "label",
    "help",
    "id",
    "error",
    "placeholder",
    "items",
    "value",
    "disabled",
]);

const search = ref("");
const itemsList = ref(props.items);
const selected = ref("");

const choose = (item: any) => {
    selected.value = item.label;
    search.value = "";
    emit("update:modelValue", item.value);
};

const setSelected = () => {
    const selectedValue = props.value;
    if (selectedValue) {
        const item = itemsList.value.find(
            (row: any) => row.value == selectedValue,
        );
        selected.value = item?.label;
        emit("changeValue", item.value);
    } else {
        selected.value = "";
    }
};

watch(
    () => props.value,
    (val, value) => {
        setSelected();
    },
);
watch(
    () => props.items,
    (val, value) => {
        itemsList.value = props.items;
    },
);
watch(
    () => search.value,
    (val, value) => {
        itemsList.value = props.items.filter((row: any) =>
            row.label.toLowerCase().includes(search.value.toLowerCase()),
        );
    },
);

onMounted(() => {
    setSelected();
});
</script>
