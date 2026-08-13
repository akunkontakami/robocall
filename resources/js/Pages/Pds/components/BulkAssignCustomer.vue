<template>
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="close"></div>
        <div class="relative bg-white rounded-lg w-full max-w-3xl max-h-[95vh] overflow-y-auto">
            <div class="flex justify-between items-center px-6 pt-4">
                <h3 class="text-dark font-krub-semibold text-[16px]">Assign Customer</h3>
                <button type="button" class="text-xl text-gray-500" @click="close">&times;</button>
            </div>
            <AssignCustomerForm
                :id="ids[0]"
                :ids="ids"
                :data="data"
                :offices="[]"
                :show-cancel="true"
                :show-details="false"
                @cancel="close"
                @success="emit('success')"
            />
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import AssignCustomerForm from "@/Pages/PdsDetail/components/AssignCustomerForm.vue";
const props = defineProps<{ ids: Array<string | number>; rows: any[] }>();
const emit = defineEmits(["close", "success"]);
const ids = props.ids;
const data = computed(() => ({
    pds_name: props.rows[0]?.name,
    campaign: { name: props.rows[0]?.campaign },
    spv: props.rows[0]?.spv,
    customers: [],
}));
const close = () => emit("close");
</script>
