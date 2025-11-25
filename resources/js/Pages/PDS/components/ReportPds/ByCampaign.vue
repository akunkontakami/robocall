<template>
    <div x-data="{filter: false}">
        <div class="flex justify-between">
            <TableSearch />
            <div class="flex gap-2">
                <ButtonOutlineGrey class="ms-auto mb-3" icon="isax icon-setting-4" x-on:click="filter=true">
                    Filter
                </ButtonOutlineGrey>
                <ButtonOutlineGreen class="ms-auto mb-3" @click="exportData">
                    Export Data
                </ButtonOutlineGreen>

                <FilterByCampaign :filter="filter" @filterData="filterData" />
            </div>
        </div>
        <Table :columns="columns" :paginate="paginate" :hide-th="true">
            <template #thead>
                <tr class="bg-[#F4F6FA]">
                    <Th rowspan="2">PDS Name</Th>
                    <Th rowspan="2">Marketing Campaign</Th>
                    <Th rowspan="2">Agent Ready</Th>
                    <Th rowspan="2">Data Size</Th>
                    <Th rowspan="2">Data Utilize</Th>
                    <Th colspan="4" class="text-center border-x">Data Contacted</Th>
                    <Th rowspan="2">Uncontacted</Th>
                    <Th rowspan="2">Abandon</Th>
                    <Th rowspan="2">Unutilize PDS</Th>
                    <Th rowspan="2">Duration PDS</Th>
                </tr>
                <tr class="bg-[#F4F6FA]">
                    <Th class="border-l">Still Thinking</Th>
                    <Th>Disagree</Th>
                    <Th>Incoming</Th>
                    <Th class="border-r">Callback</Th>
                </tr>
            </template>

            <tr
                v-for="(row, i) in paginate.data.value"
            >
                <Td>
                    {{ row.name }}
                </Td>
                <Td>
                    {{ row.campaign }}
                </Td>
                <Td>
                    {{ row.total_agent }}
                </Td>
                <Td>
                    {{ row.data_size }}
                </Td>
                <Td>
                    {{ row.data_utilize }}
                </Td>
                <Td>
                    {{ row.still_thinking }}
                </Td>
                <Td>
                    {{ row.disagree }}
                </Td>
                <Td>
                    {{ row.incoming }}
                </Td>
                <Td>
                    {{ row.callback }}
                </Td>
                <Td>
                    {{ row.uncontacted }}
                </Td>
                <Td>
                    {{ row.abandoned }}
                </Td>
                <Td>
                    {{ row.unutilize }}
                </Td>
                <Td>
                    {{ row.duration_pds }}
                </Td>
            </tr>
        </Table>
    </div>
</template>
<script setup lang="ts">
import ButtonOutlineGreen from "@/Components/Button/ButtonOutlineGreen.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import Table from "@/Components/Table/Table.vue";
import TableSearch from "@/Components/Table/TableSearch.vue";
import Td from "@/Components/Table/Td.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { ref, onBeforeUnmount, onMounted } from "vue";
import FilterByCampaign from "./FilterByCampaign.vue";
import { closeFilter, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";
import Th from "@/Components/Table/Th.vue";

const columns = ref([
    "PDS Name",
    "Marketing Campaign",
    "Agent Ready",
    "Data Size",
    "Data Utilize",
    "Still Thinking",
    "Disagree",
    "Incoming",
    "Callback",
    "Uncontacted",
    "Abandon",
    "Unutilize PDS",
    "Duration PDS",
]);

const filter = ref({
    created_start: "",
    created_end: "",
    campaigns: [],
    pds: []
});

const paginate = usePaginate({
    route: route('pds.report.campaign-datatable'),
});

const filterData = () => {
    const param = filter.value;
    if (
        !param.created_start || !param.created_end
    ) {
        showAlert("Please select date");
        return;
    }

    if (validateGreaterDateRange(param.created_start, param.created_end)) {
        var filterParam: any = {
            "filter[created_start]": param.created_start || "",
            "filter[created_end]": param.created_end || "",
            "tab": 'campaign'
        };

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }

};

const exportData = () => {
    window.open(route('pds.report.campaign-export'))
}
</script>
