<template>
    <AppLayout title="PDS Detail" :header="data.pds_name" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="campaign" :id="id" />
        </template>
        <AssignCustomerForm :id="id" :data="data" :offices="offices" />
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import AssignCustomerForm from "./components/AssignCustomerForm.vue";
defineProps(["id", "data", "offices"]);
</script>
