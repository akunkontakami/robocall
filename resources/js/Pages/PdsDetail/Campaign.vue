<template>
    <AppLayout title="PDS Detail" header="PDS Detail" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="campaign" />
        </template>
        <form action="">
            <div class="bg-white max-w-3xl p-4 mx-auto">
                <SelectSearch
                    label="Campaign"
                    id="campaign"
                    v-model="form.campaign"
                    :items="campaigns"
                    :error="form.errors.campaign"
                    placeholder="Select Campaign"
                />
                <MultipleSelect
                    label="Status"
                    id="status"
                    v-model="form.status"
                    :items="callPreferences"
                    :error="form.errors.status"
                    placeholder="Select Status"
                />

                <ButtonYellow type="submit" class="w-[120px]"> Submit </ButtonYellow>
            </div>
        </form>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import Input from "@/Components/Input/Index.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import MultipleSelect from "@/Components/Input/Select/MultipleSelect.vue";
import TabMenu from "./components/TabMenu.vue";
import SelectSearch from "@/Components/Input/Select/SelectSearch.vue";

const form = useForm({
    campaign: '',
    status: [],
});

const callPreferences = [
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

const campaigns = [
    {
        value: 'Campaign 1',
        label: 'Campaign 1',
    },
    {
        value: 'Campaign 2',
        label: 'Campaign 2',
    },
    {
        value: 'Campaign 3',
        label: 'Campaign 3',
    },
]
</script>
