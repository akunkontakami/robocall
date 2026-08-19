<template>
    <div>
        <div class="flex items-center">

        <label class="flex gap-x-3 cursor-pointer group">
            <input
                type="checkbox"
                class="!m-0 shrink-0"
                :checked="modelValue.length > 0"
                @change="toggleAll"
            />
            <span class="text-xs font-opensauceone-medium text-slate-600">
                {{ label }}
            </span>
        </label>
        </div>

        <div
            v-if="modelValue.length > 0"
            class="ml-4 grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-lg border mt-3"
        >
            <label
                v-for="number in options"
                :key="number"
                class="flex gap-x-2 cursor-pointer group"
            >
                <input
                    type="checkbox"
                    :checked="modelValue.includes(`${prefix} ${number}`)"
                    @change="toggle(`${prefix} ${number}`)"
                />
                <span class="text-xs font-opensauceone-medium text-slate-500 group-hover:text-slate-700 transition-colors">{{ prefix }} {{ number }}</span>
            </label>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    modelValue: string[];
    label: string;
    prefix: string;
    options: number[];
}>();
const emit = defineEmits(["update:modelValue"]);

const toggleAll = () => {
    emit(
        "update:modelValue",
        props.modelValue.length
            ? []
            : props.options.map((number) => `${props.prefix} ${number}`),
    );
};

const toggle = (value: string) => {
    emit(
        "update:modelValue",
        props.modelValue.includes(value)
            ? props.modelValue.filter((item) => item !== value)
            : [...props.modelValue, value],
    );
};
</script>
