<template>
    <AppLayout
        title="Robocall Detail"
        :header="data.robocall_name"
        :headerBackUrl="route('robocall.setup')"
    >
        <template v-slot:tab>
            <TabMenu tab="campaign" :id="id" :data="data" />
        </template>
        <form @submit.prevent="submit">
            <div
                class="bg-white max-w-3xl p-4 mx-auto"
                v-if="!data.customers.length"
            >
                <SelectSearch
                    label="Marketing Campaign"
                    id="marketing_campaign"
                    v-model="form.marketing_campaign"
                    :value="form.marketing_campaign"
                    :items="campaigns"
                    :error="form.errors.marketing_campaign"
                    placeholder="Choose Marketing Campaign"
                    :disabled="data.marketing_campaign_id"
                    @changeValue="changeMarketingCampaign"
                />
                <MultipleSelect
                    label="Status"
                    id="status"
                    v-model="form.status"
                    :items="statuses"
                    :error="form.errors.status"
                    placeholder="Select Status"
                    v-if="showStatus"
                />

                <ButtonYellow
                    type="submit"
                    class="w-[120px] mt-10"
                    v-if="!data.customers.length"
                    :disabled="form.processing || !form.status.length"
                    :loading="form.processing"
                >
                    Submit
                </ButtonYellow>
            </div>

            <div class="bg-white max-w-3xl p-4 mx-auto" v-else>
                <Input
                    label="Marketing Campaign"
                    id="marketing_campaign"
                    :value="data.campaign?.name"
                    :disabled="true"
                />

                <div
                    class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] outline-none shadow-none py-2 w-full mb-2 flex gap-2 flex-wrap"
                    v-if="
                        data.status_campaigns &&
                        data.status_campaigns.length > 0
                    "
                >
                    <span
                        class="border px-2 items-center flex rounded bg-[#F4F6FA] text-[10px] h-[20px] text-[#0E0F0F] font-krub-medium"
                        v-for="item in data.status_campaigns"
                    >
                        {{ item }}
                    </span>
                </div>
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
import { ref } from "vue";
import axios from "axios";

const props = defineProps(["id", "data", "campaigns"]);

const form = useForm({
    marketing_campaign: "",
    status: [],
});

const showStatus = ref(true);
const statuses = ref([]);

const submit = () => {
    if (!form.processing) {
        form.post(route("robocall.detail.assign-campaign", props.id), {
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};

const changeMarketingCampaign = (id: string) => {
    form.status = [];
    statuses.value = [];
    showStatus.value = false;

    axios
        .get(route("robocall.detail.status", { id: props.id, campaignId: id }))
        .then((res: any) => {
            statuses.value = res.data.statuses;
        })
        .finally(() => {
            showStatus.value = true;
        });
};
</script>
