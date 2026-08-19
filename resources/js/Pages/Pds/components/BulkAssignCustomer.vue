<template>
    <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close"></div>

        <form
            class="relative flex h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white"
            @submit.prevent="submit"
        >
            <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
                <div>
                    <h3 class="text-[16px] font-krub-semibold text-dark">
                        Assign Customer
                    </h3>
                    <p class="mt-1 text-[11px] text-slate-500">
                        Configure each PDS separately ({{ currentIndex + 1 }} of
                        {{ assignments.length }})
                    </p>
                </div>
                <button
                    type="button"
                    class="text-xl text-gray-500"
                    aria-label="Close"
                    @click="close"
                >
                    &times;
                </button>
            </div>

            <div
                class="flex shrink-0 gap-2 overflow-x-auto border-b bg-slate-50 px-6 py-3"
            >
                <button
                    v-for="(assignment, index) in assignments"
                    :key="assignment.pds_id"
                    type="button"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-krub-medium"
                    :class="
                        index === currentIndex
                            ? 'border-[#3943B7] bg-[#3943B7] text-white'
                            : isAssignmentValid(assignment)
                              ? 'border-green-500 bg-white text-green-700'
                              : 'border-slate-300 bg-white text-slate-600'
                    "
                    @click="currentIndex = index"
                >
                    {{ index + 1 }}. {{ assignment.pds_name }}
                </button>
            </div>

            <div v-if="current" class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                <div class="mb-5 rounded-lg bg-[#F4F6FA] px-4 py-3">
                    <div class="text-[10px] text-slate-500">PDS Name</div>
                    <div class="mt-1 text-[13px] font-krub-semibold text-dark">
                        {{ current.pds_name }}
                    </div>
                </div>

                <Select
                    :key="`type-${current.pds_id}`"
                    v-model="current.type"
                    label="Type"
                    :error="fieldError(currentIndex, 'type')"
                    required
                    @change="changeType(current)"
                >
                    <option value="" selected>Select Type</option>
                    <option value="HO">HO</option>
                    <option value="Branch">Branch</option>
                </Select>

                <div
                    class="mt-4 mb-4 border rounded-xl overflow-visible"
                    data-bulk-risk-criteria
                >
                    <div
                        class="flex items-center justify-between bg-[#F3F3F3] px-4 py-3"
                    >
                        <span class="text-xs font-opensauceone-medium text-slate-700">
                            Criteria &amp; Overdue Settings
                        </span>
                        <span
                            class="text-[10px] font-opensauceone-medium text-[#3943B7]"
                        >
                            Select at least one range per criteria
                        </span>
                    </div>

                    <div class="space-y-4 p-4">
                        <div
                            v-for="risk in riskOptions(current)"
                            :key="`${current.pds_id}-${risk.value}`"
                            class="grid grid-cols-[150px_1fr] gap-x-4 p-4 border rounded-xl"
                            :class="{
                                'border-[#3943B7] shadow-sm':
                                    current.risk_criteria[risk.value].length,
                            }"
                        >
                            <label
                                class="flex items-center gap-x-3 h-[38px] cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    class="shrink-0 mb-4"
                                    :checked="
                                        current.risk_criteria[risk.value].length > 0
                                    "
                                    @change="toggleRisk(current, risk)"
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
                                            !current.risk_criteria[risk.value].length,
                                    }"
                                    @click="toggleRiskDropdown(current, risk.value)"
                                >
                                    <div class="flex-1 flex flex-wrap items-center gap-2">
                                        <template
                                            v-if="current.risk_criteria[risk.value].length"
                                        >
                                            <span
                                                v-for="day in current.risk_criteria[
                                                    risk.value
                                                ]"
                                                :key="`${risk.value}-${day}`"
                                                class="inline-flex items-center px-2 py-1 bg-[#3943B7] text-white rounded-full text-xs"
                                            >
                                                {{ day }} ×
                                            </span>
                                        </template>
                                        <span v-else>
                                            Click to select days {{ risk.rangeLabel }}
                                        </span>
                                    </div>
                                    <span class="shrink-0 text-slate-500">
                                        <i
                                            v-if="
                                                current.active_risk_dropdown ===
                                                risk.value
                                            "
                                            class="isax icon-arrow-up-2"
                                        ></i>
                                        <i v-else class="isax icon-arrow-down-1"></i>
                                    </span>
                                </button>

                                <div
                                    v-if="current.active_risk_dropdown === risk.value"
                                    class="absolute z-20 mt-2 w-full bg-white border rounded-lg shadow-lg p-4"
                                >
                                    <div class="flex items-center justify-between mb-3">
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
                                                    selectAllRiskDays(current, risk)
                                                "
                                            >
                                                All
                                            </button>
                                            <button
                                                type="button"
                                                class="text-[10px] font-opensauceone-medium text-slate-500"
                                                @click.stop="
                                                    clearRisk(current, risk.value)
                                                "
                                            >
                                                None
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 sm:grid-cols-7 gap-3">
                                        <button
                                            v-for="day in risk.days"
                                            :key="`${risk.value}-day-${day}`"
                                            type="button"
                                            class="h-8 rounded-lg border text-xs font-opensauceone-medium transition-colors"
                                            :class="
                                                current.risk_criteria[
                                                    risk.value
                                                ].includes(day)
                                                    ? 'bg-[#3943B7] text-white border-[#3943B7]'
                                                    : 'bg-slate-50 text-slate-700 hover:border-[#3943B7]'
                                            "
                                            @click.stop="
                                                toggleRiskDay(
                                                    current,
                                                    risk.value,
                                                    day,
                                                )
                                            "
                                        >
                                            {{ day }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small
                            v-if="fieldError(currentIndex, 'risk_criteria')"
                            class="error-text text-[11px]"
                        >
                            {{ fieldError(currentIndex, "risk_criteria") }}
                        </small>
                    </div>
                </div>

                <div
                    v-if="current.type === 'Branch' && current.loading_options"
                    class="mb-4 text-xs text-slate-500"
                >
                    Loading offices...
                </div>
                <MultipleSelect
                    v-if="current.type === 'Branch' && !current.loading_options"
                    :key="`offices-${current.pds_id}-${current.office_select_version}`"
                    v-model="current.offices"
                    label="Office"
                    :id="`offices-${current.pds_id}`"
                    :items="current.office_options"
                    :selected="current.offices"
                    :error="fieldError(currentIndex, 'offices')"
                    placeholder="Select Office"
                    required
                    @updateSelectedValue="officeSelected(current)"
                />

                <div v-if="current.loading_status" class="mb-4 text-xs text-slate-500">
                    Loading status...
                </div>
                <MultipleSelect
                    v-else
                    :key="`status-${current.pds_id}-${current.status_select_version}`"
                    v-model="current.status"
                    label="Status"
                    :id="`status-${current.pds_id}`"
                    :items="current.status_options"
                    :selected="current.status"
                    :error="fieldError(currentIndex, 'status')"
                    placeholder="Select Status"
                    required
                    @updateMaxMobile="updateMaxMobile(current, $event)"
                    @updateMaxAdditional="updateMaxAdditional(current, $event)"
                />

                <div class="space-y-4">
                    <PhoneOptions
                        v-if="current.mobile_options.length"
                        v-model="current.mobile"
                        label="Mobile"
                        prefix="Mobile"
                        :options="current.mobile_options"
                    />
                    <PhoneOptions
                        v-if="current.additional_options.length"
                        v-model="current.additional"
                        label="Additional"
                        prefix="Additional"
                        :options="current.additional_options"
                    />
                    <small
                        v-if="fieldError(currentIndex, 'mobile')"
                        class="error-text text-[11px]"
                    >
                        {{ fieldError(currentIndex, "mobile") }}
                    </small>
                </div>
            </div>

            <div
                class="flex shrink-0 items-center justify-between gap-3 border-t bg-white px-6 py-4"
            >
                <ButtonOutlineGrey type="button" class="w-[120px]" @click="close">
                    Cancel
                </ButtonOutlineGrey>

                <div class="flex items-center gap-2">
                    <ButtonOutlineGrey
                        v-if="currentIndex > 0"
                        type="button"
                        class="w-[120px]"
                        @click="currentIndex--"
                    >
                        Previous
                    </ButtonOutlineGrey>
                    <ButtonYellow
                        v-if="currentIndex < assignments.length - 1"
                        type="button"
                        class="w-[120px]"
                        :disabled="!isAssignmentValid(current)"
                        @click="currentIndex++"
                    >
                        Next
                    </ButtonYellow>
                    <ButtonYellow
                        v-else
                        type="submit"
                        class="w-[150px]"
                        :disabled="form.processing || !allAssignmentsValid"
                        :loading="form.processing"
                    >
                        Assign Customer
                    </ButtonYellow>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import Select from "@/Components/Input/Select.vue";
import MultipleSelect from "@/Components/Input/Select/MultipleSelect.vue";
import PhoneOptions from "./BulkAssignCustomerPhoneOptions.vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";

type RiskKey = "low_risk" | "medium_risk" | "high_risk";
type Option = {
    id: string;
    value: string;
    max_mobile?: number;
    max_additional_phone?: number;
};
type Assignment = {
    pds_id: string | number;
    pds_name: string;
    type: string;
    offices: string[];
    status: string[];
    mobile: string[];
    additional: string[];
    risk_criteria: Record<RiskKey, number[]>;
    office_options: Option[];
    status_options: Option[];
    mobile_options: number[];
    additional_options: number[];
    loading_options: boolean;
    loading_status: boolean;
    request_version: number;
    active_risk_dropdown: RiskKey | null;
    office_select_version: number;
    status_select_version: number;
};

const props = defineProps<{ rows: any[] }>();
const emit = defineEmits(["close", "success"]);
const currentIndex = ref(0);
const statusTimers = new Map<string | number, ReturnType<typeof setTimeout>>();

const assignments = reactive<Assignment[]>(
    props.rows.map((row) => ({
        pds_id: row.id,
        pds_name: row.name,
        type: "",
        offices: [],
        status: [],
        mobile: [],
        additional: [],
        risk_criteria: { low_risk: [], medium_risk: [], high_risk: [] },
        office_options: [],
        status_options: [],
        mobile_options: [],
        additional_options: [],
        loading_options: true,
        loading_status: false,
        request_version: 0,
        active_risk_dropdown: null,
        office_select_version: 0,
        status_select_version: 0,
    })),
);

const form = useForm({ assignments: [] as any[] });
const current = computed(() => assignments[currentIndex.value]);
const hasRiskCriteria = (assignment: Assignment) =>
    Object.values(assignment.risk_criteria).some((days) => days.length > 0);
const isAssignmentValid = (assignment?: Assignment) =>
    !!assignment &&
    !!assignment.type &&
    hasRiskCriteria(assignment) &&
    (assignment.type !== "Branch" || assignment.offices.length > 0) &&
    assignment.status.length > 0 &&
    (assignment.mobile.length > 0 || assignment.additional.length > 0);
const allAssignmentsValid = computed(() => assignments.every(isAssignmentValid));

const riskOptions = (assignment: Assignment) => {
    if (assignment.type === "Branch") {
        return [
            { label: "Low Risk", value: "low_risk" as RiskKey, days: range(15, 21), rangeLabel: "(15-21)" },
            { label: "Medium Risk", value: "medium_risk" as RiskKey, days: range(8, 14), rangeLabel: "(8-14)" },
            { label: "High Risk", value: "high_risk" as RiskKey, days: range(4, 7), rangeLabel: "(4-7)" },
        ];
    }

    return [
        { label: "Low Risk", value: "low_risk" as RiskKey, days: range(1, 14), rangeLabel: "(1-14)" },
        { label: "Medium Risk", value: "medium_risk" as RiskKey, days: range(1, 7), rangeLabel: "(1-7)" },
        { label: "High Risk", value: "high_risk" as RiskKey, days: range(1, 3), rangeLabel: "(1-3)" },
    ];
};

function range(start: number, end: number) {
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
}

const resetResults = (assignment: Assignment) => {
    assignment.status = [];
    assignment.mobile = [];
    assignment.additional = [];
    assignment.status_options = [];
    assignment.mobile_options = [];
    assignment.additional_options = [];
    assignment.status_select_version++;
};

const changeType = (assignment: Assignment) => {
    assignment.offices = [];
    assignment.risk_criteria = { low_risk: [], medium_risk: [], high_risk: [] };
    assignment.active_risk_dropdown = null;
    resetResults(assignment);
};

const toggleRisk = (assignment: Assignment, risk: ReturnType<typeof riskOptions>[number]) => {
    assignment.risk_criteria[risk.value] = assignment.risk_criteria[risk.value].length
        ? []
        : [...risk.days];
    assignment.active_risk_dropdown = null;
    filtersChanged(assignment);
};

const toggleRiskDropdown = (assignment: Assignment, risk: RiskKey) => {
    assignment.active_risk_dropdown =
        assignment.active_risk_dropdown === risk ? null : risk;
};

const selectAllRiskDays = (
    assignment: Assignment,
    risk: ReturnType<typeof riskOptions>[number],
) => {
    assignment.risk_criteria[risk.value] = [...risk.days];
    filtersChanged(assignment);
};

const clearRisk = (assignment: Assignment, risk: RiskKey) => {
    assignment.risk_criteria[risk] = [];
    filtersChanged(assignment);
};

const toggleRiskDay = (assignment: Assignment, risk: RiskKey, day: number) => {
    const selected = assignment.risk_criteria[risk];
    assignment.risk_criteria[risk] = selected.includes(day)
        ? selected.filter((value) => value !== day)
        : [...selected, day].sort((a, b) => a - b);
    filtersChanged(assignment);
};

const officeSelected = (assignment: Assignment) => {
    filtersChanged(assignment);
};

const updateMaxMobile = (assignment: Assignment, max: number) => {
    if (!max || max <= 0) {
        assignment.mobile_options = [];
        assignment.mobile = [];
        return;
    }

    if (max < assignment.mobile_options.length) assignment.mobile = [];
    assignment.mobile_options = range(1, max);
};

const updateMaxAdditional = (assignment: Assignment, max: number) => {
    if (!max || max <= 0) {
        assignment.additional_options = [];
        assignment.additional = [];
        return;
    }

    if (max < assignment.additional_options.length) assignment.additional = [];
    assignment.additional_options = range(1, max);
};

const selectedRiskCriteria = (assignment: Assignment) =>
    Object.entries(assignment.risk_criteria).flatMap(([risk, days]) =>
        days.map((number) => ({ risk, number })),
    );

const filtersChanged = (assignment: Assignment) => {
    resetResults(assignment);
    assignment.request_version++;
    const existingTimer = statusTimers.get(assignment.pds_id);
    if (existingTimer) clearTimeout(existingTimer);
    statusTimers.set(
        assignment.pds_id,
        setTimeout(() => fetchStatuses(assignment), 250),
    );
};

const fetchStatuses = async (assignment: Assignment) => {
    const riskCriteria = selectedRiskCriteria(assignment);
    if (
        !assignment.type ||
        !riskCriteria.length ||
        (assignment.type === "Branch" && !assignment.offices.length)
    ) {
        assignment.loading_status = false;
        return;
    }

    const requestVersion = ++assignment.request_version;
    assignment.loading_status = true;
    try {
        const response = await axios.get(route("pds.detail.status", assignment.pds_id), {
            params: {
                type: assignment.type,
                offices: assignment.offices,
                risk_criteria: riskCriteria,
            },
        });
        if (requestVersion === assignment.request_version) {
            assignment.status_options = response.data.data || [];
            assignment.status_select_version++;
        }
    } finally {
        if (requestVersion === assignment.request_version) {
            assignment.loading_status = false;
        }
    }
};

const loadOffices = async (assignment: Assignment) => {
    try {
        const response = await axios.get(route("pds.detail.options", assignment.pds_id));
        assignment.office_options = response.data.offices || [];
    } finally {
        assignment.loading_options = false;
    }
};

const fieldError = (index: number, field: string) =>
    (form.errors as Record<string, string>)[`assignments.${index}.${field}`];

const submit = () => {
    if (form.processing || !allAssignmentsValid.value) return;

    form.assignments = assignments.map((assignment) => ({
        pds_id: assignment.pds_id,
        type: assignment.type,
        offices: assignment.offices,
        status: assignment.status,
        mobile: assignment.mobile,
        additional: assignment.additional,
        risk_criteria: assignment.risk_criteria,
    }));
    form.post(route("pds.setup.bulk-assign"), {
        preserveScroll: true,
        onError: (errors) => {
            const firstError = Object.keys(errors).find((key) =>
                key.startsWith("assignments."),
            );
            if (firstError) {
                currentIndex.value = Number(firstError.split(".")[1]) || 0;
            }
        },
        onSuccess: () => emit("success"),
    });
};

const close = () => {
    if (!form.processing) emit("close");
};

const closeRiskDropdowns = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest("[data-bulk-risk-criteria]")) {
        assignments.forEach((assignment) => {
            assignment.active_risk_dropdown = null;
        });
    }
};

onMounted(() => {
    document.addEventListener("mousedown", closeRiskDropdowns);
    return Promise.all(assignments.map(loadOffices));
});
onBeforeUnmount(() => {
    document.removeEventListener("mousedown", closeRiskDropdowns);
    statusTimers.forEach((timer) => clearTimeout(timer));
});
</script>
