<template>
    <div class="w-full fixed md:bottom-5 bottom-[75px] left-0 z-[1000] flex justify-center">
        <div
            class="alert px-5 py-2 transition-all duration-75 rounded-md items-center gap-2 w-fit hidden"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            id="alert-flash-message"
        >
            <i class="isax icon-info-circle text-white"></i>
            <span class="text-[11px] text-white message"> </span>
        </div>
    </div>
    <span class="bg-online"></span>
    <span class="bg-offline"></span>
</template>
