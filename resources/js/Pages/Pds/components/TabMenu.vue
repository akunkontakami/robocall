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
defineProps(["tab"]);

const menus = [
    {
        key: "dashboard",
        name: "Dashboard",
        url: route('pds.dashboard'),
        show: true,
    },
    {
        key: "setup",
        name: "Setup PDS",
        url: route('pds.setup'),
        show: true,
    },
    {
        key: "monitoring",
        name: "Monitoring PDS",
        url: route('pds.monitoring'),
        show: true,
    },
    {
        key: "report",
        name: "Report PDS",
        url: route('pds.report'),
        show: true,
    },
];
</script>
