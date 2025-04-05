<template>
    <AppLayout title="Robocall" header="Robocall">
        <template v-slot:tab>
            <TabMenu tab="dashboard" />
        </template>
        <ul class="grid md:grid-cols-5 gap-5">
            <li v-for="item in items" :key="item.id">
                <CardDashboard
                    :label="item.label"
                    :count="item.count"
                    :color="item.color"
                />
            </li>
        </ul>
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import CardDashboard from "./components/CardDashboard.vue";
import { ref } from "vue";

const items = ref([
    {
        id: 1,
        label: "Total Datasize",
        color: "#4280EF",
        count: 790,
    },
    {
        id: 2,
        label: "Total Calls",
        color: "#677AD6",
        count: 315,
    },
    {
        id: 3,
        label: "Call Answered",
        color: "#EB9813",
        count: 300,
    },
    {
        id: 4,
        label: "Call Not Answered",
        color: "#677AD6",
        count: 15,
    },
    {
        id: 5,
        label: "Call in Progress",
        color: "#677AD6",
        count: 2,
    },
]);
</script>
