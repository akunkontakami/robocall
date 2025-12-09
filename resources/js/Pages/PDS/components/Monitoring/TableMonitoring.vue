<template>
    <Table :columns="columns" :paginate="paginate">
        <tr
            v-for="(row, i) in paginate.data.value"
            :class="{
                'bg-[#FDBFB7]': row.abandoned_rate_num >= row.max_abandoned
            }"
        >
            <Td>
                {{ row.name }}
            </Td>
            <Td>
                <span v-if="!row.is_running">Stop</span>
                <span v-if="row.is_running">Running</span>
            </Td>
            <Td>
                {{ row.spv }}
            </Td>
            <Td>
                {{ row.total_agent }}
            </Td>
            <Td>
                {{ row.data_size }}
            </Td>
            <Td>
                {{ row.retry }}
            </Td>
            <Td>
                {{ row.data_dialed }}
            </Td>
            <Td>
                {{ row.calls }}
            </Td>
            <Td>
                {{ row.call_in_progress }}
            </Td>
            <Td>
                {{ row.contacted }}
            </Td>
            <Td>
                {{ row.failed }}
            </Td>
            <Td>
                {{ row.answered }}
            </Td>
            <Td>
                {{ row.abandoned }}
            </Td>
            <Td>
                {{ row.abandoned_rate }}
            </Td>
        </tr>
    </Table>
</template>
<script setup lang="ts">
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { ref, onBeforeUnmount, onMounted } from "vue";

const columns = ref([
    "PDS Name",
    "Status",
    "SPV",
    "Agent Ready",
    "Data Size",
    "Retry",
    "Data Dial",
    "Calls",
    "Calls In Progress",
    "Contacted",
    "Failed",
    "Answered",
    "Abandon",
    "Abandon Rate"
]);

const paginate = usePaginate({
    route: route('pds.monitoring.datatable'),
});
</script>
