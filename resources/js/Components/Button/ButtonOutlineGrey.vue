<template>
    <Link v-bind="$attrs" v-bind:class="classElement" :href="href" v-if="href">
        <i v-bind:class="icon" class="text-[15px]" v-if="icon"></i>
        <slot />
    </Link>
    <button
        v-bind="$attrs"
        v-bind:class="classElement"
        :type="type || 'button'"
        v-if="!href"
    >
        <LoadingButton v-if="$attrs.loading" />
        <i v-bind:class="icon" class="text-[15px]" v-if="icon"></i>
        <slot />
    </button>
</template>
<script lang="ts">
import { Link } from "@inertiajs/vue3";
import LoadingButton from "@/Components/Icon/Etc/IconLoadingButton.vue";
export default {
    props: ["href", "icon", "type"],
    components: {
        LoadingButton,
        Link,
    },
    setup() {
        const classElement =
            "border bg-white rounded-lg py-2 px-3 flex justify-center items-center gap-1 h-fit text-center disabled:bg-[#ddd] disabled:text-[#AFAFAF] disabled:cursor-not-allowed text-[12px]";
        return {
            classElement,
        };
    },
};
</script>
