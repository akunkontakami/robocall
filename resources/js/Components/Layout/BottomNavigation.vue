<template>
    <nav class="md:hidden bg-white w-full border-t px-3 py-2 overflow-auto bottom-navigation fixed bottom-0">
        <ul class="text-[12px] font-krub-medium menu-list flex  gap-10 items-center justify-center">
            <li
                x-data="{ id: $id('accordion') }"
                v-for="menu in menus"
                class="nav-item mb-1"
                :class="{
                    active: route().current(menu.active),
                }"
            >
                <Link
                    :href="menu.route || 'javascript:;'"
                    class="nav-link nav-parent w-full flex flex-col items-center gap-2"
                    :disabled="!menu.enable"
                >
                    <component :is="menu.icon" ></component>
                    <span class="menu-name text-[10px] whitespace-nowrap">{{ menu.name }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
defineProps(["menus"]);
</script>
