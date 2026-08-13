<template>
    <div>
        <label class="flex cursor-pointer items-center gap-3">
            <input
                type="checkbox"
                :checked="modelValue.length > 0"
                @change="toggleAll"
            />
            <span class="text-xs font-opensauceone-medium text-slate-600">
                {{ label }}
            </span>
        </label>

        <div
            v-if="modelValue.length > 0"
            class="ml-4 mt-3 grid grid-cols-2 gap-3 rounded-lg border bg-slate-50 p-4 sm:grid-cols-3"
        >
            <label
                v-for="number in options"
                :key="number"
                class="flex cursor-pointer items-center gap-2 text-xs text-slate-600"
            >
                <input
                    type="checkbox"
                    :checked="modelValue.includes(`${prefix} ${number}`)"
                    @change="toggle(`${prefix} ${number}`)"
                />
                <span>{{ prefix }} {{ number }}</span>
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
