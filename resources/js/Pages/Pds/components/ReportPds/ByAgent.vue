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
                    <Th rowspan="2">No Status</Th>
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

            <template v-for="dateGroup in groupedByDate" :key="dateGroup.date_key">
                <tr class="bg-[#EEF2FF]">
                    <Td :colspan="columns.length" class="font-bold text-[14px]">
                        Tanggal: {{ dateGroup.date_label }}
                    </Td>
                </tr>

                <template v-for="pdsGroup in dateGroup.pds_groups" :key="`${dateGroup.date_key}-${pdsGroup.key}`">
                    <tr class="bg-[#F4F6FA]">
                        <Td :colspan="columns.length" class="font-semibold text-[14px]">
                            {{ pdsGroup.title }}
                        </Td>
                    </tr>

                    <template
                        v-for="sessGroup in pdsGroup.session_groups"
                        :key="`${dateGroup.date_key}-${pdsGroup.key}-${sessGroup.session_key}`"
                    >
                        <tr v-for="(row, index) in sessGroup.rows" :key="`${dateGroup.date_key}-${pdsGroup.key}-${sessGroup.session_key}-${row.id}-${index}`">
                            <Td v-if="index === 0" :rowspan="sessGroup.rowspan" class="align-middle">
                                {{ sessGroup.session_start }}
                            </Td>
                            <Td v-if="index === 0" :rowspan="sessGroup.rowspan" class="align-middle">
                                {{ sessGroup.session_end }}
                            </Td>
                            <Td>
                                {{ row.agent ?? '-' }}
                            </Td>
                            <Td>
                                {{ Number(row.data_utilize ?? row.data_contacted ?? 0) }}
                            </Td>
                            <Td
                                v-for="outbound in visibleOutbounds"
                                :key="`${dateGroup.date_key}-${pdsGroup.key}-${sessGroup.session_key}-${row.id}-${outbound}`"
                            >
                                {{ findStatusValue(row, outbound) }}
                            </Td>
                            <Td>{{ findStatusValue(row, 'No Status') }}</Td>
                        </tr>
                    </template>
                </template>
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
    'contacted',
]);

const CALL_STATUS_ALIASES: Record<string, string[]> = {
    'PTP': ['Promised to Pay (PTP)', 'Promised to Pay', 'PTP'],
    'CallBack': ['Call Back', 'Callback', 'CallBack', 'CALL BACK'],
    'BPPartial': ['BP Partial', 'Bp Partial', 'BPPartial', 'Hold Date'],
    'NBPA': ['NBP-A', 'NBP A', 'NBPA'],
    'NBPB': ['NBP-B (Salah Sambung)', 'NBP-B', 'NBP B', 'NBPB', 'Salah Sambung'],
    'NBPC': ['NBP-C (Invalid Number)', 'NBP-C', 'NBP C', 'NBPC', 'Invalid Number'],
    'PaidinConfins': ['Paid in Confins', 'Paid In Confins', 'PaidinConfins'],
    'KP': ['KP', 'Kp', 'kp'],
    'VisitRequest': ['Visit Request', 'VisitRequest', 'VR', 'visit request'],
};

const CALL_STATUS_ORDER = ['PTP', 'CallBack', 'BPPartial', 'NBPA', 'NBPB', 'NBPC', 'PaidinConfins', 'KP', 'VisitRequest'];

