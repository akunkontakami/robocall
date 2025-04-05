<template>
     <div
         x-show="popupDescription"
         class="fixed top-0 left-0 z-[99] flex items-center justify-center h-full w-full"
         x-cloak
     >
         <div
             x-show="popupDescription"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-on:click="popupDescription = false"
             @click="$emit('close')"
             class="absolute inset-0 w-full max-h-full bg-black bg-opacity-40"
         ></div>
         <div
             x-show="popupDescription"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-h-[95%] pt-4 bg-white sm:rounded-lg md:max-w-lg"
         >
             <div
                 class="flex items-center justify-between pb-2 font-krub-semibold text-[16px] px-6"
             >
                 <h3 class="text-dark">{{ title }}</h3>
                 <IconClosePopup
                     class="cursor-pointer"
                     x-on:click="popupDescription = false"
                     @click="$emit('close')"
                 />
             </div>
             <div class="overflow-auto max-h-[80vh] text-[13px] mt-2 sm:rounded-lg px-6 pb-6">
                 <p class="wrap-text-line">{{ description }}</p>
             </div>
         </div>
     </div>
 </template>
 
 <script lang="ts" setup>
 import IconClosePopup from '../Icon/Etc/IconClosePopup.vue';
 defineProps(["title", "description"]);
 </script>
 