<template>
    <aside  x-data="{confirmation:false}">
        <section
            class="w-sidebar border-r h-full bg-white fixed md:flex hidden flex-col main-sidebar z-[2]"
        >
            <nav id="head nav-sidebar-application" class="py-2 mb-2">
                <Link href="" class="py-3 pb-1 flex justify-center">
                    <YelowLogo x-show="!sidebarCollapse" />
                    <YelowLogoMini class="mb-3 mt-2" x-show="sidebarCollapse" />
                </Link>
            </nav>

            <nav class="px-3 flex-1 overflow-auto main-nav">
                <ul class="text-[12px] font-krub-medium menu-list">
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
                            class="nav-link nav-parent w-full flex items-center gap-2 py-3 pb-0"
                            :disabled="!menu.enable"
                        >
                            <component :is="menu.icon"></component>
                            <span class="menu-name">{{ menu.name }}</span>
                        </Link>
                    </li>
                </ul>
            </nav>
            <button
                type="button"
                class="border-2 border-yellow rounded-full w-[30px] h-[30px] flex items-center justify-center absolute bg-white right-[-15px] bottom-[70px]"
                x-on:click="sidebarCollapse=!sidebarCollapse"
            >
                <i
                    class="isax icon-arrow-right-3 text-[18px] text-yellow transition-all"
                    x-bind:class="sidebarCollapse?'':'rotate-180'"
                ></i>
            </button>
            <div>
                <a
                    href="javascript:;"
                    class="font-krub-medium text-[13px] text-red p-2 bg-red-400 items-center justify-center gap-3 text-center flex"
                    x-on:click="confirmation=true"
                >
                    <IconMenuLogout />
                    <span x-show="!sidebarCollapse">Log out</span>
                </a>
            </div>
        </section>
        <Confirmation
            confirmation="Are you sure you want to logout?"
            @action="logout"
        />
    </aside>
</template>

<script setup lang="ts">
import Confirmation from "@/Components/Popup/Confirmation.vue";
import YelowLogo from "../Icon/Logo/YelowLogo.vue";
import YelowLogoMini from "../Icon/Logo/YelowLogoMini.vue";
import IconMenuLogout from "../Icon/Menu/IconMenuLogout.vue";
import { Link, router } from "@inertiajs/vue3";
import { leaveConnectionBroadcast } from "@/socket";

defineProps(["menus"]);

const logout = () => {
    leaveConnectionBroadcast();
    router.get(route("auth.logout"));
};
</script>