const findStatusValue = (row: any, statusName: string): number => {
    if (!row) return 0;
    const target = keyNormalize(statusName);

    if (target === keynormalize_NoStatus) {
        if (row.ticket_status && row.ticket_status['No Status'] !== undefined) return Number(row.ticket_status['No Status']) || 0;
        if (row.NoStatus !== undefined) return Number(row.NoStatus) || 0;
        if (row.no_status !== undefined) return Number(row.no_status) || 0;
        return 0;
    }
    if (target === keyNormalize('KP')) {
        if (row.ticket_status && row.ticket_status['KP'] !== undefined) return Number(row.ticket_status['KP']) || 0;
        if (row.KP !== undefined) return Number(row.KP) || 0;
    }
    if (target === keyNormalize('Visit Request')) {
        if (row.ticket_status && row.ticket_status['Visit Request'] !== undefined) return Number(row.ticket_status['Visit Request']) || 0;
        if (row.VisitRequest !== undefined) return Number(row.VisitRequest) || 0;
    }

    if (row.ticket_status) {
        const direct = row.ticket_status[statusName];
        if (direct !== undefined && direct !== null) return Number(direct) || 0;
        for (const key of Object.keys(row.ticket_status)) {
            if (keyNormalize(key) === target) {
                const v = row.ticket_status[key];
                if (v !== undefined && v !== null) return Number(v) || 0;
            }
        }
        for (const [aliasName, variants] of Object.entries(CALL_STATUS_ALIASES)) {
            for (const variant of variants) {
                if (keyNormalize(variant) === target || keyNormalize(aliasName) === target) {
                    const keyInTs = Object.keys(row.ticket_status).find(k =>
                        keyNormalize(k) === keyNormalize(variant) || keyNormalize(k) === keyNormalize(aliasName)
                    );
                    if (keyInTs !== undefined) {
                        return Number(row.ticket_status[keyInTs]) || 0;
                    }
                }
            }
        }
    }
    return 0;
};
const keynormalize_NoStatus = keyNormalize('No Status');

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
    const allRows: any[] = paginate.data.value ?? [];

    const fromPropsSet = new Set(filtered.map((s: string) => keyNormalize(s)));
    const extra: string[] = [];
    const extraSeen = new Set<string>();

    allRows.forEach((row: any) => {
        const ts = row.ticket_status ?? {};
        Object.keys(ts).forEach((key) => {
            const norm = keyNormalize(key);
            if (EXCLUDED_STATUSES.has(norm)) return;
            if (fromPropsSet.has(norm) || extraSeen.has(norm)) return;
            if (Number(ts[key] ?? 0) === 0) return;
            extraSeen.add(norm);
            extra.push(key);
        });
    });

    const combined: string[] = [...filtered, ...extra];
    if (combined && combined.length) {
        return combined;
    }

    const firstRow = allRows[0];
    if (firstRow && firstRow.ticket_status) {
        return Object.keys(firstRow.ticket_status).filter((k: string) => !EXCLUDED_STATUSES.has(keyNormalize(k)));
    }
    return CALL_STATUS_ORDER.map((a: string) => {
        for (const [alias, variants] of Object.entries(CALL_STATUS_ALIASES)) {
            if (alias === a && variants.length) return variants[0];
        }
        return a;
    });
});

const columns = computed(() => [
    "SessionStart",
    "SessionEnd",
    "Deskcoll",
    "Data Contacted",
    ...visibleOutbounds.value,
    "No Status",
]);

const groupedByDate = computed(() => {
    const dateGroups: any[] = [];
    const dateMap = new Map<string, any>();
    const allRows: any[] = paginate.data.value ?? [];

    allRows.forEach((row: any) => {
        const dateKey: string = row.date || (row.date_label ? String(new Date(row.date_label).toISOString()).slice(0, 10) : 'unknown');
        const dateLabel: string = row.date_label || row.date || '-';

        if (!dateMap.has(dateKey)) {
            const dg = {
                date_key: dateKey,
                date_label: dateLabel,
                pds_groups: [] as any[],
                _pds_map: new Map<string, any>(),
            };
            dateMap.set(dateKey, dg);
            dateGroups.push(dg);
        }

        const dateGroup = dateMap.get(dateKey);
        const pdsKey = `${row.name ?? '-'}__${row.spv ?? '-'}`;

        if (!dateGroup._pds_map.has(pdsKey)) {
            const pdsG = {
                key: pdsKey,
                title: `${row.name ?? '-'} - ${row.spv ?? '-'}`,
                session_groups: [] as any[],
                _sess_map: new Map<string, any>(),
            };
            dateGroup._pds_map.set(pdsKey, pdsG);
            dateGroup.pds_groups.push(pdsG);
        }

        const pdsGroup = dateGroup._pds_map.get(pdsKey);
        const sStart = row.session_start ?? row.start_date + ' ' + row.start_time ?? '';
        const sEnd = row.session_end ?? row.end_date + ' ' + row.end_time ?? '';
        const sessKey = `${sStart}__${sEnd}`;

        if (!pdsGroup._sess_map.has(sessKey)) {
            const sg = {
                session_key: sessKey,
                session_start: row.start_date ? `${row.start_date} ${row.start_time ?? ''}` : (sStart || '-'),
                session_end: row.end_date ? `${row.end_date} ${row.end_time ?? ''}` : (sEnd || '-'),
                rows: [] as any[],
                rowspan: 0,
            };
            pdsGroup._sess_map.set(sessKey, sg);
            pdsGroup.session_groups.push(sg);
        }

        pdsGroup._sess_map.get(sessKey).rows.push(row);
    });

    dateGroups.forEach((dg) => {
        dg.pds_groups.forEach((pg: any) => {
            pg.session_groups.forEach((sg: any) => {
                sg.rowspan = sg.rows.length || 1;
            });
            delete pg._sess_map;
        });
        delete dg._pds_map;
    });

    return dateGroups;
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
