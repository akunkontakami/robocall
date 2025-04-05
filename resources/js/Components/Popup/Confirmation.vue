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
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="confirmation = false"
            class="absolute inset-0 w-full h-full bg-black bg-opacity-40"
        ></div>
        <div
            x-show="confirmation"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full pt-4 bg-white sm:max-w-sm sm:rounded-2xl overflow-hidden"
        >
            <div class="flex flex-col items-center">
                <IconWarningConfirmation class="w-[30%]"/>
                <p
                    class="text-dark font-krub-medium mb-1 px-6 mt-2 text-[14px] text-center"
                >
                    {{ confirmation }}
                </p>
                <div
                    class="flex justify-center gap-4 mt-3 mb-3 py-2 px-4 w-full"
                >
                    <OutlineGrey
                        x-on:click="confirmation = false"
                        class="font-krub-medium text-[12px] py-2 w-[100px] uppercase"
                        id="btn-close-confirmation"
                    >
                       NO
                    </OutlineGrey>
                    <ButtonYellow
                        type="button"
                        @click="confirmAction"
                        :loading="progres"
                        :disabled="progres"
                        class="w-[100px] uppercase border-yellow"
                    >
                        YES
                    </ButtonYellow>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import ButtonYellow from "../Button/ButtonYellow.vue";
import OutlineGrey from "../Button/ButtonOutlineGrey.vue";
import IconWarningConfirmation from "../Icon/Etc/IconWarningConfirmation.vue"
import { ref } from "vue";

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
