<template>
     <div
         class="flex items-center justify-between w-full px-3 py-2 text-black bg-[#F9FBFC]"
     >
         <div class="flex justify-center items-center gap-2">
             <p class="pl-2 text-[11px] font-krub-medium">Show rows per page</p>
             <select
                 class="border rounded-md outline-none p-1 text-[11px] bg-white font-krub-medium w-[45px]"
                 @change="
                     $emit('setLimit', ($event.target as HTMLInputElement).value)
                 "
             >
                 <option :selected="props.information.per_page===10" value="10">10</option>
                 <option :selected="props.information.per_page===20" value="20">20</option>
                 <option :selected="props.information.per_page===50" value="50">50</option>
                 <option :selected="props.information.per_page===100" value="100">100</option>
                 <option :selected="props.information.per_page===150" value="150">150</option>
             </select>
         </div>
 
         <div
             class="flex justify-center items-center gap-1 pr-2 text-[11px] font-krub-medium"
         >
             <p class="mr-3">
                 {{ information?.from || 0 }}-{{ information?.to || 0}} of
                 {{ information?.total }}
             </p>
             <a
                 href="javascript:;"
                 class="font-bold text-[14px] isax icon-arrow-left-2"
                 @click="prev"
                 :class="{
                     'text-[#ddd] cursor-no-drop':
                         props.information.current_page <= 1,
                 }"
             ></a>
             <a
                 href="javascript:;"
                 class="font-bold text-[14px] isax icon-arrow-right-3"
                 @click="next"
                 :class="{
                     'text-[#ddd] cursor-no-drop':
                         props.information.current_page ===
                         props.information.last_page,
                 }"
             ></a>
         </div>
     </div>
 </template>
 <script setup lang="ts">
 const emit = defineEmits(["next", "prev", "setLimit"]);
 const props = defineProps<{
     information?: any;
 }>();
 
 const next = () => {
     if (props.information.current_page < props.information.last_page) {
         emit("next");
     }
 };
 
 const prev = () => {
     if (props.information.current_page > 1) {
         emit("prev");
     }
 };
 </script>
 