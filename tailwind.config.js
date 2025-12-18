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
                'gray-light': '#f5f5f5',
                'gray-border': '#e0e0e0',
            },
            boxShadow: {
                'card': '0 2px 4px rgba(0,0,0,0.1)',
                'card-hover': '0 4px 8px rgba(0,0,0,0.15)',
            },
        },
    },
    plugins: [],
}
