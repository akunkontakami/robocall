<template>
    <div
        x-show="confirmation"
        class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen"
        x-cloak
    >
        <div
            x-show="confirmation"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-40"
            x-transition:leave="ease-in duration-300"
            x-transition:leave-start="opacity-40"
            x-transition:leave-end="opacity-0"
            x-on:click="confirmation = false"
            class="absolute inset-0 w-full h-full bg-black opacity-40"
        ></div>
        <div
            x-show="confirmation"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full bg-white sm:max-w-sm sm:rounded-2xl overflow-hidden"
        >
            <div
                class="flex items-center justify-end py-4 font-krub font-semibold text-[16px] px-6 border border-b"
            >
                <IconClosePopup
                    class="cursor-pointer"
                    x-on:click="confirmation = false"
                    id="btn-close-confirmation"
                />
            </div>
            <div class="flex flex-col items-center">
                <p
                    class="text-dark font-krub font-medium px-6 my-8 text-base text-center"
                >
                    {{ confirmation }}
                </p>
                <div
                    class="flex justify-end gap-4 py-4 px-6 w-full bg-[#F5F4F7]"
                >
                    <OutlineGrey
                        x-on:click="confirmation = false"
                        class="font-krub font-medium text-[12px] py-2 w-[100px]"
                        id="btn-close-confirmation"
                    >
                        Cancel
                    </OutlineGrey>
                    <ButtonYellow
                        type="button"
                        @click="confirmAction"
                        :loading="progres"
                        :disabled="progres"
                        class="w-[100px] border-primary"
                    >
                        Submit
                    </ButtonYellow>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import ButtonYellow from "../Button/ButtonYellow.vue";
import OutlineGrey from "../Button/ButtonOutlineGrey.vue";
import { ref } from "vue";
import IconClosePopup from "../Icon/Etc/IconClosePopup.vue";

const emit = defineEmits(["action"]);
defineProps<{
    confirmation: string;
    type?: string;
}>();

const progres = ref(false);

const confirmAction = () => {
    progres.value = true;
    emit("action", () => {
        progres.value = false;
        (
            document.querySelector(
                ".delete-confirmation #btn-close-confirmation"
            ) as HTMLElement
        )?.click();
    });
};
</script>
