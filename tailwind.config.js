import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            spacing: {
                sidebar: '250px',
            },
            colors: {
                body: '#F4F6FA',
                red: '#FF0000',
                kuning :'#F6A500',
                yellow: '#3943B7',
                dark: '#25213B',
                online : '#38A363',
                offline : '#FE4C4C',
                success : '#029E2D',
                blue : '#3AA0FF',
                "dark-blue": '#424EA1',
                green: '#00982B'
            },
            fontFamily: {
                "krub-bold": "Krub-Bold",
                "krub-light": "Krub-light",
                "krub-medium": "Krub-Medium",
                "krub-regular": "Krub-Regular",
                "krub-semibold": "Krub-SemiBold",
            },
        },
    },

    plugins: [forms],
};
