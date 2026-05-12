<template>
    <Table :columns="columns" :paginate="paginate">
        <tr v-for="(row, i) in paginate.data.value">
            <Td>
                {{ row.date }}
            </Td>
            <Td>
                {{ row.name }}
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
            <Td> - </Td>
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
                            <span class="text-xs">Start Robocall</span>
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

    <div x-data="{confirmation:false}" v-if="showPopupDelete">
        <a hidden id="show-delete" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to delete this Robocall?"
            @action="actionDelete"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupRelease">
        <a hidden id="show-release" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to release customers this Robocall?"
            @action="actionRelease"
        />
    </div>

    <div x-data="{confirmation:false}" v-if="showPopupStop">
        <a hidden id="show-stop" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to stop this Robocall?"
            @action="actionStop"
        />
    </div>
</template>
<script setup lang="ts">
import IconPowerOff from "@/Components/Icon/Etc/IconPowerOff.vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { clickId, showAlert } from "@/Plugins/Function/global-function";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { router, useForm } from "@inertiajs/vue3";
import { ref, onBeforeUnmount, onMounted } from "vue";

const emits = defineEmits(["showStartPds"]);

const columns = ref([
    "Date",
    "Robocall Name",
    "Campaign Robocall",
    "Status Robocall",
    "Status",
    "Total Data",
    "Action",
]);

const showPopupRelease = ref(true);
const showPopupDelete = ref(true);
const showPopupStop = ref(true);

const form = useForm({
    id: "",
});

const paginate = usePaginate({
    route: route("robocall.setup.datatable"),
});

const actionDelete = () => {
    if (!form.processing) {
        form.post(route("robocall.setup.delete"), {
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

const actionRelease = () => {
    if (!form.processing) {
        form.post(route("robocall.setup.release"), {
            onError: () => {
                showPopupRelease.value = false;

                setTimeout(() => {
                    showPopupRelease.value = true;
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData();
                showPopupRelease.value = false;

                setTimeout(() => {
                    showPopupRelease.value = true;
                }, 100);
            },
        });
    }
};

const actionStop = () => {
    if (!form.processing) {
        form.post(route("robocall.setup.stop"), {
            onError: () => {
                showPopupStop.value = false;

                setTimeout(() => {
                    showPopupStop.value = true;
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData();
                showPopupStop.value = false;

                setTimeout(() => {
                    showPopupStop.value = true;
                }, 100);
            },
        });
    }
};

const showStart = (row: any) => {
    if (row.campaign_status == "active") {
        if (row.total_data == 0) {
            showAlert("Please upload customer data before starting");
        }
        {
            emits("showStartPds", row);
        }
    }
};

const showDelete = (row: any) => {
    if (row.total_data > 0) {
        showAlert("Please release customer data before delete robocall");
    } else {
        form.id = row.id;
        clickId("show-delete");
    }
};

const showRelease = (row: any) => {
    form.id = row.id;

    clickId("show-release");
};

const showStop = (row: any) => {
    form.id = row.id;

    clickId("show-stop");
};

const manage = (row: any) => {
    router.visit(route("robocall.detail", row.id));
};
</script>
