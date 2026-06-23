/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
    "./plugins/**/*.vue",
    "../plugins/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        accent: {
          purple: '#7c3aed',
          blue: '#2563eb',
        },
      },
    },
  },
  plugins: [],
}
