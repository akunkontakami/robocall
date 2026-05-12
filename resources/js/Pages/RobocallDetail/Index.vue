<template>
    <AppLayout
        title="Robocall Detail"
        :header="data.robocall_name"
        :headerBackUrl="route('robocall.setup')"
    >
        <template v-slot:tab>
            <TabMenu tab="detail" :id="id" :data="data" />
        </template>
        <form @submit.prevent="submit">
            <div class="min-h-[81vh]">
                <div class="bg-white max-w-3xl p-4 mx-auto">
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
                        :value="form.name"
                        :error="form.errors.name"
                        :disabled="props.data.is_running ? true : false"
                        :class="props.data.is_running ? '!bg-[#F3F3F3]' : ''"
                    />

                    <Input
                        type="text"
                        placeholder="Enter Call Limit"
                        label="Call Limit"
                        id="call_limit"
                        name="call_limit"
                        required
                        maxlength="250"
                        :hide-length="true"
                        v-model="form.call_limit"
                        :value="form.call_limit"
                        :error="form.errors.call_limit"
                        :disabled="true"
                        class="!bg-[#F3F3F3]"
                    />

                    <Input
                        type="text"
                        placeholder="Enter IVR"
                        label="IVR"
                        id="ivr"
                        name="ivr"
                        required
                        maxlength="250"
                        :hide-length="true"
                        v-model="form.ivr"
                        :value="form.ivr"
                        :error="form.errors.ivr"
                        :disabled="true"
                        class="!bg-[#F3F3F3]"
                    />

                    <Input
                        type="text"
                        placeholder="Enter Trunk"
                        label="Trunk"
                        id="trunk"
                        name="trunk"
                        required
                        maxlength="250"
                        :hide-length="true"
                        v-model="form.trunk"
                        :value="form.trunk"
                        :error="form.errors.trunk"
                        :disabled="true"
                        class="!bg-[#F3F3F3]"
                    />
                </div>
            </div>

            <div
                class="py-6 mt-2 border-t bg-white -ms-4 -me-4 -mb-3"
                v-if="!props.data.is_running"
            >
                <div class="max-w-3xl flex justify-end gap-3 mx-auto">
                    <ButtonOutlineGrey
                        type="button"
                        class="w-[120px]"
                        @click="cancel"
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
            </div>
        </form>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import Input from "@/Components/Input/Index.vue";
import { useForm } from "@inertiajs/vue3";
import TabMenu from "./components/TabMenu.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { showAlert } from "@/Plugins/Function/global-function";

const props = defineProps(["campaigns", "ivr", "routes", "data", "id"]);

const form = useForm({
    name: props.data.robocall_name || "",
    call_limit: props.data.call_limit || "-",
    ivr: props.data.ivr || "-",
    trunk: props.data.route || "-",
});

const submit = () => {
    if (!form.processing) {
        if (/\s/.test(form.name)) {
            showAlert("Robocall Name must not contain spaces");
            return;
        }

        form.post(route("robocall.detail.update", props.id), {
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};

const cancel = () => {
    form.name = props.data.robocall_name;
};
</script>
