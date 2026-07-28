<template>
    <Filter class="md:max-w-lg overflow-auto" :reset="route('pds.monitoring')+'?tab=pds-history'" @close="resetInput">
        <div class="max-h-[65vh] overflow-auto -mx-6 px-6">
            <div class="grid grid-cols-2 gap-4">
                <DatePicker
                    name="created_start"
                    label="Start date"
                    required
                    v-model="filter.created_start"
                    :value="filter.created_start"
                />
                <DatePicker
                    name="created_end"
                    label="End date"
                    required
                    v-model="filter.created_end"
                    :value="filter.created_end"
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

const props = defineProps(["filter"]);

const resetInput = () => {
    const queries = getAllQueryParameter()
    let status: any = []
    props.filter.created_start = ''
    props.filter.created_end = ''
}

</script>
