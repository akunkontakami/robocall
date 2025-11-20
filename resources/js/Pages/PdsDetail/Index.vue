<template>
    <AppLayout title="PDS Detail" :header="data.pds_name" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="detail" :id="id" />
        </template>
        <form @submit.prevent="submit">
            <div class="min-h-[83vh]">
                <div class="bg-white max-w-3xl p-4 mx-auto">
                    <Input
                        type="text"
                        placeholder="Enter Tenant Id"
                        label="Tenant Id"
                        required
                        :value="$page.props.auth.user.tenant_id"
                        :disabled="true"
                        class="!bg-[#F3F3F3]"
                    />
                    <Input
                        type="text"
                        placeholder="Enter PDS Name"
                        label="PDS Name"
                        id="name"
                        name="name"
                        required
                        maxlength="250"
                        :hide-length="true"
                        v-model="form.name"
                        :value="form.name"
                        :error="form.errors.name"
                        :disabled="true"
                        class="!bg-[#F3F3F3]"
                    />
                    <SelectSearch
                        placeholder="Choose Trunk"
                        label="Trunk"
                        id="trunk"
                        name="trunk"
                        required
                        v-model="form.trunk"
                        :value="form.trunk"
                        :error="form.errors.trunk"
                        :items="routes"
                        :disabled="true"
                    />
                    <SelectSearch
                        placeholder="Choose IVR"
                        label="IVR"
                        id="ivr"
                        name="ivr"
                        required
                        v-model="form.ivr"
                        :value="form.ivr"
                        :error="form.errors.ivr"
                        :items="ivr"
                        :disabled="true"
                    />
                    <SelectSearch
                        label="Marketing Campaign"
                        id="marketing_campaign"
                        v-model="form.marketing_campaign"
                        :value="form.marketing_campaign"
                        :items="campaigns"
                        :error="form.errors.marketing_campaign"
                        placeholder="Choose Marketing Campaign"
                        :disabled="data.spv_id"
                    />
                    <SelectSearch
                        label="Supervisor"
                        id="spv"
                        v-model="form.spv"
                        :value="form.spv"
                        v-bind:items="spvUsers"
                        :error="form.errors.spv"
                        placeholder="Choose SPV"
                        :disabled="data.spv_id"
                    />
                </div>
            </div>
            <div class="py-6 mt-2 border-t bg-white -ms-4 -me-4 -mb-3">
                <div class="max-w-3xl flex justify-end gap-3 mx-auto">
                    <ButtonOutlineGrey type="button" x-on:click="formPopup=false" class="w-[120px]">
                        Cancel
                    </ButtonOutlineGrey>
                    <ButtonYellow
                        type="submit" class="w-[120px]"
                        :disabled="
                            form.processing ||
                            (
                                !data.spv_id && (
                                    !form.marketing_campaign ||
                                    !form.spv
                                )
                            ) || data.spv_id
                        "
                        :loading="form.processing"
                    > Submit </ButtonYellow>
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
import SelectSearch from "@/Components/Input/Select/SelectSearch.vue";
import TabMenu from "./components/TabMenu.vue";
import { onBeforeMount, onMounted, ref, watch } from "vue";

const props = defineProps(["campaigns", "ivr", "routes", "data", "id"])

const form = useForm({
    name: props.data.pds_name,
    trunk: props.data.route,
    ivr: props.data.ivr,
    marketing_campaign: props.data.marketing_campaign_id,
    spv: props.data.spv_id
});

const spvUsers = ref([])


const submit = () => {
    if (!form.processing) {
        form.post(route("pds.detail.update", props.id), {
            onSuccess: () => {
                window.location.reload()
            }
        });
    }
}

watch(
    () => form.marketing_campaign,
    (newCampaignId) => {
        const campaign = props.campaigns.find((c: any) => c.value === newCampaignId);

        if (campaign) {
            spvUsers.value = campaign.spv.map((s: any) => ({
                value: s.user_id,
                label: s.company_user?.name,
            }));
        } else {
            spvUsers.value = [];
            form.spv = "";
        }
    }
);

onBeforeMount(() => {
    if (props.data.marketing_campaign_id) {
        const campaign = props.campaigns.find((c: any) => c.value === props.data.marketing_campaign_id);

        if (campaign) {
            spvUsers.value = campaign.spv.map((s: any) => ({
                value: s.user_id,
                label: s.company_user?.name,
            }));
        } else {
            spvUsers.value = [];
            form.spv = "";
        }
    }
})
</script>
