<template>
    <Table :columns="columns" :paginate="paginate" hide-th>
        <template #thead>
            <tr class="bg-[#F4F6FA]">
                <Th class="w-[20px] pt-2 pe-[14px]">
                    <input
                        type="checkbox"
                        :checked="allRowsSelected"
                        :disabled="!selectableRows.length"
                        @change="toggleSelectAll($event)"
                        class="w-4 h-4 accent-yellow cursor-pointer disabled:cursor-default mt-[-13px]"
                        aria-label="Select all PDS customers"
                    />
                </Th>
                <Th>Date</Th>
                <Th>PDS Name</Th>
                <Th>SPV</Th>
                <Th>Campaign PDS</Th>
                <Th>Status PDS</Th>
                <Th>Total Agent</Th>
                <Th>Total Data</Th>
                <Th>Action</Th>
            </tr>
        </template>
        <tr v-for="(row, i) in paginate.data.value" :key="row.id">
            <Td class="w-[20px] pt-2 pe-[14px]">
                <input
                    type="checkbox"
                    :checked="isRowSelected(row)"
                    @change="toggleRowSelection(row, $event)"
                    class="w-4 h-4 accent-yellow cursor-pointer mt-[-15px]"
                    aria-label="Select PDS customers"
                />
            </Td>
            <Td>
                {{ row.date }}
            </Td>
            <Td>
                {{ row.name }}
            </Td>
            <Td>
                <span
                    v-if="row.spv"
                    class="text-[#424EA1] font-krub-semibold underline cursor-pointer"
                    @click="showSpv(row)"
                    >View SPV</span
                >
                <span v-else>-</span>
            </Td>
            <Td>
                {{ row.campaign }}
            </Td>
            <Td>
                <span
                    class="text-red text-[13px] font-krub-semibold"
                    v-if="!row.is_running"
                    >Stop</span
                >
                <span
                    class="text-green text-[13px] font-krub-semibold"
                    v-if="row.is_running"
                    >Running</span
                >
            </Td>
            <Td>
                {{ row.total_agent ?? "-" }}
            </Td>
            <Td>
                {{ row.total_data ?? "-" }}
            </Td>
            <Td>
                <a
                    :x-on:click="`openRowIndex = '${i}'`"
                    class="rotate-90 cursor-pointer inline-flex"
                    x-ref="anchor"
                >
                    <i class="isax icon-more"></i>
                </a>

                <div
                    :x-show="`openRowIndex === '${i}'`"
                    x-anchor="$refs.anchor"
                    x-on:click.away="openRowIndex = ''"
                    class="absolute bg-white shadow-lg border rounded-md mt-1 min-w-[120px] z-50 p-2"
                >
                    <ul
                        class="text-sm text-dark flex flex-col gap-2"
                        v-if="!row.is_running"
                    >
                        <li
                            class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                            @click="manage(row)"
                        >
                            <i class="text-base isax icon-edit"></i>
                            <span class="text-xs">Manage</span>
                        </li>
                        <li
                            class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2 text-red"
                            @click="showDelete(row)"
                        >
                            <i class="text-base isax icon-trash"></i>
                            <span class="text-xs">Delete</span>
                        </li>
                        <li
                            class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                            @click="showRelease(row)"
                            v-if="row.total_data > 0"
                        >
                            <i class="text-base isax icon-refresh-circle"></i>
                            <span class="text-xs">Release Customers</span>
                        </li>
                        <li
                            class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                            @click="showStart(row)"
                            :class="{
                                '!text-[#DDD] !cursor-default':
                                    row.campaign_status == 'non_active',
                            }"
                        >
                            <i
                                class="text-base isax-b icon-play-circle text-green"
                                :class="{
                                    '!text-[#DDD]':
                                        row.campaign_status == 'non_active',
                                }"
                            ></i>
                            <span class="text-xs">Start PDS</span>
                        </li>
                    </ul>

                    <ul
                        class="text-sm text-dark flex flex-col gap-2"
                        v-if="row.is_running"
                    >
                        <li
                            class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                            @click="showStop(row)"
                        >
                            <IconPowerOff />
                            <span class="text-xs">Stop</span>
                        </li>
                    </ul>
                </div>
            </Td>
        </tr>
    </Table>

    <div
        v-if="selectedCount > 0"
        class="fixed bottom-5 left-1/2 -translate-x-1/2 z-[999] bg-white border border-[#E5E7EB] shadow-lg rounded-xl px-4 py-3 flex items-center gap-4"
    >
        <span class="text-dark text-[13px] font-krub-medium">
            {{ selectedCount }} selected
        </span>
        <ButtonYellow
            type="button"
            @click="showBulkReleaseConfirmation"
            v-if="hasReleaseSelection"
        >
            Release Customer
        </ButtonYellow>
        <ButtonYellow
            type="button"
            @click="showBulkStopConfirmation"
            v-if="hasStopSelection"
        >
            Stop PDS
        </ButtonYellow>
        <ButtonYellow type="button" @click="showBulkAssign" v-if="hasAssignSelection">
            Assign Customer
        </ButtonYellow>
        <ButtonYellow type="button" @click="showBulkStart" v-if="hasStartSelection">
            Start PDS
        </ButtonYellow>
    </div>

    <BulkAssignCustomer
        v-if="showBulkAssignPopup"
        :rows="assignableRows"
        @close="showBulkAssignPopup = false"
        @success="onBulkAssignSuccess"
    />

    <div x-data="{confirmation:false}" v-if="showPopupBulkRelease">
        <a hidden id="show-bulk-release" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to release the selected customers?"
            @action="actionBulkRelease"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupBulkStop">
        <a hidden id="show-bulk-stop" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to stop the selected PDS?"
            @action="actionBulkStop"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupRelease">
        <a hidden id="show-release" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to release customers this PDS?"
            @action="actionRelease"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupDelete">
        <a hidden id="show-delete" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to delete this PDS?"
            @action="actionDelete"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupStop">
        <a hidden id="show-stop" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to stop this PDS?"
            @action="actionStop"
        />
    </div>

    <Popup title="Spv" class="max-w-lg">
        <div class="flex flex-col gap-3 max-h-[80vh] overflow-y-auto">
            <div
                class="bg-[#F4F6FA] p-3 rounded-[4px] w-full text-[13px] text-[#181C32] font-opensauceone-medium flex justify-between items-center"
                @click="openSub('sub-' + spv?.id)"
                :class="{
                    'cursor-pointer': agents.length,
                }"
            >
                {{ spv?.company_user?.name }}
                <i
                    class="isax icon-arrow-down-1 text-base"
                    v-if="agents.length"
                ></i>
            </div>
            <div
                class="ms-5 hidden"
                :id="'sub-' + spv?.id"
                v-if="agents.length"
            >
                <div
                    v-for="agent in agents"
                    class="text-[13px] text-[#181C32] font-opensauceone-medium pt-3 flex items-center relative timeline"
                >
                    <span class="timeline-line-v"></span>
                    <span class="h-[1px] w-3 bg-[#181C32]"></span>
                    <span
                        class="h-[5px] w-[5px] rounded-full bg-[#181C32]"
                    ></span>
                    <span class="ms-2">{{ agent?.company_user?.name }}</span>
                </div>
            </div>
        </div>
    </Popup>

    <a hidden x-on:click="popup=true" id="show-popup-spv"></a>
