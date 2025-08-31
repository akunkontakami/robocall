<template>
    <Table :columns="columns" :paginate="paginate">
        <tr
            v-for="(row, i) in paginate.data.value"
            :class="{
                'bg-[#FDBFB7]': i == 3
            }"
        >
            <Td>
                PDS-{{ i }}
            </Td>
            <Td>
                Running
            </Td>
            <Td>
                SPV-{{ i }}
            </Td>
            <Td>
                10
            </Td>
            <Td>
                8
            </Td>
            <Td>
                30
            </Td>
            <Td>
                20
            </Td>
            <Td>
                111
            </Td>
            <Td>
                11
            </Td>
            <Td>
                13
            </Td>
            <Td>
                210
            </Td>
            <Td>
                20
            </Td>
            <Td>
                2
            </Td>
            <Td>
                10%
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
    "PDS Id",
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
    route: route('dummy'),
});
</script>
