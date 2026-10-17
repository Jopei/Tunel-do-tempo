import "vuetify/styles";
import "@mdi/font/css/materialdesignicons.css";

import { createVuetify } from "vuetify";

const tunelClaro = {
  dark: false,
  colors: {
    background: "#F3ECDB",
    surface: "#FBF6E6",
    "surface-variant": "#F3ECDB",
    "on-surface": "#2A2112",
    "on-background": "#2A2112",
    primary: "#C7A43A",
    "on-primary": "#2A2112",
    secondary: "#5E5441",
    error: "#B3261E",
    success: "#1E7E34",
    warning: "#8A5A00",
    info: "#1F5F8B",
    "primary-text": "#7A5D0E",
  },
  variables: {
    "border-color": "#2A2112",
    "border-opacity": 0.16,
    "high-emphasis-opacity": 0.92,
    "medium-emphasis-opacity": 0.72,
  },
};

const tunelNoite = {
  dark: true,
  colors: {
    background: "#14110B",
    surface: "#1E1A12",
    "surface-variant": "#2A2418",
    "on-surface": "#F1E8D2",
    "on-background": "#F1E8D2",
    primary: "#E0BE5A",
    "on-primary": "#1B1505",
    secondary: "#B9AE93",
    error: "#FF8A80",
    success: "#66D17A",
    warning: "#F0C86A",
    info: "#8FC1E8",
    "primary-text": "#E0BE5A",
  },
  variables: {
    "border-color": "#F1E8D2",
    "border-opacity": 0.16,
  },
};

export default createVuetify({
  icons: { defaultSet: "mdi" },
  theme: {
    defaultTheme: "tunelClaro",
    themes: { tunelClaro, tunelNoite },
  },
  defaults: {
    VBtn: { rounded: "lg", class: "text-none", color: "primary" },
    VTextField: { variant: "outlined", density: "comfortable", color: "primary" },
    VTextarea: { variant: "outlined", density: "comfortable", color: "primary" },
    VSelect: { variant: "outlined", density: "comfortable", color: "primary" },
    VAutocomplete: { variant: "outlined", density: "comfortable", color: "primary" },
    VCombobox: { variant: "outlined", density: "comfortable", color: "primary" },
    VCard: { rounded: "lg" },
  },
});
