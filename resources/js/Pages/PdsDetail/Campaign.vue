<template>
    <AppLayout
        title="PDS Detail"
        :header="data.pds_name"
        :headerBackUrl="route('pds.setup')"
    >
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
                    @updateMaxMobile="updateMaxMobile"
                    @updateMaxAdditional="updateMaxAdditional"
                    v-if="!data.customers.length && data.campaign"
                />

                <!-- Phone Options -->
                <div class="space-y-4 mt-4">
                    <!-- Mobile Phone Section -->
                    <div class="space-y-3" v-if="mobileOptions.length">
                        <div class="flex items-center">
                            <label class="flex gap-x-3 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    :checked="form.mobile.length > 0"
                                    @change="toggleParent('mobile')"
                                />
                                <span
                                    class="text-xs font-opensauceone-medium text-slate-600 group-hover:text-slate-800 transition-colors"
                                >
                                    Mobile
                                </span>
                            </label>
                        </div>

                        <!-- Mobile Phone Sub-list -->
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div
                                v-if="form.mobile.length > 0"
                                class="ml-4 grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-lg border border-slate-100"
                            >
                                <label
                                    v-for="num in mobileOptions"
                                    :key="`mobile-${num}`"
                                    class="flex gap-x-2 cursor-pointer group"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="
                                            form.mobile.includes(
                                                `Mobile ${num}`,
                                            )
                                        "
                                        @change="
                                            toggleSubPhone(
                                                `Mobile ${num}`,
                                                'mobile',
                                            )
                                        "
                                    />
                                    <span
                                        class="text-xs font-opensauceone-medium text-slate-500 group-hover:text-slate-700 transition-colors"
                                    >
                                        Mobile {{ num }}
                                    </span>
                                </label>
                            </div>
                        </transition>
                    </div>

                    <!-- Additional Phone Section -->
                    <div class="space-y-3" v-if="additionalOptions.length">
                        <div class="flex items-center">
                            <label class="flex gap-x-3 cursor-pointer group">
                                <input
                                    type="checkbox"
                                    :checked="form.additional.length > 0"
                                    @change="toggleParent('additional')"
                                />
                                <span
                                    class="text-xs font-opensauceone-medium text-slate-600 group-hover:text-slate-800 transition-colors"
                                >
                                    Additional
                                </span>
                            </label>
                        </div>

                        <!-- Additional Phone Sub-list -->
                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div
                                v-if="form.additional.length > 0"
                                class="ml-4 grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-lg border border-slate-100"
                            >
                                <label
                                    v-for="num in additionalOptions"
                                    :key="`additional-${num}`"
                                    class="flex gap-x-2 cursor-pointer group"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="
                                            form.additional.includes(
                                                `Additional ${num}`,
                                            )
                                        "
                                        @change="
                                            toggleSubPhone(
                                                `Additional ${num}`,
                                                'additional',
                                            )
                                        "
                                    />
                                    <span
                                        class="text-xs font-opensauceone-medium text-slate-500 group-hover:text-slate-700 transition-colors"
                                    >
                                        Additional {{ num }}
                                    </span>
                                </label>
                            </div>
                        </transition>
                    </div>
                </div>

                <ButtonYellow
                    type="submit"
                    class="w-[120px] mt-10"
                    v-if="!data.customers.length"
                    :disabled="
                        form.processing ||
                        !form.status.length ||
                        (form.mobile.length === 0 &&
                            form.additional.length === 0)
                    "
                    :loading="form.processing"
                >
                    Submit
                </ButtonYellow>
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
import { ref } from "vue";

const props = defineProps(["id", "data", "statuses"]);

const form = useForm({
    campaign: "",
    status: [],
    mobile: [] as any,
    additional: [] as any,
});

const mobileOptions = ref<number[]>([]);
const additionalOptions = ref<number[]>([]);

const toggleParent = (type: string) => {
    if (type === "mobile") {
        form.mobile =
            form.mobile.length > 0
                ? []
                : mobileOptions.value.map((num) => `Mobile ${num}`);
    }

    if (type === "additional") {
        form.additional =
            form.additional.length > 0
                ? []
                : additionalOptions.value.map((num) => `Additional ${num}`);
    }
};

const toggleSubPhone = (value: string, type: string) => {
    if (type === "mobile") {
        if (form.mobile.includes(value)) {
            form.mobile = form.mobile.filter((item: any) => item !== value);
        } else {
            form.mobile.push(value);
        }
    }

    if (type === "additional") {
        if (form.additional.includes(value)) {
            form.additional = form.additional.filter(
                (item: any) => item !== value,
            );
        } else {
            form.additional.push(value);
        }
    }
};

const updateMaxMobile = (max: number) => {
    if (!max || max <= 0) {
        mobileOptions.value = [];
        form.mobile = [];
        return;
    }

    if (max < mobileOptions.value.length) {
        form.mobile = [];
    }

    mobileOptions.value = Array.from({ length: max }, (_, i) => i + 1);
};

const updateMaxAdditional = (max: number) => {
    if (!max || max <= 0) {
        additionalOptions.value = [];
        form.additional = [];
        return;
    }

    if (max < additionalOptions.value.length) {
        form.additional = [];
    }

    additionalOptions.value = Array.from({ length: max }, (_, i) => i + 1);
};

const submit = () => {
    if (!form.processing) {
        form.post(route("pds.detail.assign", props.id), {
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};
</script>
