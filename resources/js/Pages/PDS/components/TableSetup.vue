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
                <span v-if="row.spv" class="text-[#424EA1] font-krub-semibold underline cursor-pointer" @click="showSpv(row)">View SPV</span>
                <span v-else>-</span>
            </Td>
            <Td>
                {{ row.campaign }}
            </Td>
            <Td>
                <span class="text-red text-[13px] font-krub-semibold" v-if="!row.is_running">Stop</span>
                <span class="text-green text-[13px] font-krub-semibold" v-if="row.is_running">Running</span>
            </Td>
            <Td>
                {{ row.total_agent }}
            </Td>
            <Td>
                {{ row.total_data }}
            </Td>
            <Td>
                <a
                    :x-on:click="`openRowIndex = '${i}'`"
                    class="rotate-90 cursor-pointer inline-flex" x-ref="anchor"
                >
                    <i class="isax icon-more"></i>
                </a>

                <div
                    :x-show="`openRowIndex === '${i}'`"
                    x-anchor="$refs.anchor"
                    x-on:click.away="openRowIndex = ''"
                    class="absolute bg-white shadow-lg border rounded-md mt-1 min-w-[120px] z-50 p-2"
                >
                    <ul class="text-sm text-dark flex flex-col gap-2" v-if="!row.is_running">
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="manage(row)">
                            <i class="text-base isax icon-edit"></i>
                            <span class="text-xs">Manage</span>
                        </li>
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showDelete(row)">
                            <i class="text-base isax icon-trash"></i>
                            <span class="text-xs">Delete</span>
                        </li>
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showRelease(row)" v-if="row.total_data > 0">
                            <i class="text-base isax icon-trash"></i>
                            <span class="text-xs">Release Customers</span>
                        </li>
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showStart(row)">
                            <i class="text-base isax-b icon-play-circle text-green"></i>
                            <span class="text-xs">Start PDS</span>
                        </li>
                    </ul>

                    <ul class="text-sm text-dark flex flex-col gap-2" v-if="row.is_running">
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showStop(row)">
                            <IconPowerOff />
                            <span class="text-xs">Stop</span>
                        </li>
                    </ul>
                </div>
            </Td>
        </tr>
    </Table>

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
                class="bg-[#F4F6FA] p-3 rounded-[4px] w-full text-[13px] text-[#181C32] font-opensauceone-medium flex justify-between items-center cursor-pointer"
                @click="openSub('sub-'+spv?.id)"
            >
                {{ spv?.company_user?.name }} <i class="isax icon-arrow-down-1 text-base" v-if="agents.length"></i>
            </div>
            <div
                class="ms-5 hidden"
                :id="'sub-'+spv?.id"
            >
                <div
                    v-for="agent in agents"
                    class="text-[13px] text-[#181C32] font-opensauceone-medium pt-3 flex items-center relative timeline"
                >
                    <span class="timeline-line-v"></span>
                    <span class="h-[1px] w-3 bg-[#181C32]"></span>
                    <span class="h-[5px] w-[5px] rounded-full bg-[#181C32]"></span>
                    <span class="ms-2">{{ agent?.company_user?.name }}</span>
                </div>
            </div>
        </div>
    </Popup>

    <a hidden x-on:click="popup=true" id="show-popup-spv"></a>
</template>
<script setup lang="ts">
import IconPowerOff from "@/Components/Icon/Etc/IconPowerOff.vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";
import Popup from "@/Components/Popup/Index.vue";
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { clickId, showAlert } from "@/Plugins/Function/global-function";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { router, useForm } from "@inertiajs/vue3";
import { ref, onBeforeUnmount, onMounted } from "vue";

const emits = defineEmits(['showStartPds'])

const showPopupRelease = ref(true)
const showPopupDelete = ref(true)
const showPopupStop = ref(true)

const columns = ref([
    "Date",
    "PDS Name",
    "SPV",
    "Campaign PDS",
    "Status PDS",
    "Total Agent",
    "Total Data",
    "Action"
]);

const spv = ref<any>(null)
const agents = ref<any>([])

const form = useForm({
    id: ''
})

const paginate = usePaginate({
    route: route('pds.setup.datatable'),
});

const showStart = (row: any) => {
    if (row.total_data == 0) {
        showAlert("Please upload customer data before starting")
    } else if (row.total_agent == 0) {
        showAlert("Please assign agent before starting")
    } else {
        emits('showStartPds', row)
    }
}


const actionDelete = () => {
    if (!form.processing) {
        form.post(route('pds.setup.delete'), {
            onError: () => {
                showPopupDelete.value = false

                setTimeout(() => {
                    showPopupDelete.value = true
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData()
                showPopupDelete.value = false

                setTimeout(() => {
                    showPopupDelete.value = true
                }, 100);
            }
        })
    }
}

const actionRelease = () => {
    if (!form.processing) {
        form.post(route('pds.setup.release'), {
            onError: () => {
                showPopupRelease.value = false

                setTimeout(() => {
                    showPopupRelease.value = true
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData()
                showPopupRelease.value = false

                setTimeout(() => {
                    showPopupRelease.value = true
                }, 100);
            }
        })
    }
}

const showDelete = (row: any) => {
    form.id = row.id

    clickId("show-delete")
}

const showRelease = (row: any) => {
    form.id = row.id

    clickId("show-release")
}

const actionStop = () => {
    if (!form.processing) {
        form.post(route('pds.setup.stop'), {
            onError: () => {
                showPopupStop.value = false

                setTimeout(() => {
                    showPopupStop.value = true
                }, 100);
            },
            onSuccess: () => {
                paginate.fetchData()
                showPopupStop.value = false

                setTimeout(() => {
                    showPopupStop.value = true
                }, 100);
            }
        })
    }
}

const showStop = (row: any) => {
    form.id = row.id

    clickId("show-stop")
}

const manage = (row: any) => {
    router.visit(route('pds.detail', row.id))
}

const openSub = (id: string) => {
    document.getElementById(id)?.classList.toggle('hidden')
}

const showSpv = (item: any) => {
    agents.value = item.agents
    spv.value = item.spv

    clickId("show-popup-spv")
}
</script>
