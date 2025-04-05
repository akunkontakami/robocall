<template>
    <label
        :for="id"
        class="text-[12px] text-dark font-krub-medium mb-1 block"
        v-if="label"
    >
        {{ label }}
        <span class="text-red" v-if="$attrs.required">*</span>
    </label>
    <div
        class="relative mb-2"
        x-data="{
            input: $el.getAttribute('data-value'),
            itemsDropdownOpen:false,
            position : {
                x:0,
                y:0,
                width : '100px'
            },
            openDropdown(react){
                 this.position = {
                     x: `${react.x}px`,
                     y: `${react.y+47}px`,
                     width: `${react.width}px`,
                 }
                 this.itemsDropdownOpen=!this.itemsDropdownOpen
            }
         }"
        :data-value="$attrs.value || ''"
        v-bind:class="{ 'has-error': error }"
    >
        <div
            class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] flex gap-2 flex-wrap outline-none py-2 w-full mb-2 items-center"
            x-ref="multipleSelectDropdown"
        >
            <span
                class="border px-2 items-center flex rounded-md bg-[#ddd] text-[10px] h-[20px]"
                x-on:click="itemsDropdownOpen=false"
                v-for="item in (selected as any)"
            >
                {{ item.value }}
                <i
                    class="isax icon-close-circle ms-3 cursor-pointer"
                    @click="removeItem(item.id)"
                ></i>
            </span>
            <div
                class="mt-[3px] flex-1 h-full min-w-[100px] cursor-pointer whitespace-nowrap overflow-hidden"
                x-on:click="openDropdown($el.parentElement.getBoundingClientRect())"
                v-bind:class="{ 'text-[#ddd]': selected.length }"
            >
                {{ placeholder }}
            </div>
        </div>
        <i
            class="isax icon-arrow-down-1 float-right mt-[-37px] me-4 text-[#B4B6B8] text-[13px]"
        ></i>
        <div class="mb-4" v-if="$attrs.maxlength">
            <p class="text-[11px] text-dark float-right">
                <span x-text="input.length"></span>/{{ $attrs.maxlength }}
            </p>
        </div>
        <div
            class="fixed w-full z-10 mt-1"
            x-transition:enter="transition ease-out duration-50"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100"
            x-show="itemsDropdownOpen"
            x-on:click.away="itemsDropdownOpen = false"
            x-bind:style="{ top: position.y, left : position.x,width : position.width}"
            x-anchor.bottom-start="$refs.multipleSelectDropdown"
        >
            <div
                class="bg-white border rounded-lg w-full max-h-60 p-2 flex flex-col"
            >
                <div>
                    <input
                        type="text"
                        v-model="search"
                        class="border w-full text-[11px] mb-2 font-krub-medium rounded-lg py-1"
                        placeholder="Search items"
                    />
                </div>
                <ul class="flex-1 overflow-auto">
                    <li v-for="item in itemList" :key="item.id">
                        <a
                            href="javascript:;"
                            class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] text-[11px] text-dark"
                            x-on:click="itemsDropdownOpen=false"
                            @click="addItem(item)"
                        >
                            <span v-html="item.html" v-if="item.html"></span>
                            <span v-else>{{ item.value }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <small
            v-if="error"
            class="mt-[-7px] error-text mb-4 block text-[11px]"
            >{{ error }}</small
        >
        <small
            class="block mt-[-7px] text-[10px] mb-4 text-[#A3A3A3]"
            v-if="help"
            >{{ help }}</small
        >
    </div>
</template>

<script lang="ts" setup>
import { ref, watch, onMounted } from "vue";

const emit = defineEmits(["update:modelValue"]);
const props = defineProps<{
    label?: string;
    help?: string;
    error?: string;
    id?: string;
    items: Array<any>;
    selected?: Array<any>;
    placeholder?: string;
}>();

const search = ref("");
const itemList = ref(props.items);
const selected = ref([]);

const addItem = (row: any) => {
    const selectedIds = selected.value.map((val: any) => val.id);
    if (!selectedIds.includes(row.id)) {
        (selected.value as any).push({
            id: row.id,
            value: row.value,
        });
        emit(
            "update:modelValue",
            selected.value.map((row: any) => row.id)
        );
    }
};

const removeItem = (id: string) => {
    selected.value = selected.value.filter((row: any) => row.id !== id);
    emit(
        "update:modelValue",
        selected.value.map((row: any) => row.id)
    );
};

onMounted(() => {
    if (props.selected) {
        const itemSelected: any = [];
        const selectedId = JSON.parse(JSON.stringify(props.selected));
        props.items.forEach((row: any) => {
            if (selectedId.includes(row.id)) {
                itemSelected.push({
                    id: row.id,
                    value: row.value,
                });
            }
            row.sub.forEach((key: any) => {
                if (selectedId.includes(key.id)) {
                    itemSelected.push({
                        id: key.id,
                        value: key.value,
                    });
                }
            });
        });
        selected.value = itemSelected;
    }
});
watch(search, (newValue, oldValue) => {
    const searchValue = newValue.toLowerCase();
    itemList.value = props.items?.filter(function (row) {
        return row.value.toLowerCase().includes(searchValue);
    });
});
</script>
