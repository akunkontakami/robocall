<template>
    <span>
        <label
            :for="id"
            class="text-[12px] text-dark font-krub-medium mb-1 block pre-text-content"
            v-if="label"
        >
            {{ label }}
            <span class="text-red" v-if="$attrs.required">*</span>
        </label>
        <small
            class="block mt-[-5px] mb-1 text-[10px] text-[#7B7B7B]"
            v-if="labelHelp"
            v-html="labelHelp"
        ></small>
        <div class="relative mb-2" v-bind:class="{ 'has-error': error }">
            <i
                v-bind:class="icon"
                class="absolute top-[14px] left-4 text-[14px]"
                v-if="icon"
            ></i>
            <select
                v-bind="$attrs"
                v-bind:class="{
                    'ps-10' : icon,
                }"
                :id="id"
                @change="
                    $emit(
                        'update:modelValue',
                        ($event.target as HTMLInputElement).value
                    )
                "
                class="border rounded-lg select-input placeholder:text-black px-4 border-offline text-[12px] outline-none shadow-none py-2 min-h-[42px] w-full mb-2 disabled:bg-[#F3F3F3]"
            >
                <slot />
            </select>
            <small
                v-if="error"
                class="mt-[-7px] error-text mb-4 block text-[11px] "
                >{{ error }}</small
            >
            <small
                class="block mt-[-7px] text-[10px] mb-4"
                v-if="help"
                v-html="help"
            ></small>
        </div>
    </span>
</template>
<script lang="ts">
export default {
    props: ["label", "icon", "help", "id", "error", "labelHelp"],
};
</script>
