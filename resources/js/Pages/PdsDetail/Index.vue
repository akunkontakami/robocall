<template>
    <AppLayout title="PDS Detail" header="PDS Detail" :headerBackUrl="route('pds.setup')">
        <template v-slot:tab>
            <TabMenu tab="detail" />
        </template>
        <form action="">
            <div class="bg-white max-w-3xl p-4 mx-auto min-h-[90vh]">
                <Input
                    type="text"
                    placeholder="Enter PDS Code"
                    label="PDS Code"
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
                    placeholder="Enter PDS Name"
                    label="PDS Name"
                    id="name"
                    name="name"
                    required
                    maxlength="250"
                    :hide-length="true"
                    v-model="form.name"
                    :error="form.errors.name"
                />
                <MultipleSelect
                    label="SPV"
                    id="spv"
                    v-model="form.spv"
                    :items="callPreferences"
                    :error="form.errors.spv"
                    placeholder="Select SPV"
                />
                <MultipleSelect
                    label="Call Preference"
                    id="call_preferences"
                    v-model="form.call_preferences"
                    :items="callPreferences"
                    :error="form.errors.call_preferences"
                    placeholder="Select Call Preference"
                />
                <label
                    for="call_limit"
                    class="text-[12px] text-dark font-krub-medium mb-1 block pre-text-content"
                    v-bind:class="{
                        'text-red': form.errors.call_limit,
                    }"
                >
                    Call Limit
                    <p class="mb-1 font-krub-light text-[#3F4254]"><span class="text-red">*</span> Maximum Call Limit [value jumlah license yg dibeli]</p>
                </label>
                <Input
                    type="number"
                    placeholder="Enter Call Limit"
                    id="call_limit"
                    name="call_limit"
                    required
                    maxlength="30"
                    v-model="form.call_limit"
                    :hide-length="true"
                    :error="form.errors.call_limit"
                />
                <Input
                    type="number"
                    placeholder="Enter Concurrent Call"
                    label="Concurrent Call"
                    id="concurrent_call"
                    name="concurrent_call"
                    required
                    maxlength="30"
                    v-model="form.concurrent_call"
                    :hide-length="true"
                    :error="form.errors.concurrent_call"
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
                    :hide-length="true"
                    :error="form.errors.call_wait"
                />
                <Input
                    type="number"
                    placeholder="Enter Call Retry After (in second)"
                    label="Call Retry After (in second)"
                    id="call_retry"
                    name="call_retry"
                    required
                    maxlength="30"
                    v-model="form.call_retry"
                    :hide-length="true"
                    :error="form.errors.call_retry"
                />
                <Input
                    type="number"
                    placeholder="Enter Call Retry Max"
                    label="Call Retry Max"
                    id="max_call_retry"
                    name="max_call_retry"
                    required
                    maxlength="30"
                    v-model="form.max_call_retry"
                    :hide-length="true"
                    :error="form.errors.max_call_retry"
                />
                <Input
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
                />
            </div>
            <div class="py-6 mt-2 border-t bg-white -ms-4 -me-4 -mb-3">
                <div class="max-w-3xl flex justify-end gap-3 mx-auto">
                    <ButtonOutlineGrey type="button" x-on:click="formPopup=false" class="w-[120px]">
                        Cancel
                    </ButtonOutlineGrey>
                    <ButtonYellow type="submit" class="w-[120px]"> Submit </ButtonYellow>
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

const form = useForm({
    code: "",
    name: "",
    concurrent_call: 0,
    call_limit: 0,
    call_wait: 0,
    call_retry: 0,
    max_call_retry: 0,
    max_abandon_rate: 0,
    call_preferences: [],
    spv: []
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
</script>
