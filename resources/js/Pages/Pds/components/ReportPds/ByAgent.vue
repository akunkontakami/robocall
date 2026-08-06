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
                        {{ row.start_date ? `${row.start_date} ${row.start_time ?? ''}` : (row.session_start ?? '-') }}
                    </Td>
                    <Td v-if="index === 0" :rowspan="group.rowspan" class="align-middle">
                        {{ row.end_date ? `${row.end_date} ${row.end_time ?? ''}` : (row.session_end ?? '-') }}
                    </Td>
                    <Td>
                        {{ row.agent ?? '-' }}
                    </Td>
                    <Td>
                        {{ Number(row.data_utilize ?? row.data_contacted ?? 0) }}
                    </Td>
                    <Td
                        v-for="outbound in visibleOutbounds"
                        :key="`${group.key}-${row.id}-${outbound}`"
                    >
                        {{ findStatusValue(row, outbound) }}
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

const keyNormalize = (s: string) => String(s ?? '').toLowerCase().replace(/[\s\-_()]/g, '');

const EXCLUDED_STATUSES = new Set([
    'visitrequestcontacted',
    'visitrequest',
    'contacted',
    'vr',
    'visitrequestcontacted',
]);

const CALL_STATUS_ALIASES: Record<string, string[]> = {
    'PTP': ['Promised to Pay (PTP)', 'Promised to Pay', 'PTP'],
    'CallBack': ['Call Back', 'Callback', 'CallBack', 'CALL BACK'],
    'BPPartial': ['BP Partial', 'Bp Partial', 'BPPartial'],
    'NBPA': ['NBP-A', 'NBP A', 'NBPA'],
    'NBPB': ['NBP-B (Salah Sambung)', 'NBP-B', 'NBP B', 'NBPB', 'Salah Sambung'],
    'NBPC': ['NBP-C (Invalid Number)', 'NBP-C', 'NBP C', 'NBPC', 'Invalid Number'],
    'PaidinConfins': ['Paid in Confins', 'Paid In Confins', 'PaidinConfins'],
};

const CALL_STATUS_ORDER = ['PTP', 'CallBack', 'BPPartial', 'NBPA', 'NBPB', 'NBPC', 'PaidinConfins'];

const findStatusValue = (row: any, statusName: string): number => {
    if (!row || !row.ticket_status) return 0;
    const direct = row.ticket_status[statusName];
    if (direct !== undefined && direct !== null) return Number(direct) || 0;
    const target = keyNormalize(statusName);
    for (const key of Object.keys(row.ticket_status)) {
        if (keyNormalize(key) === target) {
            const v = row.ticket_status[key];
            if (v !== undefined && v !== null) return Number(v) || 0;
        }
    }
    return 0;
};

const propsOutboundNames = computed(() =>
    (props.outbounds ?? []).map((outbound: any) =>
        typeof outbound === "string" ? outbound : outbound?.name
    ).filter(Boolean)
);

const filteredPropsOutboundNames = computed(() => {
    const names: string[] = [];
    propsOutboundNames.value.forEach((name: string) => {
        const kn = keyNormalize(name);
        if (EXCLUDED_STATUSES.has(kn)) return;
        for (const aliasList of Object.values(CALL_STATUS_ALIASES)) {
            for (const variant of aliasList) {
                if (keyNormalize(variant) === kn) {
                    names.push(name);
                    return;
                }
            }
        }
    });
    return names;
});

const visibleOutbounds = computed(() => {
    const filtered = filteredPropsOutboundNames.value;

    if (filtered && filtered.length) {
        return filtered;
    }

    const firstRow = (paginate.data.value ?? [])[0];
    if (firstRow && firstRow.ticket_status) {
        return Object.keys(firstRow.ticket_status);
    }
    return CALL_STATUS_ORDER;
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
