<template>
     <div>
         <label
             class="text-[12px] text-dark font-krub-medium mb-1 block"
             v-if="label"
         >
             {{ label }}
             <span class="text-red" v-if="$attrs.required">*</span>
         </label>
 
         <div
             class="relative mb-2 time-picker"
             x-data="{input: $el.getAttribute('data-value')}"
             :data-value="value || ''"
             v-bind:class="{ 'has-error': error }"
         >
             <input
                 type="text"
                 x-model="input"
                 v-bind="$attrs"
                 v-bind:class="name"
                 class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] outline-none py-2 w-full mb-2"
             />
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
 import { ref, onMounted,watch } from "vue";
 const props = defineProps<{
     name: string;
     default?: string;
     label?: string;
     error?: string;
     value?: any;
 }>();
 const emit = defineEmits(["update:modelValue"]);
 
 const datePicker  : any= ref(null);
 onMounted(() => {
     datePicker.value = flatpickr(`.${props.name}`, {
         minuteIncrement : 1,
         enableTime: true,
         noCalendar: true,
         dateFormat: "H:i",
         time_24hr: true,
         defaultDate: props.value || props.default,
         onChange: (selectedDates, dateStr) => {
             emit("update:modelValue", dateStr);
         },
     });
     if (!props.value) {
         emit("update:modelValue", props.default);
     }
 });
 watch(
     () => props.default,
     (min, value) => {
         if(props.default){
             datePicker.value.set('defaultDate', props.default);
         }
     }
 );
 
 watch(
     () => props.value,
     (min, value) => {
         if(props.value){
             datePicker.value.setDate(props.value,"H:i");
         }else{
             datePicker.value.setDate('',"H:i");
         }
     }
 );
 </script>
 