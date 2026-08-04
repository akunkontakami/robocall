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

                <FilterByCampaign :filter="filter" @filterData="filterData" :campaigns="campaigns" :spv="spv" :agents="agents" :pds="pds" />
            </div>
        </div>
        <Table :columns="columns" :paginate="paginate" :hide-th="true">
            <template #thead>
                <tr class="bg-[#F4F6FA]">
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">PDS Name</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">SessionStart</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">SessionEnd</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Agent Ready</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Data Size</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Data Utilize</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Data Unutilize</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Attempt</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Contacted</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Uncontacted</Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Abandon</Th>
                    <Th
                        v-if="visibleOutbounds.length"
                        :colspan="visibleOutbounds.length"
                        class="text-center border-x"
                    >
                        Contract
                    </Th>
                    <Th :rowspan="visibleOutbounds.length ? 2 : 1">Duration PDS</Th>
                </tr>
                <tr v-if="visibleOutbounds.length" class="bg-[#F4F6FA]">
                    <Th
                        v-for="(outbound, i) in visibleOutbounds"
                        :key="outbound"
                        :class="{
                            'border-l': i == 0,
                            'border-r': i + 1 == visibleOutbounds.length
                        }"
                    >
                        {{ outbound }}
                    </Th>
                </tr>
            </template>

            <tr
                v-for="(row, i) in paginate.data.value"
                :key="i"
            >
                <Td>{{ row.campaign }}</Td>
                <Td>{{ row.session_start }}</Td>
                <Td>{{ row.session_end }}</Td>
                <Td>{{ row.total_agent }}</Td>
                <Td>{{ row.data_size }}</Td>
                <Td>{{ row.data_utilize }}</Td>
                <Td>{{ row.data_unutilize }}</Td>
                <Td>{{ row.attempt }}</Td>
                <Td>{{ row.contacted }}</Td>
                <Td>{{ getUncontactedValue(row) }}</Td>
                <Td>{{ row.abandoned }}</Td>
                <Td v-for="(outbound, i) in visibleOutbounds" :key="outbound">
                    {{ row.ticket_status?.[outbound] ?? 0 }}
                </Td>
                <Td>{{ getDurationPdsValue(row) }}</Td>
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
import { computed, ref } from "vue";
import FilterByCampaign from "./FilterByCampaign.vue";
import { closeFilter, getArrayParamsFromUrl, getQueryParam, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";
import Th from "@/Components/Table/Th.vue";

const props = defineProps(["campaigns", "spv", "agents", "pds", "outbounds"])

const filter = ref({
    created_start: getQueryParam("created_start"),
    created_end: getQueryParam("created_end"),
    campaigns: getArrayParamsFromUrl("filter[campaigns]"),
    pds: getArrayParamsFromUrl("filter[pds]"),
});

const paginate = usePaginate({
    route: route('pds.report.campaign-datatable'),
});

const CONTACTED_STATUSES = [
    "Promised to Pay (PTP)",
    "Call Back",
    "Visit Request - Contacted",
    "BP Partial",
    "NBP-A",
    "NBP-B (Salah Sambung)",
    "NBP-C (Invalid Number)",
    "Paid in Confins",
];

const parseSessionDate = (value?: string | null) => {
    if (!value) {
        return null;
    }

    const normalizedValue = value.includes("T") ? value : value.replace(" ", "T");
    const date = new Date(normalizedValue);

    return Number.isNaN(date.getTime()) ? null : date;
};

const formatDuration = (totalSeconds: number) => {
    const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, "0");
    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, "0");
    const seconds = String(totalSeconds % 60).padStart(2, "0");

    return `${hours}:${minutes}:${seconds}`;
};

const getContactedValue = (row: any) => {
    return CONTACTED_STATUSES.reduce((total, status) => {
        return total + Number(row.ticket_status?.[status] ?? 0);
    }, 0);
};

const getUncontactedValue = (row: any) => {
    const dataUtilize = Number(row.data_utilize ?? 0);
    const contacted = Number(row.contacted ?? 0);
    const abandoned = Number(row.abandoned ?? 0);

    return Math.max(0, dataUtilize - contacted - abandoned);
};

const getDurationPdsValue = (row: any) => {
    const sessionStart = parseSessionDate(row.session_start);
    const sessionEnd = parseSessionDate(row.session_end);

    if (!sessionStart || !sessionEnd) {
        return row.duration_pds ?? "00:00:00";
    }

    const diffInSeconds = Math.max(
        0,
        Math.floor((sessionEnd.getTime() - sessionStart.getTime()) / 1000)
    );

    return formatDuration(diffInSeconds);
};

const visibleOutbounds = computed(() => {
    const rows = paginate.data.value ?? [];

    return (props.outbounds ?? []).filter((outbound: string) =>
        rows.some((row: any) => Number(row.ticket_status?.[outbound] ?? 0) !== 0)
    );
});

const columns = computed(() => [
    "PDS Name",
    "SessionStart",
    "SessionEnd",
    "Agent Ready",
    "Data Size",
    "Data Utilize",
    "Data Unutilize",
    "Attempt",
    "Contacted",
    "Uncontacted",
    "Abandon",
    ...visibleOutbounds.value,
    "Duration PDS",
]);

const filterData = () => {
    const param = filter.value;
    if (!param.created_start || !param.created_end) {
        showAlert("Please select date");
        return;
    }

    if (validateGreaterDateRange(param.created_start, param.created_end)) {
        var filterParam: any = {
            "created_start": param.created_start || "",
            "created_end": param.created_end || "",
            "tab": 'campaign'
        };

        param.pds.forEach((id, index) => {
            filterParam[`filter[pds][${index}]`] = id;
        });

        param.campaigns.forEach((id, index) => {
            filterParam[`filter[campaigns][${index}]`] = id;
        });

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }
};

const exportData = () => {
    window.open(
        route('pds.report.campaign-export') + window.location.search
    )
}
</script>
