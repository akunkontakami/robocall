<template>
    <AppLayout title="Robocall" header="Robocall">
        <template v-slot:tab>
            <TabMenu tab="dashboard" />
        </template>
        <div class="flex items-center gap-6 mb-1">
            <div
                class="fixed top-0 left-0 bottom-0 right-0 bg-black/30 z-[1] flex items-center justify-center"
                v-if="loading"
            >
                <div class="relative flex flex-col items-center justify-center">
                    <span
                        class="absolute inline-flex h-10 w-10 animate-ping rounded-full bg-white opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex size-5 rounded-full bg-white/10"
                    ></span>
                </div>
            </div>
            <p class="font-krub-bold text-[#0D0D0D] text-base mb-3">
                Robocall Dashboard
            </p>

            <ButtonOutlineGreen class="ms-auto mb-3" @click="exportData">
                Export Data
            </ButtonOutlineGreen>
            <p class="mb-3 text-[13px] text-[#181C32]">Robocall Name:</p>
            <Select
                class="min-w-[180px]"
                v-model="filter.robocall"
                :value="filter.robocall"
            >
                <option value="" disabled selected>Select Robocall Name</option>

                <option
                    v-for="item in robocall_list"
                    :value="item.robocall_name"
                >
                    {{ item.robocall_name }}
                </option>
            </Select>

            <p class="mb-3 text-[13px] text-[#181C32]">Periode:</p>
            <Select
                class="min-w-[120px]"
                v-model="filter.period"
                :value="filter.period"
            >
                <option value="">All</option>
                <option value="Today">Today</option>
                <option value="Week">Week</option>
                <option value="Month">Month</option>
            </Select>
            <ButtonYellow class="!bg-dark-blue mb-3" @click="submitFilter">
                Submit
            </ButtonYellow>
        </div>
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
import { onMounted, ref } from "vue";
import {
    getQueryParam,
    removeAllUrlParameter,
    routeAppendParam,
} from "@/Plugins/Function/global-function";
import axios from "axios";
import Select from "@/Components/Input/Select.vue";
import ButtonOutlineGreen from "@/Components/Button/ButtonOutlineGreen.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";

defineProps(["robocall_list"]);

const items = ref([
    {
        id: 1,
        label: "Total Datasize",
        color: "#4280EF",
        count: 0,
    },
    {
        id: 2,
        label: "Total Calls",
        color: "#677AD6",
        count: 0,
    },
    {
        id: 3,
        label: "Call Answered",
        color: "#EB9813",
        count: 0,
    },
    {
        id: 4,
        label: "Call Not Answered",
        color: "#677AD6",
        count: 0,
    },
    {
        id: 5,
        label: "Call in Progress",
        color: "#677AD6",
        count: 0,
    },
]);

const filter = ref({
    robocall: getQueryParam("robocall") || "",
    period: getQueryParam("period") || "Week",
});

const loading = ref(false);

const submitFilter = () => {
    loading.value = true;
    if (filter.value.period || filter.value.robocall) {
        removeAllUrlParameter();
        routeAppendParam(filter.value, false);
    }

    axios
        .get(route("robocall.dashboard.data"), {
            params: filter.value,
        })
        .then((res: any) => {
            const sessions = res.data.sessions;
            items.value[0].count = sessions.DataSize;
            items.value[1].count = sessions.DialCount;
            items.value[2].count = sessions.DialContacted;
            items.value[3].count = sessions.DialFailed;
            items.value[4].count = sessions.DataDialed;
        })
        .finally(() => {
            loading.value = false;
        });
};

onMounted(() => {
    submitFilter();
});

const exportData = () => {
    const url = new URL(route("robocall.dashboard.export"));

    if (filter.value.period) {
        url.searchParams.set("period", filter.value.period);
    }
    if (filter.value.robocall) {
        url.searchParams.set("robocall", filter.value.robocall);
    }

    window.open(url.toString());
};
</script>
