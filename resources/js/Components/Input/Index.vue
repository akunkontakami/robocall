<template>
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
    <small
        class="block mt-[-5px] mb-2 text-[10px] text-[#7B7B7B]"
        v-if="labelHelp"
        v-html="labelHelp"
    ></small>
    <div
        class="relative mb-2"
        x-data="{input: $el.getAttribute('data-value')}"
        :data-value="$attrs.value || ''"
        v-bind:class="error ? 'has-error' : '' + $attrs.name"
    >
        <i
            v-bind:class="icon"
            class="absolute top-[14px] left-4 text-[14px] text-[#A4A4A4]"
            v-if="icon"
        ></i>
        <input
            x-model="input"
            v-bind:class="icon ? 'ps-10' : ''"
            @keypress="isNumber($event, $attrs.type || 'text')"
            @input="
                validateInput($event.target, $attrs.name);
                $emit(
                    'update:modelValue',
                    ($event.target as HTMLInputElement).value
                );
            "
            v-bind="$attrs"
            class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] outline-none shadow-none py-2 w-full mb-2"
        />
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
        <div
            class="mb-4 mt-[-6px]"
            v-if="$attrs.maxlength && hideLength == undefined"
        >
            <p class="text-[10px] text-[#666666] float-right">
                {{ modelValue?.length }}
                /{{ $attrs.maxlength }}
            </p>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { useAttrs } from "vue";

const props = defineProps([
    "modelValue",
    "label",
    "icon",
    "help",
    "error",
    "id",
    "hideLength",
    "labelHelp",
]);
const attr = useAttrs();
const validateInput = (input: any, name: any) => {
    var value = input.value;
    if (name === "username") {
        if (value) {
            value = value
                .toString()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase()
                .trim()
                .replace(/\s+/g, "_")
                .replace(/[^\w-]+/g, "")
                .replace(/--+/g, "_");
            input.value = value;
        }
    } else if (attr.maxlength) {
        input.value = value.slice(0, attr.maxlength);
    }
};

const isNumber = (evt: any, type: any) => {
    var charCode = evt.which ? evt.which : evt.keyCode;
    if (type === "number" && [46, 44, 43, 45, 101].includes(charCode)) {
        evt.preventDefault();
    }
    if (
        type === "number" &&
        charCode > 31 &&
        (charCode < 48 || charCode > 57) &&
        charCode !== 46
    ) {
        evt.preventDefault();
    }
    if (attr.maxlength && ![8, 17, 65].includes(charCode)) {
        if (evt.target.value.length >= Number(attr.maxlength)) {
            evt.preventDefault();
        }
    }
    return true;
};
</script>
