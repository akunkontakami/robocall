<template>
    <AppLayout title="PDS Detail" header="PDS Detail" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="campaign" :id="id" />
        </template>
        <form action="">
            <div class="bg-white max-w-3xl p-4 mx-auto">
                <Input
                    type="text"
                    placeholder="Campaign"
                    label="Campaign"
                    :value="data.campaign?.name"
                    :disabled="true"
                    class="!bg-[#F3F3F3]"
                />
                <Input
                    type="text"
                    placeholder="SPV"
                    label="SPV"
                    :value="data.spv?.company_user?.name"
                    :disabled="true"
                    class="!bg-[#F3F3F3]"
                />
                <MultipleSelect
                    label="Status"
                    id="status"
                    v-model="form.status"
                    :items="statuses"
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

defineProps(["id", "data", "statuses"])

const form = useForm({
    campaign: '',
    status: [],
});

</script>
