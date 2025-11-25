<template>
    <div class="fixed top-0 left-0 bottom-0 right-0 bg-black/30 z-[1] flex items-center justify-center" v-if="loading">
        <div class="relative flex flex-col items-center justify-center">
            <span class="absolute inline-flex h-10 w-10 animate-ping rounded-full bg-white opacity-75"></span>
            <span class="relative inline-flex size-5 rounded-full bg-white/10"></span>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 mb-8" >
        <div class="lg:col-span-5 flex flex-col gap-4">
            <div>
                <h2 class="text-[#0D0D0D] text-[13px] font-krub-bold mb-3">CALLS TODAY</h2>
                <ul class="grid md:grid-cols-3 gap-4">
                    <li v-for="item in calls" :key="item.id">
                        <CardDashboard
                            :label="item.label"
                            :count="item.count"
                            :color="item.color"
                        />
                    </li>
                </ul>
            </div>

            <div>
                <h2 class="text-[#0D0D0D] text-[13px] font-krub-bold mb-3">PDS</h2>
                <ul class="grid md:grid-cols-3 gap-4">
                    <li v-for="item in activities" :key="item.id">
                        <CardDashboard
                            :label="item.label"
                            :count="item.count"
                            :color="item.color"
                        />
                    </li>
                </ul>
            </div>
        </div>

        <!-- <div class="pt-[30px]">
            <div class="shadow-md border rounded-lg bg-white px-4 py-3 pb-2 h-full flex flex-col items-center justify-center">
                <p class="text-[32px] font-krub-bold text-[#404040]">{{ callInProgress }}</p>
                <p class="text-[13px] font-krub-semibold text-[#0D0D0D]">Call In Progress</p>
            </div>
        </div> -->
    </div>

    <TableMonitoring />
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import CardDashboard from '../CardDashboard.vue';
import TableMonitoring from './TableMonitoring.vue';
import axios from 'axios';
import { numberFormat } from '@/Plugins/Function/global-function';



const callInProgress = ref<string | number>(0)

const calls = ref([
    {
        id: 1,
        label: "Total",
        color: "#4280EF",
        count: "0",
    },
    {
        id: 2,
        label: "Answered",
        color: "#EB9813",
        count: "0",
    },
    {
        id: 3,
        label: "Answered Rate",
        color: "#677AD6",
        count: "0",
    }
]);

const activities = ref([
    {
        id: 1,
        label: "Active",
        color: "#4280EF",
        count: "0",
    },
    {
        id: 2,
        label: "Paused",
        color: "#EB9813",
        count: "0",
    },
    {
        id: 3,
        label: "Recently Finished",
        color: "#677AD6",
        count: "0",
    }
])

const loading = ref(false)

const fetchData = () => {
    loading.value = true
    axios.get(route('pds.monitoring.data'))
    .then((res: any) => {
        const sessions = res.data.sessions
        const progress = res.data.progress
        const dialer = res.data.dialer

        calls.value[0].count = numberFormat(sessions.DataDialed)
        calls.value[1].count = numberFormat(sessions.DialAgentAnswered)
        calls.value[2].count = sessions.AnsweredRate
        callInProgress.value = numberFormat(progress.DataInProgress)

        activities.value[0].count = numberFormat(dialer.Active)
        activities.value[1].count = numberFormat(dialer.Paused)
        activities.value[2].count = numberFormat(dialer.Finished)
    })
    .finally(() => {
        loading.value = false
    })
}

onMounted(() => {
    fetchData()
})

</script>
