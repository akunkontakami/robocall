<template>
    <AppLayout title="PDS" header="PDS">
        <template v-slot:tab>
            <TabMenu tab="dashboard" />
        </template>
        <div class="flex items-center gap-6 mb-1">
            <div class="fixed top-0 left-0 bottom-0 right-0 bg-black/30 z-[1] flex items-center justify-center" v-if="loading">
                <div class="relative flex flex-col items-center justify-center">
                    <span class="absolute inline-flex h-10 w-10 animate-ping rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex size-5 rounded-full bg-white/10"></span>
                </div>
            </div>
            <p class="font-krub-bold text-[#0D0D0D] text-base mb-3">PDS Dashboard</p>

            <ButtonOutlineGreen class="ms-auto mb-3" @click="exportData">
                Export Data
            </ButtonOutlineGreen>
            <p class="mb-3 text-[13px] text-[#181C32]">PDS Name:</p>
            <Select class="min-w-[180px]" v-model="filter.pds" :value="filter.pds">
                <option value="" disabled selected>Select PDS Name</option>

                <option v-for="item in pds_list" :value="item.pds_name">{{ item.pds_name }}</option>
            </Select>

            <p class="mb-3 text-[13px] text-[#181C32]">Periode:</p>
            <Select class="min-w-[120px]" v-model="filter.period" :value="filter.period">
                <option value="" >All</option>
                <option value="Today" >Today</option>
                <option value="Week" >Week</option>
                <option value="Month" >Month</option>
            </Select>
            <ButtonYellow class="!bg-dark-blue mb-3" @click="submitFilter">
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
import { onMounted, ref } from "vue";
import ButtonOutlineGreen from "@/Components/Button/ButtonOutlineGreen.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import Select from "@/Components/Input/Select.vue";
import CardPerformance from "./components/CardPerformance.vue";
import axios from "axios";
import { getQueryParam, numberFormat, removeAllUrlParameter, routeAppendParam } from "@/Plugins/Function/global-function";

defineProps(["pds_list"])

const items = ref([
    {
        id: 1,
        label: "Agent Ready",
        color: "#4280EF",
        count: 0,
    },
    {
        id: 2,
        label: "Data Size",
        color: "#EB9813",
        count: 0,
    },
    {
        id: 3,
        label: "Total Call",
        color: "#677AD6",
        count: 0,
    },
    {
        id: 4,
        label: "Redial",
        color: "#0CE3B0",
        count: 0,
    },
    {
        id: 5,
        label: "Duration",
        color: "#F24E65",
        count: "00.00.00",
    },
]);

const durations = ref([
    {
        id: 1,
        label: getQueryParam('period') || "All",
        title: 'Average Handling Time (AHT)',
        count: "00:00:00",
    },
    {
        id: 1,
        label: getQueryParam('period') || "All",
        title: 'Duration Call',
        count: "00:00:00",
    },
    {
        id: 1,
        label: getQueryParam('period') || "All",
        title: 'Idle Time',
        count: "00:00:00",
    },
]);

const performances = ref([
    {
        id: 1,
        label: getQueryParam('period') || 'All',
        title: 'Performance Calls',
        data: [
            {
                label: 'Answer',
                color: '#0CE3B0',
                count: '0',
                percentage: 0
            },
            {
                label: 'No Answer',
                color: '#EB9813',
                count: '0',
                percentage: 0
            },
            {
                label: 'Abandon',
                color: '#F24E65',
                count: '0',
                percentage: 0
            }
        ]
    },
    {
        id: 2,
        label: getQueryParam('period') || 'All',
        title: 'Percentage Calls',
        data: [
            {
                label: 'Answer Rate',
                color: '#0CE3B0',
                count: '0',
                percentage: 0
            },
            {
                label: 'No Answer Rate',
                color: '#EB9813',
                count: '0',
                percentage: 0
            },
            {
                label: 'Abandon Rate',
                color: '#F24E65',
                count: '0',
                percentage: 0
            }
        ]
    }
])

const filter = ref({
    pds: getQueryParam('pds') || '',
    period: getQueryParam('period') || ''
})

const loading = ref(false)

const submitFilter = () => {
    loading.value = true
    if (filter.value.period || filter.value.pds) {
        removeAllUrlParameter()
        routeAppendParam(filter.value, false)
    }

    durations.value[0].label = filter.value.period
    durations.value[1].label = filter.value.period
    durations.value[2].label = filter.value.period

    performances.value[0].label = filter.value.period
    performances.value[1].label = filter.value.period

    axios.get(route('pds.dashboard.data'), {
        params: filter.value
    })
    .then((res: any) => {
        const sessions = res.data.sessions

        items.value[1].count = numberFormat(sessions.DataSize)
        items.value[2].count = numberFormat(sessions.DataDialed)
        items.value[3].count = numberFormat(sessions.DialCount)
        items.value[4].count = sessions.TotalDurationFormatted

        performances.value[0].data[0].count = numberFormat(sessions.DialAgentAnswered)
        performances.value[0].data[0].percentage = safePercentage(sessions.DialAgentAnswered, sessions.DialCount)

        performances.value[0].data[1].count = numberFormat(sessions.DialFailed)
        performances.value[0].data[1].percentage = safePercentage(sessions.DialFailed, sessions.DialCount)

        performances.value[0].data[2].count = numberFormat(sessions.DialAbandoned)
        performances.value[0].data[2].percentage = safePercentage(sessions.DialAbandoned, sessions.DialCount)

        performances.value[1].data[0].count = Number(safePercentage(sessions.DialAgentAnswered, sessions.DialCount)).toLocaleString('en-US', { maximumFractionDigits: 2 }) + " %"
        performances.value[1].data[0].percentage = safePercentage(sessions.DialAgentAnswered, sessions.DialCount)

        performances.value[1].data[1].count = Number(safePercentage(sessions.DialFailed, sessions.DialCount)).toLocaleString('en-US', { maximumFractionDigits: 2 }) + " %"
        performances.value[1].data[1].percentage = safePercentage(sessions.DialFailed, sessions.DialCount)

        performances.value[1].data[2].count = Number(safePercentage(sessions.DialAbandoned, sessions.DialCount)).toLocaleString('en-US', { maximumFractionDigits: 2 }) + " %"
        performances.value[1].data[2].percentage = safePercentage(sessions.DialAbandoned, sessions.DialCount)

        durations.value[0].count = sessions.AverageHandling
        durations.value[1].count = sessions.DurationCall
        durations.value[2].count = res.data.idle.time
    })
    .finally(() => {
        loading.value = false
    })
}

const monitoring = () => {
    axios.get(route('pds.dashboard.monitoring'))
    .then((res: any) => {
        items.value[0].count = numberFormat(res.data.total)
    })
    .finally(() => {
    })
}

onMounted(() => {
    submitFilter()
    monitoring()
})

function safePercentage(value: any, total: any) {
    if (!total || total === 0) return 0;
    return (value / total) * 100;
}

const exportData = () => {
    const url = new URL(route('pds.dashboard.export'))

    if (filter.value.period) {
        url.searchParams.set('period', filter.value.period)
    }
    if (filter.value.pds) {
        url.searchParams.set('pds', filter.value.pds)
    }

    window.open(url.toString())

}


</script>
