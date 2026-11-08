/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: "class",
  content: ["./app/**/*.{js,jsx}", "./components/**/*.{js,jsx}"],
  theme: {
    extend: {
      colors: {
        ink: "#101b2c",
        muted: "#667085",
        gold: { DEFAULT: "#d7ad62", light: "#f0d9a9", dark: "#9e7739" },
        midnight: "#0b1421",
      },
      fontFamily: {
        sans: ["var(--font-iran-sans)", "ui-sans-serif", "system-ui"],
        inter: ["var(--font-inter)", "ui-sans-serif", "system-ui"],
      },
      boxShadow: {
        glow: "0 0 80px rgba(215, 173, 98, .16)",
        card: "0 24px 80px rgba(16, 27, 44, .10)",
      },
      backgroundImage: {
        "gold-gradient": "linear-gradient(135deg, #f1d9a7 0%, #c18d3f 100%)",
        "hero-shade": "linear-gradient(90deg, rgba(8,16,28,.96) 0%, rgba(8,16,28,.83) 48%, rgba(8,16,28,.22) 100%)",
        "hero-shade-rtl": "linear-gradient(270deg, rgba(8,16,28,.96) 0%, rgba(8,16,28,.83) 48%, rgba(8,16,28,.22) 100%)",
      },
    },
  },
  plugins: [require("tailwindcss-animate")],
};
