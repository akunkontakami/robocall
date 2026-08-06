<template>
    <Filter class="md:max-w-lg overflow-auto" :reset="route('pds.report')+'?tab=agent'" @close="resetInput">
        <div class="max-h-[65vh] overflow-auto -mx-6 px-6">
            <MultipleSelect
                label="PDS Name"
                id="pds"
                v-model="filter.pds"
                :selected="filter.pds"
                :items="pds"
                placeholder="Select"
            />

            <!-- <MultipleSelect
                label="Marketing Campaign"
                id="campaigns"
                v-model="filter.campaigns"
                :selected="filter.campaigns"
                :items="campaigns"
                placeholder="Select"
            /> -->

            <MultipleSelect
                label="Spv"
                id="spv"
                v-model="filter.spv"
                :selected="filter.spv"
                :items="spv"
                placeholder="Select"
            />

            <MultipleSelect
                label="Agent"
                id="agent"
                v-model="filter.agent"
                :selected="filter.agent"
                :items="agents"
                placeholder="Select"
            />

            <div class="grid grid-cols-2 gap-4">
                <DatePicker
                    name="created_start"
                    label="Start date"
                    required
                    v-model="filter.created_start"
                    :value="filter.created_start"
                    :default="filter.created_start"
                />
                <DatePicker
                    name="created_end"
                    label="End date"
                    required
                    v-model="filter.created_end"
                    :value="filter.created_end"
                    :default="filter.created_end"
                    :min="filter.created_start"
                />
            </div>

            <div class="flex gap-3 justify-end mt-4">
                <ButtonOutlineGrey
                    class="w-[100px] py-3"
                    x-on:click="filter=false"
                    id="cancel-filter"
                    @click="resetInput"
                >
                    Cancel
                </ButtonOutlineGrey>
                <ButtonYellow
                    class="w-[100px] py-3"
                    type="button"
                    @click="$emit('filterData')"
                >
                    Submit
                </ButtonYellow>
            </div>
        </div>
    </Filter>
</template>
<script setup lang="ts">
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import DatePicker from "@/Components/Input/DatePicker.vue";
import MultipleSelect from "@/Components/Input/Select/MultipleSelect.vue";
import Filter from "@/Components/Popup/Filter.vue";
import { getAllQueryParameter } from "@/Plugins/Function/global-function";
import { ref } from "vue";

const props = defineProps(["filter", "campaigns", "spv", "agents", "pds"]);

const resetInput = () => {
    const queries = getAllQueryParameter()
    let status: any = []
    props.filter.created_start = ''
    props.filter.created_end = ''
    props.filter.campaigns = []
    props.filter.pds = []
    props.filter.spv = []
    props.filter.agent = []
}

const options = [
    {
        id: 'Mobile Phone',
        value: 'Mobile Phone',
    },
    {
        id: 'Phone Number 1',
        value: 'Phone Number 1',
    },
    {
        id: 'Phone Number 2',
        value: 'Phone Number 2',
    },
]


</script>
