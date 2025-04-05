<template>
    <div>
        <slot name="header" />
        <div class="overflow-auto border rounded-xl table-main main-datatable">
            <table class="border-collapse table-auto w-full text-sm">
                <thead>
                    <tr class="bg-[#F4F6FA]">
                        <Th v-for="col in columns">{{ col }}</Th>
                    </tr>
                </thead>
                <slot name="tbody" />
                <tbody class="bg-white">
                    <slot v-if="!paginate?.loading.value" />
                    <tr
                        v-if="
                            !paginate?.loading.value &&
                            !paginate?.data?.value?.length &&
                            paginate
                        "
                    >
                        <Td
                            :colspan="columns?.length + 1"
                            class="text-center py-10"
                        >
                            <div
                                class="flex flex-col justify-center items-center gap-5"
                            >
                                <EmptyData class="w-[150px] h-[150px]" />
                                <span
                                    class="text-dark font-krub-semibold text-[13px] mb-4"
                                >
                                    No Data
                                </span>
                            </div>
                        </Td>
                    </tr>
                    <tr v-for="n in 5" v-if="paginate?.loading.value">
                        <Td class="pe-0" v-if="checked !== undefined">
                            <span
                                class="min-w-[20px] bg-[#dddddda8] h-[10px] inline-block rounded-md"
                            ></span>
                        </Td>
                        <Td v-for="col in columns">
                            <span
                                class="w-full min-w-[20px] bg-[#dddddda8] h-[10px] inline-block rounded-md"
                            ></span>
                        </Td>
                    </tr>
                </tbody>
            </table>
            <Pagination
                class="rounded-xl rounded-tl-none rounded-tr-none sticky left-0"
                :information="paginate.information.value"
                @next="paginate.next()"
                @prev="paginate.prev()"
                @setLimit="paginate.changeLimit"
                v-if="paginate"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import Th from "./Th.vue";
import Td from "./Td.vue";
import Pagination from "./Pagination.vue";
import EmptyData from "../Icon/Etc/EmptyState.vue";

defineProps<{
    columns: Array<any>;
    checked?: any;
    paginate?: any;
}>();

const checkAll = (event: any) => {
    document.querySelectorAll(".checkAll-table").forEach((element: any) => {
        const isChecked = event.target.checked;
        if (isChecked !== element.checked) {
            element.click();
        }
        element.checked = isChecked;
    });
};
</script>
