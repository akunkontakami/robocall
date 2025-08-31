<template>
    <AppLayout title="PDS" header="PDS">
        <template v-slot:tab>
            <TabMenu tab="monitoring" />
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


        <LiveMonitoring v-if="tabActive == 'live-monitoring'" />
        <PdsHistory v-if="tabActive == 'pds-history'" />
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import { ref } from "vue";
import { getQueryParam, removeAllUrlParameter, routeAppendParam } from "@/Plugins/Function/global-function";
import LiveMonitoring from "./components/Monitoring/LiveMonitoring.vue";
import PdsHistory from "./components/Monitoring/PdsHistory.vue";

const tabActive = ref(getQueryParam('tab') || 'live-monitoring')

const tabs = ref([
    {
        name: 'Live Monitoring',
        key: 'live-monitoring'
    },
    {
        name: 'PDS History',
        key: 'pds-history'
    },
])

const changeTab = (tab: any) => {
    tabActive.value = tab.key
    removeAllUrlParameter()
    routeAppendParam({ tab: tab.key }, false);
}

</script>
