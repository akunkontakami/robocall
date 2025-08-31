<template>
    <Table :columns="columns" :paginate="paginate">
        <tr v-for="(row, i) in paginate.data.value">
            <Td>
                [date]
            </Td>
            <Td>
                [PDS Name]
            </Td>
            <Td>
                [SPV]
            </Td>
            <Td>
                [Campaign PDS]
            </Td>
            <Td>
                <span class="text-red text-[13px] font-krub-semibold" v-if="i % 2 == 0">Stop</span>
                <span class="text-green text-[13px] font-krub-semibold" v-if="i % 2 != 0">Running</span>
            </Td>
            <Td>
                10
            </Td>
            <Td>
                10
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
                    <ul class="text-sm text-dark flex flex-col gap-2" v-if="i % 2 == 0">
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="manage(row)">
                            <i class="text-base isax icon-edit"></i>
                            <span class="text-xs">Manage</span>
                        </li>
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showDelete(row)">
                            <i class="text-base isax icon-trash"></i>
                            <span class="text-xs">Delete</span>
                        </li>
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showStart(row)">
                            <i class="text-base isax-b icon-play-circle text-green"></i>
                            <span class="text-xs">Start PDS</span>
                        </li>
                    </ul>

                    <ul class="text-sm text-dark flex flex-col gap-2" v-if="i % 2 != 0">
                        <li class="transition-all rounded-sm py-1 px-2 hover:bg-gray-100 cursor-pointer flex items-center gap-2" @click="showStop(row)">
                            <IconPowerOff />
                            <span class="text-xs">Stop</span>
                        </li>
                    </ul>
                </div>
            </Td>
        </tr>
    </Table>

    <div x-data="{confirmation:false}" v-if="showPopupStart">
        <a hidden id="show-start" x-on:click="confirmation=true"></a>
        <ConfirmationSubmit
            confirmation="Are you sure you want to start PDS?"
            @action="actionStart"
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
</template>
<script setup lang="ts">
import IconPowerOff from "@/Components/Icon/Etc/IconPowerOff.vue";
import ConfirmationSubmit from "@/Components/Popup/ConfirmationSubmit.vue";
import Table from "@/Components/Table/Table.vue";
import Td from "@/Components/Table/Td.vue";
import { clickId } from "@/Plugins/Function/global-function";
import { usePaginate } from "@/Plugins/Hooks/usePaginate";
import { router, useForm } from "@inertiajs/vue3";
import { ref, onBeforeUnmount, onMounted } from "vue";

const showPopupStart = ref(true)
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

const form = useForm({
    id: ''
})

const paginate = usePaginate({
    route: route('dummy'),
});

const actionStart = () => {
    paginate.fetchData()
    showPopupStart.value = false

    setTimeout(() => {
        showPopupStart.value = true
    }, 100);
}

const showStart = (row: any) => {
    form.id = row.id

    clickId("show-start")
}


const actionDelete = () => {
    paginate.fetchData()
    showPopupDelete.value = false

    setTimeout(() => {
        showPopupDelete.value = true
    }, 100);
}

const showDelete = (row: any) => {
    form.id = row.id

    clickId("show-delete")
}

const actionStop = () => {
    paginate.fetchData()
    showPopupStop.value = false

    setTimeout(() => {
        showPopupStop.value = true
    }, 100);
}

const showStop = (row: any) => {
    form.id = row.id

    clickId("show-stop")
}

const manage = (row: any) => {
    router.visit(route('pds.detail', 1))
}
</script>
