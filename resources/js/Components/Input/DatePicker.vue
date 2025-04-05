<template>
     <div>
         <label
             class="text-[12px] text-dark font-krub-medium mb-1 block pre-text-content"
             v-if="label"
         >
             {{ label }}
             <span class="text-red" v-if="$attrs.required">*</span>
         </label>
 
         <div
             class="relative mb-2"
             x-data="{input: $el.getAttribute('data-value')}"
             :data-value="$attrs.value || ''"
             v-bind:class="{ 'has-error': error }"
         >
             <input
                 type="text"
                 x-model="input"
                 v-bind="$attrs"
                 v-bind:class="name"
                 placeholder="yyyy-mm-dd"
                 class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] shadow-none outline-none py-2 w-full mb-2"
             />
             <i
                 class="isax icon-calendar-2 absolute right-2 top-3 pointer-events-none"
             ></i>
             <small
                 v-if="error"
                 class="mt-[-7px] error-text mb-4 block text-[11px]"
                 >{{ error }}</small
             >
         </div>
     </div>
 </template>
 <script setup lang="ts">
 import flatpickr from "flatpickr";
 import "flatpickr/dist/flatpickr.min.css";
 import { ref } from "vue";
 import { watch, onMounted } from "vue";
 const props = defineProps<{
     name: string;
     default?: string;
     label?: string;
     error?: string;
     min?: string;
     max?: string;
 }>();
 const emit = defineEmits(["update:modelValue"]);
 
 const datePicker  : any= ref(null);
 onMounted(() => {
     datePicker.value = flatpickr(`.${props.name}`, {
         dateFormat: "Y-m-d",
         time_24hr: true,
         minDate: props.min,
         maxDate: props.max,
         defaultDate: props.default,
         onChange: (selectedDates, dateStr) => {
             emit("update:modelValue", dateStr);
         },
     });
     if(props.default){
         emit("update:modelValue", props.default);
     }
 });
 
 watch(
     () => props.min,
     (min, value) => {
         datePicker.value.set('minDate', props.min);
     }
 );

 watch(
     () => props.max,
     (min, value) => {
         datePicker.value.set('maxDate', props.max);
     }
 );
 </script>
 