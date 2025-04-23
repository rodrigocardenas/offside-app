/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                'offside': {
                    'dark': '#002E2C',
                    'primary': '#00857B',
                    'secondary': '#00B5A5',
                    'light': '#E6FAF8',
                },
            },
        },
    },
    plugins: [],
}
