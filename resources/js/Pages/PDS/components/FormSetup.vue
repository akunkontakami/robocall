<template>
    <FormPopup title="Setup new PDS">
        <form @submit.prevent="submit">
            <div class="overflow-auto max-h-[60vh]">
                <Input
                    type="text"
                    placeholder="Enter Tenant Id"
                    label="Tenant Id"
                    :required="true"
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
                    :required="true"
                    maxlength="30"
                    :hide-length="true"
                    v-model="form.name"
                    :error="form.errors.name"
                    help="Spaces are not allowed in this field. Please enter text without spaces"
                />
                <SelectSearch
                    placeholder="Choose Trunk"
                    label="Trunk"
                    id="trunk"
                    name="trunk"
                    :required="true"
                    v-model="form.trunk"
                    :error="form.errors.trunk"
                    :items="route"
                />
                <SelectSearch
                    placeholder="Choose IVR"
                    label="IVR"
                    id="ivr"
                    name="ivr"
                    :required="true"
                    v-model="form.ivr"
                    :error="form.errors.ivr"
                    :items="ivr"
                />
                <SelectSearch
                    label="Marketing Campaign"
                    id="marketing_campaign"
                    v-model="form.marketing_campaign"
                    :items="campaigns"
                    :error="form.errors.marketing_campaign"
                    placeholder="Choose Marketing Campaign"
                    help="To display the campaign name, please assign an SPV to the marketing campaign"
                />
                <SelectSearch
                    label="Supervisor"
                    id="spv"
                    v-model="form.spv"
                    v-bind:items="spvUsers"
                    :error="form.errors.spv"
                    placeholder="Choose Supervisor"
                />
            </div>
            <div class="flex justify-end gap-3 pt-3 mt-2 border-t sticky bottom-0 bg-white">
                <ButtonOutlineGrey type="button" x-on:click="formPopup=false" class="w-[120px]">
                    Cancel
                </ButtonOutlineGrey>
                <ButtonYellow
                    type="submit" class="w-[120px]"
                    :disabled="
                        form.processing ||
                        !form.name ||
                        !form.trunk ||
                        !form.ivr
                    "
                    :loading="form.processing"
                > Submit </ButtonYellow>
            </div>
        </form>
    </FormPopup>
</template>
<script setup lang="ts">
import FormPopup from "@/Components/Popup/FormPopup.vue";
import Input from "@/Components/Input/Index.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import SelectSearch from "@/Components/Input/Select/SelectSearch.vue";
import { ref, watch } from "vue";
import { showAlert } from "@/Plugins/Function/global-function";

const props = defineProps(["campaigns", "ivr", "route"])

const form = useForm({
    name: "",
    trunk: "",
    ivr: "",
    marketing_campaign: '',
    spv: ''
});

const spvUsers = ref([])


const submit = () => {
    if (!form.processing) {
        if (/\s/.test(form.name)) {
            showAlert('PDS Name must not contain spaces')
            return
        }

        form.post(route("pds.setup.store"), {
            onSuccess: () => {
                window.location.reload()
            }
        });
    }
};

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
</script>
