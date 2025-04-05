<template>
    <div x-data="{ dropdownOpen: false }">
        <div class="flex gap-2 items-center">
            <span class="text-[12px] font-krub-semibold"> Campaign : </span>
            <button
                type="button"
                class="flex items-center gap-2 e py-[6px] px-3 bg-white text-[11px] border rounded-full font-krub-semibold"
                x-on:click="dropdownOpen=!dropdownOpen"
                @click.stop="$event.preventDefault()"
                x-ref="button"
            >
                {{ label }}
                <i class="isax icon-arrow-down-1"></i>
            </button>
        </div>

        <Dropdown
            x-show="dropdownOpen"
            x-anchor.bottom-start="$refs.button"
            class="z-10 mt-1 max-h-[300px] overflow-auto"
        >
            <DropdownMenu v-for="campaign in campaigns" @click="$emit('update',campaign.id)">
                {{ campaign.name }} 
            </DropdownMenu>
        </Dropdown>
    </div>
</template>
<script setup lang="ts">
import Dropdown from "./Dropdown.vue";
import DropdownMenu from "./DropdownMenu.vue";
import { ref,watch,onMounted } from "vue";

const props = defineProps(["campaigns","campaignId"]);
const label = ref("Choose Campaign");


const changeLabel = () => {
    const find = props.campaigns.find((row: any) => row.id == props.campaignId);
    label.value = find ? find.name : "Choose Campaign";
};
onMounted(()=>{
    changeLabel()
})
watch(
    () => props.campaignId,
    (value, val) => {
        changeLabel();
    }
);
</script>
