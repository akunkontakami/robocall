<template>
     <li>
         <button
             type="button"
             x-on:click="dropdownOpen=false"
             class="flex items-center w-full whitespace-nowrap px-3 gap-2 py-1 hover:bg-[#E9E9E9] cursor-pointer"
             v-if="!$attrs.href"
         >
             <slot />
         </button>
         <Link
             :href="$attrs.href.toString()"
             class="flex items-center w-full whitespace-nowrap px-3 gap-2 py-1 hover:bg-[#E9E9E9] cursor-pointer"
             v-if="$attrs.href"
         >
             <slot />
         </Link>
     </li>
 </template>
 <script setup lang="ts">
 import { Link } from "@inertiajs/vue3";
 </script>
 