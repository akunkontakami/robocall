<template>
    <div x-data="{filter: false}">
        <div class="flex justify-between">
            <TableSearch />
            <div class="flex gap-2">
                <ButtonOutlineGrey class="ms-auto mb-3" icon="isax icon-setting-4" x-on:click="filter=true">
                    Filter
                </ButtonOutlineGrey>
                <ButtonOutlineGreen
                    class="ms-auto mb-3"
                    @click="exportData"
                >
                    Export Data
                </ButtonOutlineGreen>

                <FilterPds :filter="filter" @filterData="filterData" />
            </div>
        </div>
        <Table :columns="columns" :paginate="paginate">
            <tr
                v-for="(row, i) in paginate.data.value"
            >
                <Td>
                    {{ row.name }}
                </Td>
                <Td>
                    {{ row.total_agent }}
                </Td>
                <Td>
                    {{ row.SessionStart }}
                </Td>
                <Td>
                    {{ row.SessionEnd }}
                </Td>
                <Td>
                    {{ row.DataSize }}
                </Td>
                <Td>
                    {{ row.data_utilize }}
                </Td>
                <Td>
                    {{ row.calls }}
                </Td>
                <Td>
                    {{ row.DialContacted }}
                </Td>
                <Td>
                    {{ row.DialUnContacted }}
                </Td>
                <Td>
                    {{ row.DialAbandon }}
                </Td>
                <Td>
                    {{ row.DialAbandonRate }}
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
import FilterPds from "./FilterPds.vue";
import { closeFilter, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";

const columns = ref([
    "PDS Name",
    "Agent Ready",
    "Start Time",
    "End Time",
    "Data Size PDS",
    "Data Utilize",
    "Calls",
    "Call Contacted",
    "Call UnContacted",
    "Call Abandon",
    "Abandon Rate",
]);

const filter = ref({
    created_start: "",
    created_end: ""
});

const paginate = usePaginate({
    route: route('pds.monitoring.history-datatable'),
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
            "tab": 'pds-history'
        };

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }
}

const exportData = () => {
    window.open(route('pds.monitoring.history-export'))
}
</script>
