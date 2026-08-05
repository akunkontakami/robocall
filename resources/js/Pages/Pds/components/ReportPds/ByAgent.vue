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

                <FilterByAgent :filter="filter" @filterData="filterData" :campaigns="campaigns" :spv="spv" :agents="agents" :pds="pds" />
            </div>
        </div>
        <Table :columns="columns" :paginate="paginate" :hide-th="true">
            <template #thead>
                <tr class="bg-[#F4F6FA]">
                    <Th rowspan="2">SessionStart</Th>
                    <Th rowspan="2">SessionEnd</Th>
                    <Th rowspan="2">Deskcoll</Th>
                    <Th rowspan="2">Data Contacted</Th>
                    <Th v-if="visibleOutbounds.length" :colspan="visibleOutbounds.length" class="text-center border-x">
                        Call Status
                    </Th>
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

            <template v-for="group in groupedRows" :key="group.key">
                <tr class="bg-[#F4F6FA]">
                    <Td :colspan="columns.length" class="font-semibold text-[14px]">
                        {{ group.title }}
                    </Td>
                </tr>

                <tr v-for="(row, index) in group.rows" :key="`${group.key}-${row.id}-${index}`">
                    <Td v-if="index === 0" :rowspan="group.rowspan" class="align-middle">
                        {{ row.session_start ?? '-' }}
                    </Td>
                    <Td v-if="index === 0" :rowspan="group.rowspan" class="align-middle">
                        {{ row.session_end ?? '-' }}
                    </Td>
                    <Td>
                        {{ row.agent }}
                    </Td>
                    <Td>
                        {{ row.data_utilize ?? 0 }}
                    </Td>
                    <Td
                        v-for="outbound in visibleOutbounds"
                        :key="`${group.key}-${row.id}-${outbound}`"
                    >
                        {{ row.ticket_status?.[outbound] ?? 0 }}
                    </Td>
                </tr>
            </template>
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
import FilterByAgent from "./FilterByAgent.vue";
import { closeFilter, getArrayParamsFromUrl, getQueryParam, removeAllUrlParameter, routeAppendParam, showAlert, validateGreaterDateRange } from "@/Plugins/Function/global-function";
import Th from "@/Components/Table/Th.vue";

const props = defineProps(["campaigns", "spv", "agents", "pds", "outbounds"]);

const filter = ref({
    created_start: getQueryParam("created_start"),
    created_end: getQueryParam("created_end"),
    campaigns: getArrayParamsFromUrl("filter[campaigns]"),
    pds: getArrayParamsFromUrl("filter[pds]"),
    spv: getArrayParamsFromUrl("filter[spv]"),
    agent: getArrayParamsFromUrl("filter[agent]"),
});

const paginate = usePaginate({
    route: route('pds.report.agent-datatable'),
});

const visibleOutbounds = computed(() => {
    const rows = paginate.data.value ?? [];

    return (props.outbounds ?? []).filter((outbound: string) =>
        rows.some((row: any) => Number(row.ticket_status?.[outbound] ?? 0) !== 0)
    );
});

const columns = computed(() => [
    "SessionStart",
    "SessionEnd",
    "Deskcoll",
    "Data Contacted",
    ...visibleOutbounds.value,
]);

const groupedRows = computed(() => {
    const groups: Array<any> = [];
    const groupMap = new Map<string, any>();

    (paginate.data.value ?? []).forEach((row: any) => {
        const groupKey = `${row.name ?? '-'}__${row.spv ?? '-'}`;

        if (!groupMap.has(groupKey)) {
            const group = {
                key: groupKey,
                title: `${row.name ?? '-'} - ${row.spv ?? '-'}`,
                rows: [],
                rowspan: 0,
            };

            groupMap.set(groupKey, group);
            groups.push(group);
        }

        groupMap.get(groupKey).rows.push(row);
    });

    return groups.map((group) => ({
        ...group,
        rowspan: group.rows.length || 1,
    }));
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
            "created_start": param.created_start || "",
            "created_end": param.created_end || "",
            "tab": 'agent'
        };

        param.pds.forEach((id, index) => {
            filterParam[`filter[pds][${index}]`] = id;
        });

        param.campaigns.forEach((id, index) => {
            filterParam[`filter[campaigns][${index}]`] = id;
        });

        param.spv.forEach((id, index) => {
            filterParam[`filter[spv][${index}]`] = id;
        });

        param.agent.forEach((id, index) => {
            filterParam[`filter[agent][${index}]`] = id;
        });

        removeAllUrlParameter();
        routeAppendParam(filterParam, false);
        closeFilter();
    }
};

const exportData = () => {
    window.open(
        route('pds.report.agent-export') + window.location.search
    );
};
</script>
