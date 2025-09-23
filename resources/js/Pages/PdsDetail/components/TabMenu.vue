<template>
    <span v-for="menu in menus" class="block mb-2">
        <Link
            v-bind:class="
                tab === menu.key
                    ? 'text-yellow border-b-[3px] border-yellow'
                    : ''
            "
            :href="menu.url"
            class="pb-2 hover:border-b-[3px] hover:text-yellow hover:border-yellow"
            v-if="menu.show"
        >
            {{ menu.name }}
        </Link>
    </span>
</template>

<script lang="ts" setup>
import { Link } from "@inertiajs/vue3";
const props = defineProps(["tab", "id"]);

const menus = [
    {
        key: "detail",
        name: "PDS Detail",
        url: route('pds.detail', props.id),
        show: true,
    },
    {
        key: "campaign",
        name: "Assign Campaign",
        url: route('pds.detail.campaign', props.id),
        show: true,
    },
    {
        key: "spv-agent",
        name: "SPV/Agent",
        url: route('pds.detail.spv-agent', props.id),
        show: true,
    }
];
</script>
