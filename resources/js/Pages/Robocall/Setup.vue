<template>
    <AppLayout title="Robocall" header="Robocall">
        <template v-slot:tab>
            <TabMenu tab="setup" />
        </template>
        <div x-data="{formPopup : true}">
            <div class="flex justify-between">
                <TableSearch />
                <div class="flex gap-2">
                    <ButtonYellow
                        type="button"
                        class="flex items-center gap-2"
                        x-on:click="formPopup=true"
                    >
                        <i class="isax icon-add text-[13px]"></i>
                        Setup New Robocall
                    </ButtonYellow>
                </div>
            </div>
            <TableSetup />
            <FormSetup/>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import TableSearch from "@/Components/Table/TableSearch.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import TableSetup from "./components/TableSetup.vue";
import FormSetup from "./components/FormSetup.vue";
</script>
