<template>
    <AppLayout
        title="Robocall Detail"
        :header="data.robocall_name"
        :headerBackUrl="route('robocall.setup')"
    >
        <template v-slot:tab>
            <TabMenu tab="upload" :id="id" :data="data" />
        </template>

        <div x-data="{openRowIndex: ''}">
            <div class="flex justify-between">
                <TableSearch />
                <div class="flex gap-2">
                    <ButtonYellow
                        class="ms-auto mb-3"
                        icon="isax icon-add"
                        @click="downloadTemplate"
                    >
                        Download Template
                    </ButtonYellow>
                    <ButtonYellow
                        class="mb-3"
                        @click="openUpload"
                        :loading="form.processing"
                        :disabled="form.processing"
                        v-if="!data.data_type"
                    >
                        Upload
                    </ButtonYellow>

                    <input
                        ref="fileInput"
                        type="file"
                        accept=".csv"
                        class="hidden"
                        @change="upload"
                    />
                </div>
            </div>
            <Table :columns="columns" :paginate="paginate">
                <tr v-for="(row, i) in paginate.data.value">
                    <Td>
                        {{ row.date }}
                    </Td>
                    <Td>
                        {{ row.name }}
                    </Td>
                    <Td>
                        {{ row.progress }}
                    </Td>
                    <Td>
                        {{ row.total_data }}
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
                            v-if="!form.processing"
                        >
                            <ul
                                class="text-sm text-dark flex flex-col gap-2"
                                v-if="!props.data.is_running"
                            >
                                <li
                                    class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2"
                                    @click="selectFromInject(row)"
                                >
                                    <i class="text-base isax icon-flash-1"></i>
                                    <span class="text-xs">Inject</span>
                                </li>
                                <li
                                    class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2 text-red"
                                    @click="showDelete(row)"
                                >
                                    <i class="text-base isax icon-trash"></i>
                                    <span class="text-xs">Delete</span>
                                </li>
                            </ul>
                        </div>
                    </Td>
                </tr>
            </Table>
        </div>

        <div x-data="{confirmation:false}" v-if="showPopupDelete">
            <a hidden id="show-delete" x-on:click="confirmation=true"></a>
            <ConfirmationSubmit
                confirmation="Are you sure you want to delete this Upload?"
                @action="actionDelete"
            />
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import Input from "@/Components/Input/Index.vue";
import ButtonYellow from "@/Components/Button/ButtonYellow.vue";
import ButtonOutlineGrey from "@/Components/Button/ButtonOutlineGrey.vue";
import { useForm } from "@inertiajs/vue3";
import TabMenu from "./components/TabMenu.vue";
import { ref } from "vue";
import axios from "axios";
import TableSearch from "@/Components/Table/TableSearch.vue";
import Table from "@/Components/Table/Table.vue";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import Td from "@/Components/Table/Td.vue";
import { clickId, showAlert } from "@/Plugins/Function/global-function";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";

const props = defineProps(["id", "data", "template"]);

const columns = ref([
    "Date",
    "Filename",
    "Upload Progress",
    "Total Data",
    "Action",
]);

const showPopupDelete = ref(true);

const paginate = usePaginate({
    route: route("robocall.detail.upload-datatable", props.id),
});

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    id: "",
    file: null as File | null,
});

const downloadTemplate = () => {
    window.open(props.template);
};

const openUpload = () => {
    fileInput.value?.click();
    form.id = "";
};

const selectFromInject = (row: any) => {
    fileInput.value?.click();
    form.id = row.id;
};

const upload = (event: Event) => {
    const target = event.target as HTMLInputElement;

    if (!target.files || !target.files[0]) return;

    form.file = target.files[0];

    form.post(route("robocall.detail.assign-upload", props.id), {
        forceFormData: true,
        onSuccess: () => {
            if (paginate?.data?.value?.length > 1) {
                paginate.fetchData();
            } else {
                window.location.reload();
            }
        },
        onFinish: () => {
            form.file = null;
            form.id = "";
            if (fileInput.value) {
                fileInput.value.value = "";
            }
        },
    });
};

const showDelete = (row: any) => {
    form.id = row.id;
    clickId("show-delete");
};

const actionDelete = () => {
    if (!form.processing) {
        form.post(route("robocall.detail.delete-upload", props.id), {
            onError: () => {
                showPopupDelete.value = false;
                setTimeout(() => {
                    showPopupDelete.value = true;
                }, 100);
            },
            onSuccess: () => {
                if (paginate?.data?.value?.length > 1) {
                    paginate.fetchData();
                } else {
                    window.location.reload();
                }
                showPopupDelete.value = false;
                setTimeout(() => {
                    showPopupDelete.value = true;
                }, 100);
            },
        });
    }
};
</script>
