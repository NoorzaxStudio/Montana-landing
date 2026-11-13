import { defineConfig, globalIgnores } from "eslint/config";
import globals from "globals";
import nextVitals from "eslint-config-next/core-web-vitals";
import nextTypescript from "eslint-config-next/typescript";

export default defineConfig(
  ...nextVitals,
  ...nextTypescript,
  {
    languageOptions: { globals: { ...globals.browser, ...globals.node } },
  },
  globalIgnores([
    ".next/**",
    ".next-build-output/**",
    "node_modules/**",
    "bluehost/**",
    "archive/**",
    "research/**",
    "scripts/**",
  ]),
);
