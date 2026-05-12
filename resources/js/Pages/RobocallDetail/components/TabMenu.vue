<template>
    <span v-for="menu in menus" class="block mb-2">
        <Link
            v-bind:class="
                tab === menu.key
                    ? 'text-yellow border-b-[3px] border-yellow'
                    : ''
            "
            :href="menu.url ?? ''"
            class="pb-2 hover:border-b-[3px] hover:text-yellow hover:border-yellow"
            v-if="menu.show"
        >
            {{ menu.name }}
        </Link>

        <a href="javascript:;" class="pb-2 cursor-not-allowed" v-else>
            {{ menu.name }}
        </a>
    </span>
</template>

<script lang="ts" setup>
import { Link } from "@inertiajs/vue3";
const props = defineProps(["tab", "id", "data"]);

const menus = [
    {
        key: "detail",
        name: "Robocall Detail",
        url: route("robocall.detail", props.id),
        show: true,
    },
    {
        key: "upload",
        name: "Upload",
        url: route("robocall.detail.upload", props.id),
        show: props.data.data_type == "campaign" ? false : true,
    },
    {
        key: "campaign",
        name: "Campaign",
        url: route("robocall.detail.campaign", props.id),
        show: props.data.data_type == "upload" ? false : true,
    },
];
</script>
