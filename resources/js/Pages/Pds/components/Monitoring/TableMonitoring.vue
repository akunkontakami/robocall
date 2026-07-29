<template>
    <Table :columns="columns" :paginate="paginate">
        <tr
            v-for="(row, i) in paginate.data.value"
            :key="row.SessionId || i"
        >
            <Td>
                {{ row.SessionStatus }}
            </Td>
            <Td>
                {{ row.SessionStart }}
            </Td>
            <Td>
                {{ row.campaign_id }}
            </Td>
            <Td>
                {{ row.DataSize }}
            </Td>
            <Td>
                {{ row.DataDialed }}
            </Td>
            <Td>
                {{ row.DialInProgress }}
            </Td>
            <Td>
                {{ row.DialAbandoned }}
            </Td>
            <Td>
                {{ row.DialFailed }}
            </Td>
            <Td>
                {{ row.DialContacted }}
            </Td>
        </tr>
    </Table>
</template>
<script setup lang="ts">
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps<{
    paginate?: any;
}>();

const columns = ref([
    "Status",
    "Session Start",
    "Campaign ID",
    "Data Size",
    "Data Dial",
    "Dial In Progress",
    "Call Abandoned",
    "No Answer",
    "Contacted"
]);

const paginate = props.paginate ?? usePaginate({
    route: route('pds.monitoring.datatable'),
});

let refreshInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    refreshInterval = setInterval(() => {
        paginate.fetchData();
    }, 60000);
});

onBeforeUnmount(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
