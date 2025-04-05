<template>
    <Link v-bind="$attrs" v-bind:class="classElement" :href="href" v-if="href">
        <i v-bind:class="icon" class="text-[15px]" v-if="icon"></i>
        <slot />
    </Link>
    <button
        v-bind="$attrs"
        v-bind:class="
            classElement + ($attrs.loading ? ' cursor-not-allowed' : '')
        "
        :type="type || 'button'"
        v-if="!href"
    >
        <LoadingButton v-if="$attrs.loading" />
        <i
            v-bind:class="icon"
            class="text-[15px]"
            v-if="icon && !$attrs.loading"
        ></i>
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
    setup(props, context) {
        var classElement =
            "flex justify-center bg-yellow text-white px-3 font-krub-medium py-2 rounded-lg text-[12px] items-center gap-1 h-fit text-center disabled:bg-[#ddd] disabled:cursor-not-allowed";
        return {
            classElement,
        };
    },
};
</script>
