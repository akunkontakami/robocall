<template>
    <AppLayout title="PDS" header="PDS">
        <template v-slot:tab>
            <TabMenu tab="report" />
        </template>

        <div class="flex gap-4 items-center mb-8">
            <div
                v-for="tab in tabs"
                class="border rounded-full py-[6px] px-3 text-[#6D6D6D] text-xs bg-white cursor-pointer"
                :class="{
                    'font-krub-medium text-white bg-yellow !border-yellow': tab.key == tabActive
                }"
                @click="changeTab(tab)"
            >
                {{ tab.name }}
            </div>
        </div>


        <ByCampaign v-if="tabActive == 'campaign'" />
        <ByAgent v-if="tabActive == 'agent'" />
        <Tracking v-if="tabActive == 'tracking'" />
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import { ref } from "vue";
import { getQueryParam, removeAllUrlParameter, routeAppendParam } from "@/Plugins/Function/global-function";
import ByCampaign from "./components/ReportPds/ByCampaign.vue";
import ByAgent from "./components/ReportPds/ByAgent.vue";
import Tracking from "./components/ReportPds/Tracking.vue";

const tabActive = ref(getQueryParam('tab') || 'campaign')

const tabs = ref([
    {
        name: 'Report By Campaign',
        key: 'campaign'
    },
    {
        name: 'Report By Agent',
        key: 'agent'
    },
    {
        name: 'Tracking Report PDS',
        key: 'tracking'
    },
])

const changeTab = (tab: any) => {
    tabActive.value = tab.key
    removeAllUrlParameter()
    routeAppendParam({ tab: tab.key }, false);
}

</script>