</template>
<script setup lang="ts">
import IconPowerOff from "@/Components/Icon/Etc/IconPowerOff.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import BulkAssignCustomer from "./BulkAssignCustomer.vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";
import Popup from "@/Components/Popup/Index.vue";
import Table from "@/Components/Table/Table.vue";
import Th from "@/Components/Table/Th.vue";
import Td from "@/Components/Table/Td.vue";
import { clickId, showAlert } from "@/Plugins/Function/global-function";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { router, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const emits = defineEmits(["showStartPds", "showBulkStartPds"]);

const showPopupRelease = ref(true);
const showPopupBulkRelease = ref(true);
const showPopupBulkStop = ref(true);
const showPopupDelete = ref(true);
const showPopupStop = ref(true);

const selectedRowIds = ref<Array<string | number>>([]);
const selectedRows = ref<any[]>([]);
const selectedCount = computed(() => selectedRowIds.value.length);
const hasReleaseSelection = computed(() =>
    selectedRows.value.some((row: any) => row.total_data > 0),
);
const hasStopSelection = computed(() =>
    selectedRows.value.some((row: any) => row.is_running),
);
const assignableRows = computed(() => selectedRows.value.filter((row: any) => !row.is_running && row.total_data === 0 && row.campaign_status === "active"));
const hasAssignSelection = computed(() => assignableRows.value.length > 0);
const showBulkAssignPopup = ref(false);
const startableRows = computed(() => selectedRows.value.filter((row: any) =>
    !row.is_running &&
    row.campaign_status === "active" &&
    row.total_data > 0 &&
    row.total_agent > 0,
));
const hasStartSelection = computed(() => startableRows.value.length > 0);

const columns = ref([
    "",
    "Date",
    "PDS Name",
    "SPV",
    "Campaign PDS",
    "Status PDS",
    "Total Agent",
    "Total Data",
    "Action",
]);

const spv = ref<any>(null);
const agents = ref<any>([]);

const form = useForm({
    id: "",
    ids: [] as Array<string | number>,
});

const paginate = usePaginate({
    route: route("pds.setup.datatable"),
});

const selectableRows = computed(() =>
    paginate.data.value.filter((row: any) => !row.is_running),
);
const allRowsSelected = computed(
    () =>
        selectableRows.value.length > 0 &&
        selectableRows.value.every((row: any) => isRowSelected(row)),
);

const isRowSelected = (row: any) => selectedRowIds.value.includes(row.id);

const toggleRowSelection = (row: any, event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;

    if (checked && !isRowSelected(row)) {
        selectedRowIds.value.push(row.id);
        selectedRows.value.push(row);
    } else if (!checked) {
        selectedRowIds.value = selectedRowIds.value.filter(
            (id) => id !== row.id,
        );
        selectedRows.value = selectedRows.value.filter(
            (selectedRow) => selectedRow.id !== row.id,
        );
    }
};

const toggleSelectAll = (event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;
    const pageRowIds = selectableRows.value.map((row: any) => row.id);

    if (checked) {
        selectedRowIds.value = Array.from(
            new Set([...selectedRowIds.value, ...pageRowIds]),
        );
        const selectedIds = new Set(selectedRowIds.value);
        selectedRows.value = [
            ...selectedRows.value,
            ...selectableRows.value.filter(
                (row: any) =>
                    selectedIds.has(row.id) &&
                    !selectedRows.value.some(
                        (selectedRow) => selectedRow.id === row.id,
                    ),
            ),
        ];
    } else {
        selectedRowIds.value = selectedRowIds.value.filter(
            (id) => !pageRowIds.includes(id),
        );
        selectedRows.value = selectedRows.value.filter(
            (row) => !pageRowIds.includes(row.id),
        );
    }
};

const showBulkReleaseConfirmation = () => {
    clickId("show-bulk-release");
};

const showBulkStopConfirmation = () => {
    clickId("show-bulk-stop");
};

const showBulkAssign = () => { showBulkAssignPopup.value = true; };
const showBulkStart = () => { emits("showBulkStartPds", startableRows.value); };
const onBulkAssignSuccess = () => {
    showBulkAssignPopup.value = false;
    selectedRowIds.value = [];
    selectedRows.value = [];
    paginate.fetchData();
};

const actionBulkRelease = (finishConfirmation?: (close?: boolean) => void) => {
    form.ids = selectedRows.value
        .filter((row: any) => row.total_data > 0)
        .map((row: any) => row.id);
    submitRelease(true, finishConfirmation);
};

const actionBulkStop = (finishConfirmation?: (close?: boolean) => void) => {
    form.ids = selectedRows.value
        .filter((row: any) => row.is_running)
        .map((row: any) => row.id);
    submitStop(true, finishConfirmation);
};

const showStart = (row: any) => {
    if (row.campaign_status == "active") {
        if (row.total_data == 0) {
            showAlert("Please upload customer data before starting");
        } else if (row.total_agent == 0) {
            showAlert("Please assign agent before starting");
        } else {
            emits("showStartPds", row);
        }
    }
};

const actionDelete = () => {
    if (!form.processing) {
        form.post(route("pds.setup.delete"), {
            onError: () => {
                showPopupDelete.value = false;

                setTimeout(() => {
                    showPopupDelete.value = true;
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData();
                showPopupDelete.value = false;

                setTimeout(() => {
                    showPopupDelete.value = true;
                }, 100);
            },
        });
    }
};

const actionRelease = (finishConfirmation?: (close?: boolean) => void) => {
    submitRelease(false, finishConfirmation);
};

const submitRelease = (
    isBulk: boolean,
    finishConfirmation?: (close?: boolean) => void,
) => {
    const releasePopup = isBulk ? showPopupBulkRelease : showPopupRelease;

    if (!form.processing) {
        form.post(route("pds.setup.release"), {
            onError: () => {
                finishConfirmation?.(false);
                releasePopup.value = false;

                setTimeout(() => {
                    releasePopup.value = true;
                }, 100);
            },
            onSuccess: () => {
                finishConfirmation?.();
                paginate.fetchData();
                selectedRowIds.value = [];
                selectedRows.value = [];
                releasePopup.value = false;

                setTimeout(() => {
                    releasePopup.value = true;
                }, 100);
            },
        });
    }
};

const showDelete = (row: any) => {
    if (row.total_data > 0) {
        showAlert("Please release customer data before delete pds");
    } else if (row.total_agent > 0) {
        showAlert("Please release agent before delete pds");
    } else {
        form.id = row.id;
        clickId("show-delete");
    }
};

const showRelease = (row: any) => {
    form.id = row.id;
    form.ids = [row.id];

    clickId("show-release");
};

const actionStop = (finishConfirmation?: (close?: boolean) => void) => {
    submitStop(false, finishConfirmation);
};

const submitStop = (
    isBulk: boolean,
    finishConfirmation?: (close?: boolean) => void,
) => {
    const stopPopup = isBulk ? showPopupBulkStop : showPopupStop;

    if (!form.processing) {
        form.post(route("pds.setup.stop"), {
            onError: () => {
                finishConfirmation?.(false);
                stopPopup.value = false;

                setTimeout(() => {
                    stopPopup.value = true;
                }, 100);
            },
            onSuccess: () => {
                finishConfirmation?.();
                paginate.fetchData();
                selectedRowIds.value = [];
                selectedRows.value = [];
                stopPopup.value = false;

                setTimeout(() => {
                    stopPopup.value = true;
                }, 100);
            },
        });
    }
};

const showStop = (row: any) => {
    form.id = row.id;
    form.ids = [row.id];

    clickId("show-stop");
};

const manage = (row: any) => {
    router.visit(route("pds.detail", row.id));
};

const openSub = (id: string) => {
    document.getElementById(id)?.classList.toggle("hidden");
};

const showSpv = (item: any) => {
    agents.value = item.agents;
    spv.value = item.spv;

    clickId("show-popup-spv");
};
</script>
