import type { GlobalThemeOverrides } from 'naive-ui'

/**
 * Opsyra's brand, from the palette on ADR-001's page: teal accent, cool
 * grey neutrals, Plus Jakarta Sans for headings and Source Sans 3 for body.
 * Naive UI takes these as theme overrides; everything else inherits.
 */
const bodyFont = '"Source Sans 3", -apple-system, "Segoe UI", sans-serif'

export const headingFont = '"Plus Jakarta Sans", "Segoe UI", sans-serif'

const shared = {
  fontFamily: bodyFont,
  fontSize: '15px',
  fontSizeMedium: '15px',
  borderRadius: '8px',
  borderRadiusSmall: '6px',
}

export const lightTheme: GlobalThemeOverrides = {
  common: {
    ...shared,
    primaryColor: '#0E6E62',
    primaryColorHover: '#12806F',
    primaryColorPressed: '#0A5A50',
    primaryColorSuppl: '#12806F',
    bodyColor: '#F6F7F9',
    cardColor: '#FFFFFF',
    textColorBase: '#16202A',
    textColor1: '#16202A',
    textColor2: '#2C3A47',
    textColor3: '#5A6774',
    borderColor: '#DCE1E7',
    dividerColor: '#DCE1E7',
    errorColor: '#A23B45',
    warningColor: '#8A5A00',
    successColor: '#0E6E62',
    infoColor: '#0E6E62',
  },
  Layout: { siderColor: '#FFFFFF', headerColor: '#FFFFFF', color: '#F6F7F9' },
}

export const darkTheme: GlobalThemeOverrides = {
  common: {
    ...shared,
    primaryColor: '#5CC4B4',
    primaryColorHover: '#72D0C1',
    primaryColorPressed: '#48AE9F',
    primaryColorSuppl: '#72D0C1',
    bodyColor: '#11161C',
    cardColor: '#182029',
    modalColor: '#182029',
    popoverColor: '#182029',
    textColorBase: '#E6EAEE',
    textColor1: '#E6EAEE',
    textColor2: '#C9D1D9',
    textColor3: '#9AA6B2',
    borderColor: '#2A3440',
    dividerColor: '#2A3440',
    errorColor: '#EE8C96',
    warningColor: '#E2B35C',
    successColor: '#5CC4B4',
    infoColor: '#5CC4B4',
  },
  Layout: { siderColor: '#182029', headerColor: '#182029', color: '#11161C' },
}
