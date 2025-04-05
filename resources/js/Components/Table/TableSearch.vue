<template>
    <form @submit.prevent="search" class="relative md:w-[30%]">
        <i
            class="isax icon-search-normal-1 absolute top-[13px] left-3 text-[13px]"
        ></i>
        <input
            type="text"
            :placeholder="placeholder || 'Search'"
            id="search"
            title="Enter to search"
            @keyup.enter="search"
            :value="getQueryParam('search')"
            class="border rounded-lg ps-8 placeholder:text-black px-3 text-[12px] outline-none shadow-none py-[7px] w-full mb-3 bg-white min-h-[40px]"
        />
    </form>
</template>
<script setup lang="ts">
import {
    routeAppendParam,
    getQueryParam,
} from "@/Plugins/Function/global-function";
defineProps(["placeholder"]);
const search = (event: any) => {
    routeAppendParam({ search: event.target.value }, false);
};
</script>
