<template>
     <label :for="id" class="text-[12px] text-dark font-krub-medium mb-1 block  pre-text-content" v-if="label">
         {{ label }}
         <span class="text-red" v-if="$attrs.required">*</span>
     </label>
     <div
         class="relative mb-2"
         x-data="{ show: false }"
         v-bind:class="{ 'has-error': error }"
     >
         <i
             v-bind:class="icon"
             class="absolute top-[14px] left-4 text-[14px] text-[#A4A4A4]"
             v-if="icon"
         ></i>
         <input
             v-bind:type="show ? 'text' : 'password'"
             v-bind="$attrs"
             v-bind:class="icon ? 'ps-10' : ''"
             @input="
                 $emit(
                     'update:modelValue',
                     ($event.target as HTMLInputElement).value
                 )
             "
             class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] outline-none shadow-none py-2 w-full mb-2 bg-white appearance-none pe-10"
         />
         <i
             class="isax absolute cursor-pointer top-[14px] right-4 text-[14px]"
             v-bind:class="show ? 'icon-eye-slash' : 'icon-eye'"
             @click="show = !show"
         ></i>
 
         <small
             v-if="error"
             class="mt-[-7px] error-text mb-4 block text-[11px]"
             >{{ error }}</small
         >
         <small class="block mt-[-7px] text-[10px] mb-2 text-[#A3A3A3]" v-if="help">{{
             help
         }}</small>
     </div>
 </template>
 
 <script lang="ts" setup>
 import { ref } from "vue";
 defineProps<{
     label?: string;
     icon?: string;
     help?: string;
     error?: string;
     id?: string;
 }>();
 
 const show = ref(false);
 </script>
 