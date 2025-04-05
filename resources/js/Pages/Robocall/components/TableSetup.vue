<template>
    <Table :columns="columns" :paginate="paginate"> </Table>
</template>
<script setup lang="ts">
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { ref, onBeforeUnmount, onMounted } from "vue";

const columns = ref([
     "Date",
     "Robocall Name",
     "Campaign Robocall",
     "Status Robocall",
     "Status",
     "Total Data",
     "Action"
]);
const paginate = usePaginate({
    route: "",
});
</script>
