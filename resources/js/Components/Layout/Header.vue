<template>
    <header
        class="bg-white w-full border-b p-4 py-2 z-20 sticky flex flex-col top-0"
        v-if="user"
        x-data="{confirmation:false}"
    >
        <div class="flex justify-between items-center">
            <div>
                <AppLogoSm class="md:hidden w-[30px] h-[30px]" />
                <Link
                    :href="headerBackUrl"
                    class="font-krub-bold flex items-center gap-2 text-[15px] text-dark w-fit py-2"
                    v-if="headerBackUrl"
                >
                    <i
                        class="isax icon-arrow-left text-[20px]"
                        v-if="headerBackUrl"
                    ></i>
                    {{ header }}
                </Link>
                <h1
                    class="font-krub-bold flex items-center gap-2 text-[15px] text-dark w-fit py-2"
                    v-else
                >
                    {{ header }}
                </h1>
                <ul
                    class="hidden mb-[-7px] mt-2 w-fit gap-4 text-[13px] font-krub-semibold flex-wrap md:flex"
                    v-if="$slots.tab"
                >
                    <slot name="tab"></slot>
                </ul>
            </div>
            <ul class="flex gap-2 items-center">
                <li x-data="{ dropdownProfile: false }">
                    <div
                        class="flex text-end gap-2 items-center"
                    >
                        <div class="max-w-[350px]">
                            <b
                                class="text-[12px] line-clamp-1"
                                id="header-user-name"
                            >
                                {{ user.user_company.name }}
                            </b>
                            <p class="text-[9px]">
                                <span v-if="user.company_name">
                                    {{user.company_name}} -
                                </span>
                                <span class="text-yellow">
                                    {{ roleUser[user.role as string] }}
                                </span>
                            </p>
                        </div>
                        <img
                            :src="user.user_company.profile"
                            :alt="user.user_company.name"
                            id="header-user-profile"
                            class="w-[30px] h-[30px] rounded-full object-cover border"
                        />
                        <!-- <i
                            class="isax icon-arrow-down-1 text-[12px] ms-[-4px]"
                        ></i> -->
                    </div>
                    <!-- <div
                        x-show="dropdownProfile"
                        x-clock
                        x-on:click.away="dropdownProfile=false"
                        x-anchor.bottom-end="$refs.dropdownProfile"
                        class="absolute z-50 mt-3 w-fit whitespace-nowrap"
                    >
                        <div
                            class="py-1 bg-white border rounded-md shadow-sm border-neutral-200/70 w-fit min-w-[130px] text-neutral-700"
                        >
                            <ul
                                class="font-krub-semibold text-[13px] px-1 min-w-[100px]"
                            >
                                <li>
                                    <Link
                                        href=""
                                        class="text-dark text-[12px] flex gap-2 items-center px-3 py-[7px] rounded-md hover:bg-[#E6E9EF] relative"
                                    >
                                        <i
                                            class="isax-b icon-user text-[15px]"
                                        ></i>
                                        <span>Account</span>
                                    </Link>
                                </li>
                                <li>
                                    <button
                                        type="button"
                                        x-on:click="confirmation=true"
                                        class="text-dark text-[12px] w-full flex gap-2 items-center px-3 py-[7px] rounded-md hover:bg-[#E6E9EF]"
                                    >
                                        <i
                                            class="isax-b icon-logout rotate-180 text-[15px]"
                                        ></i>
                                        <span>Logout</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div> -->
                </li>
            </ul>
        </div>
        <ul
            class="md:hidden flex mb-[-7px] w-fit gap-4 text-[13px] font-krub-semibold flex-wrap mt-2"
            v-if="$slots.tab"
        >
            <slot name="tab"></slot>
        </ul>

        <Confirmation
            confirmation="Are you sure you want to logout?"
            @action="logout"
        />
    </header>
</template>

<script setup lang="ts">
import Confirmation from "@/Components/Popup/Confirmation.vue";
import AppLogoSm from "@/Components/Icon/Logo/AppLogoSm.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { leaveConnectionBroadcast } from "@/socket";

defineProps(["headerBackUrl", "header"]);
const user = usePage().props.auth?.user;
const roleUser: any = {
    ba: "Business Account - Superadmin",
    admin: "Business Account - Admin",
    spv: "Supervisor",
    am: "AM",
    spv_escalation: "Escalation SPV",
};
const logout = () => {
    leaveConnectionBroadcast();
    router.get(route("auth.logout"));
};
</script>
