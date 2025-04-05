<template>
    <ul
        x-show="dropdownOpen"
        x-on:click.away="dropdownOpen=false"
        v-bind:class="$attrs.top || 'top-5'"
        class="absolute bg-white border rounded-md text-left w-fit right-0 text-[11px] z-10 dropdown-button-main"
    >
        <slot />
    </ul>
</template>
