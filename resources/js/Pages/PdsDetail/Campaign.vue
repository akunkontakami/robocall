<template>
    <AppLayout title="PDS Detail" :header="data.pds_name" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="campaign" :id="id" />
        </template>
        <form @submit.prevent="submit">
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
                    placeholder="Supervisor"
                    label="Supervisor"
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
                    v-if="!data.customers.length"
                />

                <ButtonYellow
                    type="submit" class="w-[120px] mt-10" v-if="!data.customers.length"
                    :disabled="form.processing || !form.status.length"
                    :loading="form.processing"
                > Submit </ButtonYellow>
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

const props = defineProps(["id", "data", "statuses"])

const form = useForm({
    campaign: '',
    status: [],
})

const submit = () => {
    if (!form.processing) {
        form.post(route("pds.detail.assign", props.id), {
            onSuccess: () => {
                window.location.reload()
            }
        });
    }
}

</script>
