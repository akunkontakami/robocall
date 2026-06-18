<template>
    <AppLayout title="Report Robocall" header="Report Robocall">
        <template v-slot:tab>
            <TabMenu tab="report" />
        </template>

        <div x-data="{filter: false}">
            <div class="flex justify-between">
                <TableSearch />
                <div class="flex gap-2">
                    <ButtonOutlineGrey
                        class="ms-auto mb-3"
                        icon="isax icon-setting-4"
                        x-on:click="filter=true"
                    >
                        Filter
                    </ButtonOutlineGrey>
                    <ButtonOutlineGreen
                        class="ms-auto mb-3"
                        @click="exportData"
                    >
                        Export Data
                    </ButtonOutlineGreen>
                </div>
            </div>
            <FilterReportRobocall
                :filter="filter"
                @filterData="filterData"
                :campaigns="campaigns"
            />

            <Table :columns="columns" :paginate="paginate">
                <tr v-for="(row, i) in paginate.data.value">
                    <Td>
                        {{ row.campaign_id }}
                    </Td>
                    <Td>
                        {{ row.customer_number }}
                    </Td>
                    <Td>
                        {{ row.customer_name }}
                    </Td>
                    <Td>
                        {{ row.dialed_number }}
                    </Td>
                    <Td>
                        {{ row.dial_time }}
                    </Td>
                    <Td>
                        {{ row.dial_status }}
                    </Td>
                    <Td>
                        {{ row.call_status }}
                    </Td>
                </tr>
            </Table>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import ButtonOutlineGreen from "@/Components/Button/ButtonOutlineGreen.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import Table from "@/Components/Table/Table.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { ref } from "vue";
import TabMenu from "../Robocall/components/TabMenu.vue";
import TableSearch from "@/Components/Table/TableSearch.vue";
import Td from "@/Components/Table/Td.vue";
import FilterReportRobocall from "./FilterReportRobocall.vue";
import {
    closeFilter,
    getArrayParamsFromUrl,
    getQueryParam,
    removeAllUrlParameter,
    routeAppendParam,
    showAlert,
    validateGreaterDateRange,
} from "@/Plugins/Function/global-function.js";

defineProps(["campaigns"]);

const columns = ref([
    "Marketing Campaign",
    "Customer Number",
    "Name",
    "Phone Number",
    "Call Date",
    "Dial Status",
    "Call Status",
]);

const paginate = usePaginate({
    route: route("robocall.report.datatable"),
});

const filter = ref({
    created_start: getQueryParam("created_start") || "",
    created_end: getQueryParam("created_end") || "",
    campaigns: getArrayParamsFromUrl("filter[campaigns]") || [],
});

const filterData = () => {
    const param = filter.value;
    if (!param.created_start || !param.created_end) {
        showAlert("Please select date");
        return;
    }

    if (validateGreaterDateRange(param.created_start, param.created_end)) {
        var filterParam: any = {
            created_start: param.created_start || "",
            created_end: param.created_end || "",
            tab: "tracking",
        };

        param.campaigns.forEach((id, index) => {
            filterParam[`filter[campaigns][${index}]`] = id;
        });

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }
};

const exportData = () => {
    const params = new URLSearchParams();

    params.append("created_start", filter.value.created_start);
    params.append("created_end", filter.value.created_end);

    filter.value.campaigns.forEach((id, index) => {
        params.append(`filter[campaigns][${index}]`, id);
    });

    window.open(`${route("robocall.report.export")}?${params.toString()}`);
};
</script>
