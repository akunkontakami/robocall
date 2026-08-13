<template>
<form @submit.prevent="submit">
            <div class="bg-white max-w-3xl p-4 mx-auto">
                <template v-if="showDetails !== false">
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
                </template>

                <Select
                    label="Type"
                    id="type"
                    v-model="form.type"
                    :error="form.errors.type"
                    placeholder="Select Type"
                    v-if="!data.customers.length && data.campaign"
                >
                    <option value="" disabled selected>Select Type</option>
                    <option value="HO">HO</option>
                    <option value="Branch">Branch</option>
                </Select>

                <!-- Risk Criteria -->
                <div
                    ref="riskCriteriaRef"
                    class="mt-4 mb-4 border rounded-xl overflow-visible"
                    v-if="!data.customers.length && data.campaign"
                >
                    <div
                        class="flex items-center justify-between bg-[#F3F3F3] px-4 py-3"
                    >
                        <span
                            class="text-xs font-opensauceone-medium text-slate-700"
                        >
                            Criteria & Overdue Settings
                        </span>

                        <span
                            class="text-[10px] font-opensauceone-medium text-[#3943B7]"
                        >
                            Select at least one range per criteria
                        </span>
                    </div>

                    <div class="space-y-4 p-4">
                        <div
                            v-for="risk in riskCriteriaOptions"
                            :key="risk.value"
                            class="grid grid-cols-[150px_1fr] gap-x-4 p-4 border rounded-xl"
                            :class="{
                                'border-[#3943B7] shadow-sm':
                                    form.risk_criteria[risk.value]?.length,
                            }"
                        >
                            <label
                                class="flex items-center gap-x-3 h-[38px] cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    class="shrink-0 mb-4"
                                    :checked="
                                        form.risk_criteria[risk.value]?.length >
                                        0
                                    "
                                    @change="toggleRisk(risk.value)"
                                />

                                <span
                                    class="text-xs font-opensauceone-medium text-slate-700"
                                >
                                    {{ risk.label }}
                                </span>
                            </label>

                            <div class="relative">
                                <button
                                    type="button"
                                    class="w-full min-h-[38px] px-4 py-2 border rounded-lg text-left text-xs flex items-center gap-x-3"
                                    :class="{
                                        'text-slate-400 bg-slate-50':
                                            !form.risk_criteria[risk.value]
                                                ?.length,
                                    }"
                                    @click="toggleDropdown(risk.value)"
                                >
                                    <div
                                        class="flex-1 flex flex-wrap items-center gap-2"
                                    >
                                        <template
                                            v-if="
                                                form.risk_criteria[risk.value]
                                                    ?.length
                                            "
                                        >
                                            <span
                                                v-for="day in form
                                                    .risk_criteria[risk.value]"
                                                :key="`${risk.value}-${day}`"
                                                class="inline-flex items-center px-2 py-1 bg-[#3943B7] text-white rounded-full text-xs"
                                            >
                                                {{ day }} ×
                                            </span>
                                        </template>

                                        <span v-else>
                                            Click to select days
                                            {{ risk.rangeLabel }}
                                        </span>
                                    </div>

                                    <span class="shrink-0 text-slate-500">
                                        <i
                                            class="isax icon-arrow-up-2"
                                            v-if="
                                                activeRiskDropdown ===
                                                risk.value
                                            "
                                        ></i>
                                        <i
                                            class="isax icon-arrow-down-1"
                                            v-else
                                        ></i>
                                    </span>
                                </button>

                                <div
                                    v-if="activeRiskDropdown === risk.value"
                                    class="absolute z-20 mt-2 w-full bg-white border rounded-lg shadow-lg p-4"
                                >
                                    <div
                                        class="flex items-center justify-between mb-3"
                                    >
                                        <span
                                            class="text-[10px] font-opensauceone-medium text-slate-500"
                                        >
                                            Select OD Days
                                        </span>

                                        <div class="flex items-center gap-x-2">
                                            <button
                                                type="button"
                                                class="text-[10px] font-opensauceone-medium text-[#3943B7]"
                                                @click.stop="
                                                    selectAllRiskDays(
                                                        risk.value,
                                                    )
                                                "
                                            >
                                                All
                                            </button>

                                            <button
                                                type="button"
                                                class="text-[10px] font-opensauceone-medium text-slate-500"
                                                @click.stop="
                                                    clearRiskDays(risk.value)
                                                "
                                            >
                                                None
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-4 sm:grid-cols-7 gap-3"
                                    >
                                        <button
                                            v-for="day in risk.days"
                                            :key="`${risk.value}-day-${day}`"
                                            type="button"
                                            class="h-8 rounded-lg border text-xs font-opensauceone-medium transition-colors"
                                            :class="
                                                form.risk_criteria[
                                                    risk.value
                                                ]?.includes(day)
                                                    ? 'bg-[#3943B7] text-white border-[#3943B7]'
                                                    : 'bg-slate-50 text-slate-700 hover:border-[#3943B7]'
                                            "
                                            @click.stop="
                                                toggleRiskDay(risk.value, day)
                                            "
                                        >
                                            {{ day }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="form.errors.risk_criteria"
                            class="text-xs text-red-500"
                        >
                            {{ form.errors.risk_criteria }}
                        </div>
                    </div>
                </div>

                <MultipleSelect
                    label="Office"
                    id="offices"
                    v-model="form.offices"
                    :items="officeOptions"
                    :error="form.errors.offices"
                    placeholder="Select Office"
                    @updateSelectedValue="updateOfficeSelectedValue"
                    v-if="!data.customers.length && data.campaign && showOffice"
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
                    v-if="
                        !data.customers.length &&
                        data.campaign &&
                        !loadingStatus
                    "
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
                                class="ml-4 grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-lg border"
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
                                class="ml-4 grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-slate-50 rounded-lg border"
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

                <div class="flex items-center gap-2 mt-10">
                <ButtonYellow
                    type="submit"
                    class="w-[120px]"
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

                <ButtonOutlineGrey
                    type="button"
                    class="w-[120px]"
                    v-if="showCancel"
                    @click="$emit('cancel')"
                >
                    Cancel
                </ButtonOutlineGrey>
                </div>
            </div>
        </form>
</template>
<script setup lang="ts">
import Input from "@/Components/Input/Index.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import MultipleSelect from "@/Components/Input/Select/MultipleSelect.vue";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import axios from "axios";
import Select from "@/Components/Input/Select.vue";

const props = defineProps(["id", "data", "offices", "ids", "showCancel", "showDetails"]);
const emit = defineEmits(["success", "cancel"]);
const officeOptions = ref<any>(props.offices || []);

const statuses = ref<any>([]);
const loadingStatus = ref(false);
const showOffice = ref(false);
const riskCriteriaRef = ref<HTMLElement | null>(null);

const form = useForm({
    ids: props.ids?.length ? props.ids : [props.id],
    campaign: "",
    type: "",
    status: [],
    offices: [],
    mobile: [] as any,
    additional: [] as any,
    risk_criteria: {
        low_risk: [] as number[],
        medium_risk: [] as number[],
        high_risk: [] as number[],
    } as any,
});

const activeRiskDropdown = ref<string | null>(null);
const riskDropdownSnapshot = ref<string>("");

const riskCriteriaOptions = computed(() => {
    if (form.type === "Branch") {
        return [
            {
                label: "Low Risk",
                value: "low_risk",
                days: Array.from({ length: 7 }, (_, i) => i + 15), // 15-21
                rangeLabel: "(15-21)",
            },
            {
                label: "Medium Risk",
                value: "medium_risk",
                days: Array.from({ length: 7 }, (_, i) => i + 8), // 8-14
                rangeLabel: "(8-14)",
            },
            {
                label: "High Risk",
                value: "high_risk",
                days: Array.from({ length: 4 }, (_, i) => i + 4), // 4-7
                rangeLabel: "(4-7)",
            },
        ];
    }

    // HO
    return [
        {
            label: "Low Risk",
            value: "low_risk",
            days: Array.from({ length: 14 }, (_, i) => i + 1), // 1-14
            rangeLabel: "(1-14)",
        },
        {
            label: "Medium Risk",
            value: "medium_risk",
            days: Array.from({ length: 7 }, (_, i) => i + 1), // 1-7
            rangeLabel: "(1-7)",
        },
        {
            label: "High Risk",
            value: "high_risk",
            days: Array.from({ length: 3 }, (_, i) => i + 1), // 1-3
            rangeLabel: "(1-3)",
        },
    ];
});

const mobileOptions = ref<number[]>([]);
const additionalOptions = ref<number[]>([]);

const getRiskDaysSnapshot = (risk: any) => {
    return JSON.stringify(
        [...(form.risk_criteria[risk] || [])].sort(
            (a: number, b: number) => a - b,
        ),
    );
};

const openRiskDropdown = (risk: any) => {
    activeRiskDropdown.value = risk;
    riskDropdownSnapshot.value = getRiskDaysSnapshot(risk);
};

const closeRiskDropdown = () => {
    if (!activeRiskDropdown.value) {
        return;
    }

    const currentSnapshot = getRiskDaysSnapshot(activeRiskDropdown.value);
    const hasChanged = currentSnapshot !== riskDropdownSnapshot.value;

    activeRiskDropdown.value = null;
    riskDropdownSnapshot.value = "";

    if (hasChanged) {
        resetStatusAndPhoneOptions();
        fetchStatuses();
    }
};

const toggleDropdown = (risk: any) => {
    if (activeRiskDropdown.value === risk) {
        closeRiskDropdown();
        return;
    }

    if (activeRiskDropdown.value) {
        closeRiskDropdown();
    }

    openRiskDropdown(risk);
};

const toggleRisk = (risk: any) => {
    const currentValue = form.risk_criteria[risk];

    if (currentValue.length > 0) {
        form.risk_criteria[risk] = [];
    } else {
        const selectedRisk = riskCriteriaOptions.value.find(
            (item) => item.value === risk,
        );

        form.risk_criteria[risk] = selectedRisk ? [...selectedRisk.days] : [];
    }

    if (activeRiskDropdown.value === risk) {
        activeRiskDropdown.value = null;
        riskDropdownSnapshot.value = "";
    }

    resetStatusAndPhoneOptions();
    fetchStatuses();
};

const toggleRiskDay = (risk: any, day: number) => {
    if (form.risk_criteria[risk].includes(day)) {
        form.risk_criteria[risk] = form.risk_criteria[risk].filter(
            (item: number) => item !== day,
        );
    } else {
        form.risk_criteria[risk].push(day);
        form.risk_criteria[risk].sort((a: number, b: number) => a - b);
    }
};

const selectAllRiskDays = (risk: any) => {
    const selectedRisk = riskCriteriaOptions.value.find(
        (item) => item.value === risk,
    );

    form.risk_criteria[risk] = selectedRisk ? [...selectedRisk.days] : [];
};

const clearRiskDays = (risk: any) => {
    form.risk_criteria[risk] = [];
};

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

const getSelectedRiskCriteria = () => {
    return Object.entries(form.risk_criteria).flatMap(([risk, numbers]) =>
        (numbers as number[]).map((number) => ({
            risk,
            number,
        })),
    );
};

const resetStatusAndPhoneOptions = () => {
    form.status = [];
    form.mobile = [];
    form.additional = [];
    mobileOptions.value = [];
    additionalOptions.value = [];
};

const updateTypeSelectedValue = () => {
    showOffice.value = form.type === "Branch";

    form.risk_criteria = {
        low_risk: [],
        medium_risk: [],
        high_risk: [],
    };

    form.offices = [];

    activeRiskDropdown.value = null;
    riskDropdownSnapshot.value = "";

    resetStatusAndPhoneOptions();

    fetchStatuses();
};

const updateOfficeSelectedValue = (row: any) => {
    fetchStatuses();
};

const fetchStatuses = () => {
    loadingStatus.value = true;
    const riskCriteria = getSelectedRiskCriteria();

    if (!riskCriteria.length || !form.type) {
        statuses.value = [];
        setTimeout(() => {
            loadingStatus.value = false;
            return;
        }, 100);
    }

    axios
        .get(route("pds.detail.status", props.id), {
            params: {
                risk_criteria: riskCriteria,
                offices: form.offices,
                type: form.type,
            },
        })
        .then((res) => {
            statuses.value = res.data.data;
        })
        .finally(() => {
            loadingStatus.value = false;
        });
};

const handleClickOutsideRiskCriteria = (event: MouseEvent) => {
    if (
        riskCriteriaRef.value &&
        !riskCriteriaRef.value.contains(event.target as Node)
    ) {
        closeRiskDropdown();
    }
};

const submit = () => {
    if (!form.processing) {
        form.post(route("pds.detail.assign", props.id), {
            onSuccess: () => {
                if (props.ids?.length > 1) emit("success");
                else window.location.reload();
            },
        });
    }
};


watch(()=>form.type,()=>{
    updateTypeSelectedValue()
})
onMounted(() => {
    if (props.ids?.length > 1) {
        axios.get(route("pds.detail.options", props.id))
            .then((response) => { officeOptions.value = response.data.offices || []; });
    }
});

onMounted(() => {
    document.addEventListener("mousedown", handleClickOutsideRiskCriteria);
});

onBeforeUnmount(() => {
    document.removeEventListener("mousedown", handleClickOutsideRiskCriteria);
});
</script>

