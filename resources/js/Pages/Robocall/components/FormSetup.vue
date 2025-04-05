<template>
    <FormPopup title="Add New Robocall">
        <form action="">
            <Input
                type="text"
                placeholder="Enter Robocall Code"
                label="Robocall Code"
                id="code"
                name="code"
                required
                maxlength="250"
                v-model="form.code"
                :hide-length="true"
                :error="form.errors.code"
            />
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
            <Input
                type="number"
                placeholder="Enter Call Limit"
                label="Call Limit"
                id="limit"
                name="limit"
                required
                maxlength="100"
                v-model="form.limit"
                :hide-length="true"
                help="* Maximum Call Limit 100"
                :error="form.errors.limit"
            />
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <DatePicker
                        name="schedule_date"
                        v-model="form.schedule_date"
                        :error="form.errors.schedule_date"
                        label="Schedule"
                        :min="new Date()"
                    />
                </div>
                <div class="flex items-end">
                    <TimePicker
                        name="schedule_start"
                        v-model="form.schedule_start"
                        :error="form.errors.schedule_start"
                        label="Start Time"
                        placeholder="00:00"
                        :min="new Date()"
                    />
                    <span class="block mb-7 text-[12px] ms-2">hh:mm</span>
                </div>
                <div class="flex items-end">
                    <TimePicker
                        name="schedule_end"
                        v-model="form.schedule_end"
                        :error="form.errors.schedule_end"
                        label="End Time"
                        placeholder="00:00"
                        :min="new Date()"
                    />
                    <span class="block mb-7 text-[12px] ms-2">hh:mm</span>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <ButtonOutlineGrey type="button" x-on:click="formPopup=false" class="w-[120px]">
                    Cancel
                </ButtonOutlineGrey>
                <ButtonYellow type="submit" class="w-[120px]"> Submit </ButtonYellow>
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

const form = useForm({
    code: "",
    name: "",
    limit: 0,
    schedule_date: "",
    schedule_start: "",
    schedule_end: "",
});
</script>
