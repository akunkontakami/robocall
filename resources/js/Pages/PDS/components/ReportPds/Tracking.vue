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

                <FilterTracking :filter="filter" @filterData="filterData" />
            </div>
        </div>
        <Table :columns="columns" :paginate="paginate" :hide-th="true">
            <template #thead>
                <tr class="bg-[#F4F6FA]">
                    <Th rowspan="2">PDS Name</Th>
                    <Th rowspan="2">Marketing Campaign</Th>
                    <Th rowspan="2">Agent Ready</Th>
                    <Th rowspan="2">Data Size PDS</Th>
                    <Th colspan="4" class="text-center border-x">Utilize PDS</Th>
                    <Th colspan="2" class="text-center border-x">Unutilize PDS</Th>
                    <Th rowspan="2">Duration</Th>
                    <Th colspan="3" class="text-center border-x">Contacted</Th>
                    <Th colspan="3" class="text-center border-x">UnContacted</Th>
                    <Th colspan="2" class="text-center border-x">Abandon</Th>
                    <Th colspan="4" class="text-center border-x">Call Status</Th>
                </tr>
                <tr class="bg-[#F4F6FA]">
                    <Th class="border-l">Data</Th>
                    <Th>Calls</Th>
                    <Th>Call Ratio</Th>
                    <Th class="border-r">% Utilize</Th>

                    <Th class="border-l">Data</Th>
                    <Th class="border-r">% Unutilize</Th>

                    <Th class="border-l">Data</Th>
                    <Th>Calls</Th>
                    <Th class="border-r">%</Th>

                    <Th class="border-l">Data</Th>
                    <Th>Calls</Th>
                    <Th class="border-r">%</Th>

                    <Th class="border-l">Data</Th>
                    <Th class="border-r">%</Th>


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
                    {{ row.calls }}
                </Td>
                <Td>
                    {{ row.utilize_call_ratio }}
                </Td>
                <Td>
                    {{ row.utilize_percentage }}
                </Td>
                <Td>
                    {{ row.unutilize }}
                </Td>
                <Td>
                    {{ row.unutilize_percentage }}
                </Td>
                <Td>
                    {{ row.duration_pds }}
                </Td>
                <Td>
                    {{ row.contacted }}
                </Td>
                <Td>
                    {{ row.contacted }}
                </Td>
                <Td>
                    {{ row.contacted_percentage }}
                </Td>
                <Td>
                    {{ row.uncontacted }}
                </Td>
                <Td>
                    {{ row.uncontacted }}
                </Td>
                <Td>
                    {{ row.uncontacted_percentage }}
                </Td>
                <Td>
                    {{ row.abandoned }}
                </Td>
                <Td>
                    {{ row.abandoned_rate }}
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
import FilterTracking from "./FilterTracking.vue";
import { closeFilter, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";
import Th from "@/Components/Table/Th.vue";

const columns = ref([
    "PDS Name",
    "Marketing Campaign",
    "Agent Ready",
    "Data Size PDS",
    "Data",
    "Calls",
    "Call Ratio",
    "% Utilize",
    "Data",
    "% Unutilize",
    "Duration",
    "Data",
    "%",
    "Calls",
    "%",
    "Data",
    "%",
    "Calls",
    "%",
    "Data",
    "%",
    "Calls",
    "%",
    "Still Thinking",
    "Disagree",
    "Incoming",
    "Callback",
]);

const filter = ref({
    created_start: "",
    created_end: "",
    campaigns: [],
    pds: [],
});

const paginate = usePaginate({
    route: route('pds.report.tracking-datatable'),
});

const filterData = () => {
    const param = filter.value;
    if (
        !param.created_start || !param.created_end
    ) {
        showAlert("Please select created date");
        return;
    }

    if (validateGreaterDateRange(param.created_start, param.created_end)) {
        var filterParam: any = {
            "filter[created_start]": param.created_start || "",
            "filter[created_end]": param.created_end || "",
            "tab": 'tracking'
        };

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }

};

const exportData = () => {
    window.open(route('pds.report.tracking-export'))
}
</script>
