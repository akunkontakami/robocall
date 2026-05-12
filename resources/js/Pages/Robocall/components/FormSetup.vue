<template>
    <FormPopup title="Add New Robocall">
        <form action="" @submit.prevent="submit">
            <Input
                type="text"
                placeholder="Enter Robocall Name"
                label="Robocall Name"
                id="name"
                name="name"
                required
                maxlength="250"
                :hide-length="true"
                v-model="form.name"
                :error="form.errors.name"
            />
            <div class="flex justify-end gap-3">
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
                    :disabled="form.processing || !form.name.trim()"
                    :loading="form.processing"
                >
                    Submit
                </ButtonYellow>
            </div>
        </form>
    </FormPopup>
</template>
<script setup lang="ts">
import FormPopup from "@/Components/Popup/FormPopup.vue";
import Input from "@/Components/Input/Index.vue";
import DatePicker from "@/Components/Input/DatePicker.vue";
import TimePicker from "@/Components/Input/TimePicker.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import { showAlert } from "@/Plugins/Function/global-function";

const form = useForm({
    name: "",
});

const submit = () => {
    if (!form.processing) {
        if (/\s/.test(form.name)) {
            showAlert("Robocall Name must not contain spaces");
            return;
        }

        form.post(route("robocall.setup.store"), {
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};
</script>
