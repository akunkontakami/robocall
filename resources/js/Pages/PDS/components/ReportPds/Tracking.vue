<template>
    <div x-data="{filter: false}">
        <div class="flex justify-between">
            <TableSearch />
            <div class="flex gap-2">
                <ButtonOutlineGrey class="ms-auto mb-3" icon="isax icon-setting-4" x-on:click="filter=true">
                    Filter
                </ButtonOutlineGrey>
                <ButtonOutlineGreen class="ms-auto mb-3">
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
                    <Th colspan="4" class="text-center border-x">Contacted</Th>
                    <Th colspan="4" class="text-center border-x">UnContacted</Th>
                    <Th colspan="4" class="text-center border-x">Abandon</Th>
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
                    <Th>%</Th>
                    <Th>Calls</Th>
                    <Th class="border-r">%</Th>

                    <Th class="border-l">Data</Th>
                    <Th>%</Th>
                    <Th>Calls</Th>
                    <Th class="border-r">%</Th>

                    <Th class="border-l">Data</Th>
                    <Th>%</Th>
                    <Th>Calls</Th>
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
                    PDS-{{ i }}
                </Td>
                <Td>
                    MC-A
                </Td>
                <Td>
                    2
                </Td>
                <Td>
                    700
                </Td>
                <Td>
                    500
                </Td>
                <Td>
                    500
                </Td>
                <Td>
                    1,2
                </Td>
                <Td>
                    12%
                </Td>
                <Td>
                    20
                </Td>
                <Td>
                    70%
                </Td>
                <Td>
                    02:15:30
                </Td>
                <Td>
                    70
                </Td>
                <Td>
                    70%
                </Td>
                <Td>
                    10
                </Td>
                <Td>
                    13%
                </Td>
                <Td>
                    70
                </Td>
                <Td>
                    70%
                </Td>
                <Td>
                    10
                </Td>
                <Td>
                    13%
                </Td>
                <Td>
                    70
                </Td>
                <Td>
                    70%
                </Td>
                <Td>
                    10
                </Td>
                <Td>
                    13%
                </Td>
                <Td>
                    70
                </Td>
                <Td>
                    70%
                </Td>
                <Td>
                    10
                </Td>
                <Td>
                    13%
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
    route: route('dummy'),
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
</script>
