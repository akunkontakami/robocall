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
                :key="row.SessionId || i"
            >
                <Td>
                    {{ row.campaign_id || '-' }}
                </Td>
                <Td>
                    {{ numberFormat(row.DialAgentAnswered ?? 0) }}
                </Td>
                <Td>
                    {{ row.SessionStart || '-' }}
                </Td>
                <Td>
                    {{ row.SessionEnd || '-' }}
                </Td>
                <Td>
                    {{ numberFormat(row.DataSize ?? 0) }}
                </Td>
                <Td>
                    {{ numberFormat(row.DataDialed ?? 0) }}
                </Td>
                <Td>
                    {{ numberFormat(row.DialCount ?? 0) }}
                </Td>
                <Td>
                    {{ numberFormat(row.DialContacted ?? 0) }}
                </Td>
                <Td>
                    {{ numberFormat(row.DialFailed ?? 0) }}
                </Td>
                <Td>
                    {{ numberFormat(row.DialAbandoned ?? 0) }}
                </Td>
                <Td>
                    {{ getAbandonRate(row) }}
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
import { ref } from "vue";
import FilterPds from "./FilterPds.vue";
import { closeFilter, getQueryParam, numberFormat, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";

const columns = ref([
    "Campaign ID",
    "Agent Answered",
    "Start Time",
    "End Time",
    "Data Size PDS",
    "Data Dialed",
    "Calls",
    "Call Contacted",
    "Call Failed",
    "Call Abandoned",
    "Abandon Rate",
]);

const filter = ref({
    created_start: getQueryParam("start_date", ""),
    created_end: getQueryParam("end_date", "")
});

const paginate = usePaginate({
    route: route('pds.monitoring.history-datatable'),
});

const getAbandonRate = (row: any) => {
    const dialCount = Number(row?.DialCount || 0);
    const dialAbandoned = Number(row?.DialAbandoned || 0);

    if (!dialCount) {
        return "0%";
    }

    return `${((dialAbandoned / dialCount) * 100).toFixed(2)}%`;
};

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
            start_date: param.created_start || "",
            end_date: param.created_end || "",
            "tab": 'pds-history'
        };

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }
}

const exportData = () => {
    window.open(`${route('pds.monitoring.history-export')}${window.location.search}`)
}
</script>
