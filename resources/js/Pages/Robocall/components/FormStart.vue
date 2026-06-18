<template>
    <FormPopup title="Start Robocall">
        <form @submit.prevent="submit">
            <div class="overflow-auto max-h-[60vh]">
                <Input
                    type="number"
                    placeholder="Enter Call Limit"
                    label="Call Limit"
                    id="call_limit"
                    name="call_limit"
                    required
                    maxlength="30"
                    v-model="form.call_limit"
                    :value="form.call_limit"
                    :hide-length="true"
                    :error="form.errors.call_limit"
                    min="0"
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
            </div>
            <div
                class="flex justify-end gap-3 pt-3 mt-2 border-t sticky bottom-0 bg-white"
            >
                <ButtonOutlineGrey
                    type="button"
                    x-on:click="formPopup=false"
                    class="w-[120px]"
                >
                    Cancel
                </ButtonOutlineGrey>
                <ButtonYellow
                    type="submit"
                    class="w-[120px]"
                    :disabled="form.processing || !form.call_limit"
                    :loading="form.processing"
                >
                    Submit
                </ButtonYellow>
            </div>
        </form>

        <a hidden x-on:click="formPopup=false" id="hide-form-start"></a>
    </FormPopup>

    <div x-data="{confirmation:false}" v-if="showPopupStart">
        <a hidden id="show-start-robocall" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to start this Robocall?"
            @action="actionStart"
        />
    </div>
</template>
<script setup lang="ts">
import FormPopup from "@/Components/Popup/FormPopup.vue";
import Input from "@/Components/Input/Index.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import { clickId, showAlert } from "@/Plugins/Function/global-function";
import { ref } from "vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";
import SelectSearch from "@/Components/Input/Select/SelectSearch.vue";

const props = defineProps(["id", "ivr", "route"]);

const showPopupStart = ref(true);

const form = useForm({
    id: "",
    trunk: "",
    ivr: "",
    call_limit: 0,
});

const submit = () => {
    clickId("hide-form-start");
    clickId("show-start-robocall");
};

const actionStart = () => {
    if (!form.processing) {
        form.id = props.id;

        form.post(route("robocall.setup.start"), {
            onSuccess: () => {
                window.location.reload();
            },
            onError: () => {
                showPopupStart.value = false;

                setTimeout(() => {
                    showPopupStart.value = true;
                }, 100);
            },
        });
    }
};
</script>
