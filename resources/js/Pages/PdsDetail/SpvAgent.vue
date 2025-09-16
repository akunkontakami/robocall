<template>
    <AppLayout title="PDS Detail" header="PDS Detail" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="spv-agent" :id="id" />
        </template>


        <data x-data="{openRowIndex: ''}" class="flex flex-col gap-4">
            <div v-for="(item, i) in data">
                <div
                    class="w-full bg-white flex items-center gap-2 py-3 px-6 border cursor-pointer"
                    :x-bind:class="`openRowIndex === '${i}' ? 'rounded-t-md' : 'rounded-md'`"
                    :x-on:click="`openRowIndex = openRowIndex === '${i}' ? '' : '${i}'`"
                >
                    <p class="text-[#181C32] font-krub-medium text-sm">{{ item.name }}</p>
                    <p class="bg-[#3943B7] py-1 px-2 rounded-sm text-white text-[10px] font-krub-medium">Total Agent: <span class="font-krub-bold">{{ item.agents.length }}</span></p>

                    <IconArrowUp class="ms-auto transition-all"
                        :x-bind:class="`openRowIndex === '${i}' ? 'rotate-0' : 'rotate-180'`"
                    />
                </div>

                <div
                    class="border-b border-x rounded-b-md overflow-hidden" :x-show="`openRowIndex === '${i}'`"
                >
                    <div
                        class="py-[6px] text-[#181C32] text-sm font-krub-medium bg-[#F9FBFC]"
                        :style="{ paddingLeft: `${item.name.length * 14}px` }"
                    >
                        Agent Name
                    </div>
                    <div
                        class="py-[6px] bg-white text-[#181C32] text-sm font-krub-medium border-t"
                        :style="{ paddingLeft: `${item.name.length * 14}px` }"
                        v-for="agent in item.agents"
                    >
                        {{ agent.company_user?.name }}
                    </div>
                </div>
            </div>
        </data>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TabMenu from "./components/TabMenu.vue";
import { ref } from "vue";
import IconArrowUp from "@/Components/Icon/Etc/IconArrowUp.vue";

const props = defineProps(["id", "data"])

const data = ref<any>([
    {
        name: props.data.spv?.company_user?.name,
        agents: props.data.agents
    }
])
</script>
