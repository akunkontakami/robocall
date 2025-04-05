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
            }"
            x-on:click="selectOpen=!selectOpen"
            x-ref="selectContainer"
        >
            <span class="pre-text-content max-w-[95%]">
                {{ selected || placeholder }}</span
            >
            <i class="isax icon-arrow-down-1"></i>
        </div>
        <div
            class="bg-white border rounded-lg absolute w-full max-h-60 p-2 flex flex-col z-10 mb-2 mt-1"
            x-transition:enter="transition ease-out duration-50"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100"
            x-show="selectOpen"
            x-on:click.away="selectOpen = false"
            x-anchor.bottom="$refs.selectContainer"
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
                    <a
                        href="javascript:;"
                        class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] text-[11px] text-[#7B7B7B]"
                        x-on:click="selectOpen=false"
                        v-if="!item.sub.length"
                        @click="choose(item)"
                    >
                        {{ item.name }}
                    </a>
                    <a
                        href="javascript:;"
                        class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] cursor-not-allowed text-[11px] tex-dark"
                        v-bind:class="{ '': item.sub.length }"
                        v-else
                    >
                        {{ item.name }}
                    </a>
                    <ul class="ps-4">
                        <li v-for="sub in item.sub">
                            <a
                                href="javascript:;"
                                x-on:click="selectOpen=false"
                                class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] text-[11px] text-[#7B7B7B]"
                                @click="choose(sub)"
                            >
                                {{ sub.name }}
                            </a>
                        </li>
                    </ul>
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
    </div>
</template>
<script setup lang="ts">
import { ref, watch, onMounted } from "vue";

const emit = defineEmits(["update:modelValue"]);
const props = defineProps([
    "label",
    "help",
    "id",
    "error",
    "placeholder",
    "items",
    "value",
]);

const search = ref("");
const itemsList = ref(props.items);
const selected = ref("");

const choose = (item: any) => {
    selected.value = item.name;
    search.value = "";
    emit("update:modelValue", item.id);
};

const setSelected = () => {
    const selectedValue = props.value;
    if (selectedValue) {
        itemsList.value.forEach(function (row: any) {
            if (row.id == selectedValue) {
                selected.value = row.name;
            }
            row.sub.forEach(function (sub: any) {
                if (sub.id == selectedValue) {
                    selected.value = sub.name;
                }
            });
        });
    } else {
        selected.value = "";
    }
};

watch(
    () => props.value,
    (val, value) => {
        setSelected();
    }
);
watch(
    () => props.items,
    (val, value) => {
        itemsList.value = props.items;
    }
);
watch(
    () => search.value,
    (newValue, value) => {
        const searchValue = newValue.toLowerCase();
        itemsList.value = props.items?.filter(function (row: any) {
            return (
                row.name.toLowerCase().includes(searchValue) ||
                row.sub.some(function (sub: any) {
                    return sub.name.toLowerCase().includes(searchValue);
                })
            );
        });
    }
);

onMounted(() => {
    setSelected();
});
</script>
