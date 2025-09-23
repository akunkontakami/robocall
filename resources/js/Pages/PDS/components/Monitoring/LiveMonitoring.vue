<template>
    <div class="grid lg:grid-cols-5 gap-6 mb-8" >
        <div class="lg:col-span-4 flex flex-col gap-4">
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
                    <li v-for="item in pds" :key="item.id">
                        <CardDashboard
                            :label="item.label"
                            :count="item.count"
                            :color="item.color"
                        />
                    </li>
                </ul>
            </div>
        </div>

        <div class="pt-[30px]">
            <div class="shadow-md border rounded-lg bg-white px-4 py-3 pb-2 h-full flex flex-col items-center justify-center">
                <p class="text-[32px] font-krub-bold text-[#404040]">{{ callInProgress }}</p>
                <p class="text-[13px] font-krub-semibold text-[#0D0D0D]">Call In Progress</p>
            </div>
        </div>
    </div>

    <TableMonitoring />
</template>
<script setup lang="ts">
import { ref } from 'vue';
import CardDashboard from '../CardDashboard.vue';
import TableMonitoring from './TableMonitoring.vue';



const callInProgress = ref(0)

const calls = ref([
    {
        id: 1,
        label: "Total",
        color: "#4280EF",
        count: "10.000",
    },
    {
        id: 2,
        label: "Answered",
        color: "#EB9813",
        count: "250",
    },
    {
        id: 3,
        label: "Answered Rate",
        color: "#677AD6",
        count: "2.5%",
    }
]);

const pds = ref([
    {
        id: 1,
        label: "Active",
        color: "#4280EF",
        count: "2",
    },
    {
        id: 2,
        label: "Paused",
        color: "#EB9813",
        count: "1",
    },
    {
        id: 3,
        label: "Recently Finished",
        color: "#677AD6",
        count: "1",
    }
]);

</script>
