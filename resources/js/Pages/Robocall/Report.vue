<template>
     <AppLayout title="Robocall" header="Robocall">
         <template v-slot:tab>
             <TabMenu tab="report" />
         </template>
     </AppLayout>
 </template>
 <script setup lang="ts">
 import AppLayout from "@/Layouts/AppLayout.vue";
 import TabMenu from "./components/TabMenu.vue"
 </script>
 