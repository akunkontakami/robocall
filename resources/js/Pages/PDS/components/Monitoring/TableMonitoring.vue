<template>
    <Table :columns="columns" :paginate="paginate">
        <tr
            v-for="(row, i) in paginate.data.value"
            :key="row.SessionId || i"
        >
            <!-- <Td>
                {{ row.SessionId }}
            </Td> -->
            <Td>
                {{ row.SessionStatus }}
            </Td>
            <Td>
                {{ row.SessionStart }}
            </Td>
            <Td>
                {{ row.campaign_id }}
            </Td>
            <!-- <Td>
                {{ row.tenant_id }}
            </Td> -->
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
import { ref } from "vue";

const columns = ref([
    // "Session ID",
    "Status",
    "Session Start",
    "Campaign ID",
    // "Tenant ID",
    "Data Size",
    "Data Dial",
    "Calls In Progress",
    "Failed",
    "Contacted"
]);

const paginate = usePaginate({
    route: route('pds.monitoring.datatable'),
});
</script>
