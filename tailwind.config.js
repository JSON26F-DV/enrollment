/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./src/**/*.php",
    "./components/**/*.php",
    "./templates/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        'pixs-mint': 'var(--pixs-mint)',
        'google-blue': 'var(--google-blue)',
        'google-blue-hover': 'var(--google-blue-hover)',
      },
      borderRadius: {
        '4xl': '32px',
        '5xl': '40px',
      },
      boxShadow: {
        'pixs-mint': '0 10px 25px -5px rgba(61, 217, 160, 0.2), 0 8px 10px -6px rgba(61, 217, 160, 0.2)',
      },
      fontFamily: {
        sans: ['Inter', 'Roboto', 'system-ui', 'sans-serif'],
      }
    },
  },
  plugins: [],
}

