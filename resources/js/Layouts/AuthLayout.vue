<template>
    <main
        class="bg-auth h-full flex flex-col items-center justify-center md:py-6 md:px-6 py-2 px-2 min-h-screen"
    >
        <Head :title="title" />
        <div class="md:w-[400px] w-[70%] mb-3">
            <AppLogoLg class="w-full" />
        </div>
        <slot />
        <div class="fixed bottom-0 left-0">
            <AuthLayerLeft />
        </div>
        <div class="fixed bottom-0 right-0 md:block hidden">
            <AuthLayerRight />
        </div>
        <p class="text-[11px] text-dark fixed bottom-3">
            © 2024, Kontakami. All Rights Reserved
        </p>
    </main>
</template>
<script setup lang="ts">
import AuthLayerLeft from "@/Components/Icon/Etc/AuthLayerLeft.vue";
import AuthLayerRight from "@/Components/Icon/Etc/AuthLayerRight.vue";
import AppLogoLg from "@/Components/Icon/Logo/AppLogoLg.vue";
import { Head } from "@inertiajs/vue3";

defineProps(["title"]);
</script>
