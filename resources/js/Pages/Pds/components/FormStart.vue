<template>
    <FormPopup title="Start PDS">
        <form @submit.prevent="submit">
            <div class="overflow-auto max-h-[60vh]">
                <Input
                    type="number"
                    placeholder="Enter Call Factor"
                    label="Call Factor"
                    id="call_factor"
                    name="call_factor"
                    required
                    maxlength="30"
                    v-model="form.call_factor"
                    :value="form.call_factor"
                    :hide-length="true"
                    :error="form.errors.call_factor"
                    min="0"
                />
                <Input
                    type="number"
                    placeholder="Enter Call Wait (in second)"
                    label="Call Wait (in second)"
                    id="call_wait"
                    name="call_wait"
                    required
                    maxlength="30"
                    v-model="form.call_wait"
                    :value="form.call_wait"
                    :hide-length="true"
                    :error="form.errors.call_wait"
                    min="0"
                />
                <Input
                    type="number"
                    placeholder="Enter Max Call Abandon Rate"
                    label="Max Call Abandon Rate"
                    id="call_abandon_rate"
                    name="call_abandon_rate"
                    required
                    maxlength="30"
                    v-model="form.call_abandon_rate"
                    :value="form.call_abandon_rate"
                    :hide-length="true"
                    :error="form.errors.call_abandon_rate"
                    min="0"
                    step=".1"
                    help="Example rates: 0.1, 0.2, 1. Please use the Up and Down arrow keys on your keyboard to input decimal values."
                />
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

                <Input
                    type="number"
                    placeholder="Enter Call Retry Max"
                    label="Call Retry Max"
                    id="call_retry_max"
                    name="call_retry_max"
                    required
                    maxlength="30"
                    v-model="form.call_retry_max"
                    :value="form.call_retry_max"
                    :hide-length="true"
                    :error="form.errors.call_retry_max"
                    min="0"
                />
                <Input
                    type="number"
                    placeholder="Enter Call Retry After (in second)"
                    label="Call Retry After (in second)"
                    id="call_retry_after"
                    name="call_retry_after"
                    required
                    maxlength="30"
                    v-model="form.call_retry_after"
                    :value="form.call_retry_after"
                    :hide-length="true"
                    :error="form.errors.call_retry_after"
                    min="0"
                />
                <!-- <Input
                    type="number"
                    placeholder="Enter Max Abandon Rate"
                    label="Max Abandon Rate"
                    id="max_abandon_rate"
                    name="max_abandon_rate"
                    required
                    maxlength="30"
                    v-model="form.max_abandon_rate"
                    :hide-length="true"
                    help="example : 0.1, 0.2 etc"
                    :error="form.errors.max_abandon_rate"
                    step="0.1"
                /> -->
            </div>
            <div class="flex justify-end gap-3 pt-3 mt-2 border-t sticky bottom-0 bg-white">
                <ButtonOutlineGrey type="button" x-on:click="formPopup=false" class="w-[120px]">
                    Cancel
                </ButtonOutlineGrey>
                <ButtonYellow
                    type="submit" class="w-[120px]"
                    :disabled="
                        form.processing ||
                        !form.call_factor ||
                        !form.call_wait ||
                        !form.call_abandon_rate ||
                        !form.call_limit ||
                        !form.call_retry_after ||
                        !form.call_retry_max
                    "
                    :loading="form.processing"
                > Submit </ButtonYellow>
            </div>
        </form>

        <a hidden x-on:click="formPopup=false" id="hide-form-start"></a>
    </FormPopup>

    <div x-data="{confirmation:false}" v-if="showPopupStart">
        <a hidden id="show-start-pds" x-on:click="confirmation=true"></a>
            <ConfirmationSubmit
            :confirmation="ids.length > 1 ? 'Are you sure you want to start the selected PDS?' : 'Are you sure you want to start this PDS?'"
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
import { computed, ref } from "vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";

const props = defineProps(["id"])
const ids = computed(() => Array.isArray(props.id) ? props.id : [props.id])

const showPopupStart = ref(true)

const form = useForm({
    id: "",
    ids: [] as Array<string | number>,
    call_factor: 0,
    call_wait: 0,
    call_abandon_rate: 0,
    call_limit: 0,
    call_retry_after: 0,
    call_retry_max: 0
});

const submit = () => {
    if (form.call_abandon_rate == 0) {
        showAlert("Call Abandon Rate cannot be set to zero")
        return
    }

    clickId('hide-form-start')
    clickId('show-start-pds')
}

const actionStart = () => {
    if (!form.processing) {
        form.id = Array.isArray(props.id) ? props.id[0] : props.id
        form.ids = Array.isArray(props.id) ? props.id : [props.id]

        if (form.call_abandon_rate == 0) {
            showAlert("Call Abandon Rate cannot be set to zero")
            return
        }

        form.post(route("pds.setup.start"), {
            onSuccess: () => {
                window.location.reload()
            },
            onError: () => {
                showPopupStart.value = false
                clickId('toggle-start-form')

                setTimeout(() => {
                    showPopupStart.value = true
                }, 100);
            }
        });
    }
}
</script>
