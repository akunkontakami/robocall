<template>
    <AppLayout title="PDS" header="PDS">
        <template v-slot:tab>
            <TabMenu tab="dashboard" />
        </template>
        <div class="flex items-center gap-6 mb-1">
            <p class="font-krub-bold text-[#0D0D0D] text-base mb-3">PDS Dashboard</p>

            <ButtonOutlineGreen class="ms-auto mb-3">
                Export Data
            </ButtonOutlineGreen>
            <p class="mb-3 text-[13px] text-[#181C32]">Pds Name:</p>
            <Select class="min-w-[180px]">
                <option value="" disabled selected>Select PDS Name</option>
            </Select>

            <p class="mb-3 text-[13px] text-[#181C32]">Periode:</p>
            <Select class="min-w-[120px]">
                <option value="" disabled selected>Today</option>
            </Select>
            <ButtonYellow class="!bg-dark-blue mb-3">
                Submit
            </ButtonYellow>
        </div>
        <ul class="grid md:grid-cols-5 gap-5 mb-3">
            <li v-for="item in items" :key="item.id">
                <CardDashboard
                    :label="item.label"
                    :count="item.count"
                    :color="item.color"
                />
            </li>
        </ul>

        <ul class="grid md:grid-cols-2 gap-5 mb-3">
            <li v-for="item in performances" :key="item.id">
                <CardPerformance
                    :label="item.label"
                    :title="item.title"
                    :data="item.data"
                />
            </li>
        </ul>

        <ul class="grid md:grid-cols-3 gap-5">
            <li v-for="item in durations" :key="item.id">
                <CardDuration
                    :label="item.label"
                    :title="item.title"
                    :count="item.count"
                />
            </li>
        </ul>
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import CardDashboard from "./components/CardDashboard.vue";
import CardDuration from "./components/CardDuration.vue";
import { ref } from "vue";
import ButtonOutlineGreen from "@/Components/Button/ButtonOutlineGreen.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import Select from "@/Components/Input/Select.vue";
import CardPerformance from "./components/CardPerformance.vue";

const items = ref([
    {
        id: 1,
        label: "Agent Ready",
        color: "#4280EF",
        count: 100,
    },
    {
        id: 2,
        label: "Data Size",
        color: "#EB9813",
        count: "20.000",
    },
    {
        id: 3,
        label: "Total Call",
        color: "#677AD6",
        count: "12.850",
    },
    {
        id: 4,
        label: "Redial",
        color: "#0CE3B0",
        count: 800,
    },
    {
        id: 5,
        label: "Duration",
        color: "#F24E65",
        count: "04.30.00",
    },
]);

const durations = ref([
    {
        id: 1,
        label: "Today",
        title: 'Average Handling Time (AHT)',
        count: "01:10:06",
    },
    {
        id: 1,
        label: "Today",
        title: 'Duration Call',
        count: "01:10:06",
    },
    {
        id: 1,
        label: "Today",
        title: 'Idle Time',
        count: "01:10:06",
    },
]);

const performances = ref([
    {
        id: 1,
        label: 'Today',
        title: 'Performance Calls',
        data: [
            {
                label: 'Answer',
                color: '#0CE3B0',
                count: "1.250",
                percentage: (1250 / 12850) * 100
            },
            {
                label: 'No Answer',
                color: '#EB9813',
                count: "8.400",
                percentage: (8400 / 12850) * 100
            },
            {
                label: 'Abandon',
                color: '#F24E65',
                count: "3.200",
                percentage: (3200 / 12850) * 100
            }
        ]
    },
    {
        id: 2,
        label: 'Today',
        title: 'Percentage Calls',
        data: [
            {
                label: 'Answer Rate',
                color: '#0CE3B0',
                count: '3.3%',
                percentage: 3.3
            },
            {
                label: 'No Answer Rate',
                color: '#EB9813',
                count: '12.7%',
                percentage: 12.7
            },
            {
                label: 'Abandon Rate',
                color: '#F24E65',
                count: '15.2%',
                percentage: 15.2
            }
        ]
    }
])
</script>
