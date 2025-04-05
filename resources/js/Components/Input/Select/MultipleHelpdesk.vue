<template>
     <label
         :for="id"
         class="text-[12px] text-dark font-krub-medium mb-1 block pre-text-content"
         v-if="label"
     >
         {{ label }}
         <span class="text-red" v-if="$attrs.required">*</span>
     </label>
     <div
         class="relative mb-2"
         x-data="{
             input: $el.getAttribute('data-value'),
             helpdeskDropdownOpen:false,
             position : {
                 x:0,
                 y:0,
                 width : '100px'
             },
             openDropdown(react){
                  this.position = {
                      x: `${react.x}px`,
                      y: `${react.y+47}px`,
                      width: `${react.width}px`,
                  }
                  this.helpdeskDropdownOpen=!this.helpdeskDropdownOpen
             }
         }"
         :data-value="$attrs.value || ''"
         v-bind:class="{ 'has-error': error }"
     >
         <div
             class="border rounded-lg placeholder:text-[#615e5e] px-4 text-[12px] min-h-[42px] flex gap-2 flex-wrap outline-none py-2 w-full mb-2 items-center"
             x-ref="helpdeskSelect"
         >
             <span
                 class="border px-2 items-center flex rounded-md bg-[#ddd] text-[10px] h-[20px]"
                 x-on:click="helpdeskDropdownOpen=false"
                 v-for="item in (selected as any)"
             >
                 {{ item.name }}
                 <i
                     class="isax icon-close-circle ms-3 cursor-pointer"
                     @click="removeItem(item.id)"
                 ></i>
             </span>
             <div
                 class="mt-[3px] flex-1 h-full min-w-[100px] cursor-pointer whitespace-nowrap overflow-hidden"
                 x-on:click="openDropdown($el.parentElement.getBoundingClientRect())"
                 v-bind:class="{ 'text-[#ddd]': selected.length }"
             >
                 {{ placeholder || "Choose Help Desk Category" }}
             </div>
         </div>
         <i
             class="isax icon-arrow-down-1 float-right mt-[-37px] me-4 text-[#B4B6B8] text-[13px]"
         ></i>
         <div class="mb-4" v-if="$attrs.maxlength">
             <p class="text-[11px] text-dark float-right">
                 <span x-text="input.length"></span>/{{ $attrs.maxlength }}
             </p>
         </div>
         <div
             class="fixed w-full z-[11]"
             x-transition:enter="transition ease-out duration-50"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100"
             x-show="helpdeskDropdownOpen"
             x-on:click.away="helpdeskDropdownOpen = false"
             v-bind:class="{ 'mt-2': help }"
             x-anchor.bottom-start="$refs.helpdeskSelect"
         >
             <div
                 class="bg-white border rounded-lg w-full max-h-60 p-2 flex flex-col"
             >
                 <div>
                     <input
                         type="text"
                         v-model="search"
                         class="border w-full text-[11px] mb-2 font-krub-medium rounded-lg py-1"
                         :placeholder="searchPlaceholder || 'Search Helpdesk'"
                     />
                 </div>
                 <ul class="flex-1 overflow-auto">
                     <li v-for="item in itemList" :key="item.id">
                         <a
                             href="javascript:;"
                             class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] text-[11px] text-[#7B7B7B]"
                             x-on:click="helpdeskDropdownOpen=false"
                             v-if="!item.sub.length"
                             @click="addItem(item)"
                         >
                             {{ item.name }}
                         </a>
                         <a
                             href="javascript:;"
                             class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] cursor-not-allowed text-[11px] tex-dark"
                             v-bind:class="{ '': item.sub.length }"
                             v-else
                         >
                             {{ item.name }}
                         </a>
                         <ul class="ps-4">
                             <li v-for="sub in item.sub">
                                 <a
                                     href="javascript:;"
                                     x-on:click="helpdeskDropdownOpen=false"
                                     class="flex gap-3 p-[5px] px-2 rounded-md hover:bg-[#dddddd52] text-[11px] text-[#7B7B7B]"
                                     @click="addItem(sub)"
                                 >
                                     {{ sub.name }}
                                 </a>
                             </li>
                         </ul>
                     </li>
                 </ul>
             </div>
             <br /><br />
         </div>
         <small
             v-if="error"
             class="mt-[-7px] error-text mb-4 block text-[11px] "
             >{{ error }}</small
         >
         <small
             class="block mt-[-7px] text-[10px] mb-4 text-[#A3A3A3]"
             v-if="help"
             >{{ help }}</small
         >
     </div>
 </template>
 
 <script lang="ts" setup>
 import { ref, watch, onMounted } from "vue";
 
 const emit = defineEmits(["update:modelValue"]);
 const props = defineProps<{
     label?: string;
     help?: string;
     error?: string;
     id?: string;
     placeholder?: string;
     helpdesk: Array<any>;
     selected?: Array<any>;
     searchPlaceholder?: string;
 }>();
 
 const search = ref("");
 const itemList = ref(props.helpdesk);
 const selected = ref([]);
 
 const addItem = (row: any) => {
     const selectedIds = selected.value.map((val: any) => val.id);
     if (!selectedIds.includes(row.id)) {
         (selected.value as any).push({
             id: row.id,
             name: row.name,
         });
         emit(
             "update:modelValue",
             selected.value.map((row: any) => row.id)
         );
     }
 };
 
 const removeItem = (id: string) => {
     selected.value = selected.value.filter((row: any) => row.id !== id);
     emit(
         "update:modelValue",
         selected.value.map((row: any) => row.id)
     );
 };
 
 const setSelectedItem = () => {
     var selectedItem = props.selected;
     if (selectedItem) {
         const itemSelected: any = [];
         const selectedId = JSON.parse(JSON.stringify(selectedItem));
         props.helpdesk.forEach((row: any) => {
             if (selectedId.includes(row.id)) {
                 itemSelected.push({
                     id: row.id,
                     name: row.name,
                 });
             }
             row.sub.forEach((key: any) => {
                 if (selectedId.includes(key.id)) {
                     itemSelected.push({
                         id: key.id,
                         name: key.name,
                     });
                 }
             });
         });
         selected.value = itemSelected;
     }
 };
 onMounted(() => {
     setSelectedItem();
 });
 watch(search, (newValue, oldValue) => {
     const searchValue = newValue.toLowerCase();
     itemList.value = props.helpdesk?.filter(function (row) {
         return row.name.toLowerCase().includes(searchValue) || row.sub.some(function(sub:any){
             return sub.name.toLowerCase().includes(searchValue)
         });
     });
 });
 
 watch(
     () => props.selected,
     (val, value) => {
         setSelectedItem();
     }
 );
 </script>
 