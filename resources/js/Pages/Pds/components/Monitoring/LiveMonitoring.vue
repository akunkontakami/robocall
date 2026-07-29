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

    <TableMonitoring :paginate="paginate" />
</template>
<script setup lang="ts">
import { ref } from 'vue';
import CardDashboard from '../CardDashboard.vue';
import TableMonitoring from './TableMonitoring.vue';
import { numberFormat } from '@/Plugins/Function/global-function';
import { usePaginate } from '@/Plugins/Hooks/usePaginate';

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

const toNumber = (value: unknown) => {
    const parsed = Number(value ?? 0);

    return Number.isNaN(parsed) ? 0 : parsed;
}

const formatRate = (answered: number, total: number) => {
    if (!total) {
        return '0%';
    }

    return `${((answered / total) * 100).toFixed(2)}%`;
}

const countUniqueCampaigns = (rows: any[], matcher?: (status: string) => boolean) => {
    const campaigns = rows.reduce((items: Set<string>, row: any) => {
        const campaignId = row.campaign_id;
        const status = String(row.SessionStatus ?? '').toLowerCase();

        if (campaignId === null || campaignId === undefined || campaignId === '') {
            return items;
        }

        if (!matcher || matcher(status)) {
            items.add(String(campaignId));
        }

        return items;
    }, new Set<string>());

    return campaigns.size;
}

const updateSummary = (result: any) => {
    const rows = result?.data ?? [];

    const totalDialed = rows.reduce((total: number, row: any) => total + toNumber(row.DataDialed), 0);
    const totalContacted = rows.reduce((total: number, row: any) => total + toNumber(row.DialContacted), 0);
    const activeCount = countUniqueCampaigns(rows);
    const pausedCount = countUniqueCampaigns(rows, (status) => status.includes('pause'));
    const finishedCount = countUniqueCampaigns(rows, (status) => status.includes('finish') || status.includes('completed'));

    calls.value[0].count = numberFormat(totalDialed);
    calls.value[1].count = numberFormat(totalContacted);
    calls.value[2].count = formatRate(totalContacted, totalDialed);

    activities.value[0].count = numberFormat(activeCount);
    activities.value[1].count = numberFormat(pausedCount);
    activities.value[2].count = numberFormat(finishedCount);
}

const paginate = usePaginate({
    route: route('pds.monitoring.datatable'),
    callback: updateSummary,
})

const loading = paginate.loading
</script>
