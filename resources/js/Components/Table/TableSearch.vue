<template>
    <form @submit.prevent="search" class="relative md:w-[30%] border rounded-lg ps-8 px-3 outline-none shadow-none py-[7px] w-full mb-3 bg-white min-h-[40px] flex items-center gap-2">
        <i
            class="isax icon-search-normal-1 absolute top-[13px] left-3 text-[13px]"
        ></i>
        <input
            type="text"
            :placeholder="placeholder || 'Search'"
            id="table-search"
            title="Enter to search"
            @keyup.enter="search"
            v-model="searchText"
            class="placeholder:text-black text-[12px] outline-none border-0 w-full ring-0 focus:ring-0 p-0"
        />
        <div>
            <IconClose class="cursor-pointer" @click="closeSearch" v-if="searchText" />
        </div>
        <div>
            <ButtonYellow
                class="!h-[26px] !py-0 flex items-center justify-center text-[10px] !px-2"
                @click="searchButton"
            >
                Search
            </ButtonYellow>
        </div>
    </form>
</template>
<script setup lang="ts">
import {
    routeAppendParam,
    getQueryParam,
} from "@/Plugins/Function/global-function";
import { ref } from "vue";
import IconClose from "../Icon/Etc/IconClose.vue";
import ButtonYellow from "../Button/ButtonYellow.vue";
defineProps(["placeholder"]);

const searchText = ref<any>(getQueryParam('search') || '')
const search = () => {
    routeAppendParam({ search: searchText.value }, false);
};

const closeSearch = () => {
    searchText.value = '';
    (document.getElementById('table-search') as HTMLInputElement).value = ''
    routeAppendParam({ search: searchText.value }, false);
}

const searchButton = () => {
    routeAppendParam({ search: searchText.value }, false);
}
</script>
