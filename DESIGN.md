---
name: PharmaPOS
description: Pharmacy SaaS POS for Nepal — professional, precise, trustworthy
colors:
  primary: oklch(0.53 0.18 250)
  primary-deep: oklch(0.45 0.16 250)
  primary-light: oklch(0.93 0.04 250)
  success: oklch(0.52 0.18 145)
  success-deep: oklch(0.44 0.15 145)
  danger: oklch(0.55 0.22 25)
  danger-deep: oklch(0.48 0.20 25)
  warning: oklch(0.65 0.18 80)
  warning-deep: oklch(0.55 0.16 80)
  sidebar: oklch(0.18 0.03 260)
  sidebar-active: oklch(0.25 0.05 260)
  surface: oklch(1.00 0 0)
  surface-muted: oklch(0.98 0.003 260)
  border: oklch(0.88 0.005 260)
  text: oklch(0.15 0.015 260)
  text-muted: oklch(0.50 0.015 260)
typography:
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 500
    lineHeight: 1.4
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.3
  heading:
    fontFamily: "Inter, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.2
rounded:
  sm: "0.25rem"
  md: "0.375rem"
  lg: "0.5rem"
spacing:
  sm: "0.5rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
components:
  button-primary:
    backgroundColor: "{colors.primary-deep}"
    textColor: "{colors.surface}"
    rounded: "{rounded.md}"
    padding: "0.625rem 1rem"
  button-primary-hover:
    backgroundColor: "oklch(0.38 0.14 250)"
  button-outline:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: "0.625rem 1rem"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.md}"
    padding: "0.5rem 0.75rem"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "1.5rem"
  sidebar:
    backgroundColor: "{colors.sidebar}"
    textColor: "oklch(0.70 0.01 260)"
    width: "16rem"
---

# Design System: PharmaPOS

## 1. Overview

**Creative North Star: "The Organized Shelf"**

A pharmacy counter where every medicine has its place, every label is clear, and the pharmacist's hand moves with practiced efficiency. PharmaPOS translates that physical precision into a digital interface. The system is dense by design: a pharmacist checking stock needs to see batch numbers, expiry dates, quantities, and prices simultaneously. Progressive disclosure is reserved for settings and configuration, never for the dispensing workflow.

The color strategy is Restrained: tinted neutrals for the surface, one saturated primary accent for actions and selection. This is not a dashboard that needs to impress; it is a tool that needs to work. The OKLCH color space ensures perceptual uniformity across all tones, so a warning amber reads as urgently at 50% lightness as it does at 90%.

**Key Characteristics:**
- Information density as a feature, not a problem
- Single accent color (blue at hue 250) for all interactive elements
- Semantic color vocabulary: primary for actions, success for confirmation, danger for errors/expiry, warning for alerts
- Dark sidebar with light content area, providing clear spatial hierarchy
- Inter as the sole typeface, optimized for screen readability at small sizes
- 44px minimum touch targets on all interactive elements

## 2. Colors: The Precision Palette

The palette is functional, not decorative. Each color carries meaning that a pharmacist learns to read at a glance: blue means "you can act on this," green means "confirmed/in-stock," red means "check this now," amber means "attention needed."

### Primary (Actions, Selection, Focus)
- **Clear Blue** (oklch(0.53 0.18 250)): The single accent color. Used for primary buttons, active navigation items, selected states, focus rings, and links. Its saturation (0.18) is high enough to be immediately identifiable against the neutral surface, but restrained enough to not overwhelm data-dense screens.

### Success (Confirmation, In-Stock, Paid)
- **Pharmacy Green** (oklch(0.52 0.18 145)): Confirms positive states. A paid invoice badge, an in-stock indicator, a successful dispensing. Same saturation as primary for visual balance.

### Danger (Errors, Expiry, Out-of-Stock)
- **Alert Red** (oklch(0.55 0.22 25)): The highest chroma in the palette. Reserved for urgent information: expired medicines, out-of-stock warnings, validation errors, failed payments. Its intensity is intentional; a pharmacist must never miss an expiry alert.

### Warning (Low Stock, Attention)
- **Caution Amber** (oklch(0.65 0.18 80)): Between green and red. Low stock alerts, expiring-soon warnings, pending actions. Less urgent than danger but more noticeable than neutral.

### Neutral (Surfaces, Text, Borders)
- **Surface** (oklch(1.00 0 0)): Pure white background. Tinted slightly toward the primary hue in dark mode.
- **Surface Muted** (oklch(0.98 0.003 260)): Subtle off-white for secondary backgrounds, table row alternation, disabled states.
- **Border** (oklch(0.88 0.005 260)): Light separator between elements. Visible but not prominent.
- **Text** (oklch(0.15 0.015 260)): Near-black for primary content. High contrast ratio (>15:1) against white.
- **Text Muted** (oklch(0.50 0.015 260)): Secondary text for labels, descriptions, timestamps.

### Sidebar (Navigation)
- **Sidebar** (oklch(0.18 0.03 260)): Very dark blue-gray. Provides strong contrast with the light content area, establishing clear spatial hierarchy.
- **Sidebar Active** (oklch(0.25 0.05 260)): Slightly lighter for the current navigation item.
- **Sidebar Hover** (oklch(0.22 0.04 260)): Intermediate state between default and active.

**The Rarity Rule.** The primary accent appears only on interactive elements and active states. It never decorates. Its scarcity is the point; when you see blue, you know you can click it.

## 3. Typography

**Display Font:** Inter (with system-ui fallback)
**Body Font:** Inter (with system-ui fallback)

**Character:** Inter is a workhorse sans-serif designed for screen readability. It has tabular numerals (critical for a POS system displaying prices), clear letterforms at small sizes, and a neutral personality that doesn't compete with data. No display font is needed; the hierarchy comes from weight and size contrast, not font pairing.

### Hierarchy
- **Heading** (700, 1.5rem/24px, line-height 1.2): Page titles, section headers. Used sparingly; one per screen.
- **Title** (600, 1.125rem/18px, line-height 1.3): Card titles, form section labels, table headers.
- **Label** (500, 0.875rem/14px, line-height 1.4): Form labels, button text, navigation items, badge text. The most-used weight.
- **Body** (400, 0.875rem/14px, line-height 1.5): General content, descriptions, help text. Max line length: 65-75ch.
- **Small** (400, 0.75rem/12px, line-height 1.4): Timestamps, secondary metadata, table cell details.

**The Tabular Numerals Rule.** All numeric data (prices, quantities, dates) uses tabular figures. A pharmacist comparing stock levels across batches must be able to scan vertically without numbers jumping left and right.

## 4. Elevation

Flat by default. The system uses tonal layering rather than shadows for depth: the sidebar is dark, the content area is light, and modals use a semi-transparent overlay. Shadows exist only as a subtle `shadow-sm` on cards (a single diffuse shadow) and `shadow-lg` on dropdowns/modals (indicating floating above the surface).

### Shadow Vocabulary
- **Ambient Low** (`shadow-sm`): Subtle lift on cards and containers. Indicates a discrete surface without competing with content.
- **Ambient High** (`shadow-lg`): Floating elements: dropdown menus, modals, popovers. Signals "above the content layer."

**The Flat-By-Default Rule.** Surfaces are flat at rest. Shadows appear only as a response to state (hover, elevation, focus). No decorative shadows, no multi-layer depth effects, no inset shadows on inputs.

## 5. Components

### Buttons
- **Shape:** Gently curved edges (md radius, 6px)
- **Primary:** Deep blue background, white text, 44px height. The default action button.
- **Hover:** Slightly darker blue (oklch(0.38 0.14 250)). 150ms transition.
- **Focus:** 2px ring in primary-500 with 2px offset. Always visible on keyboard navigation.
- **Destructive:** Red background for dangerous actions (delete, cancel).
- **Outline:** Bordered, transparent background for secondary actions.
- **Ghost:** No border, no background. Hover reveals surface-muted background.
- **Disabled:** 50% opacity, pointer-events disabled.

### Cards
- **Corner Style:** Gentle curve (lg radius, 8px)
- **Background:** White surface with subtle border
- **Shadow:** Single ambient-low shadow-sm
- **Border:** 1px border-border
- **Internal Padding:** 1.5rem (24px) on header/footer, 0 on content (content controls its own padding)

### Inputs
- **Style:** Filled background (surface), subtle border
- **Focus:** 2px ring in primary-500 with 2px offset. Border color shifts to primary.
- **Error:** Border shifts to danger-500, ring shifts to danger. Error message below in danger-600.
- **Disabled:** 50% opacity, cursor changes to not-allowed.
- **Label:** Above input, 500 weight, text-text color.
- **Hint:** Below input, 400 weight, text-text-muted color.

### Navigation (Sidebar)
- **Style:** Dark background (sidebar), vertical list of links
- **Default:** text-white/70, no background
- **Hover:** sidebar-hover background, text-white
- **Active:** sidebar-active background, text-white, font-medium
- **Collapsed:** 64px wide, icons only with title tooltips
- **Expanded:** 256px wide, icons + text labels
- **Mobile:** Overlay with backdrop, dismissible

### Badges
- **Style:** Rounded-full (pill shape), small text (12px), semibold
- **Variants:** Primary (blue bg), Success (green bg), Danger (red bg), Warning (amber bg), Secondary (gray bg)
- **Usage:** Status indicators, schedule types, payment status, stock levels

### Data Tables
- **Header:** Surface-muted background, 500 weight text, sortable columns with indicator
- **Rows:** White background, hover surface-muted, border-border between rows
- **Pagination:** Bottom of table, centered, with page numbers and prev/next buttons (44px touch targets)
- **Empty State:** Icon + heading + description centered in table body

### Dialogs/Modals
- **Overlay:** Black at 50% opacity
- **Container:** White surface, lg radius, shadow-lg
- **Focus Trap:** Tab/Shift+Tab cycling, Escape to close
- **Structure:** Header (title + close), Content, Footer (actions)

### Toast Notifications
- **Position:** Bottom-right, stacked
- **Types:** Success (green), Error (red), Warning (amber), Info (blue)
- **Auto-dismiss:** 5 seconds
- **ARIA:** role="alert" for errors, aria-live="polite" for info

## 6. Do's and Don'ts

### Do:
- **Do** use semantic color tokens (text-text, bg-surface, border-border) instead of hard-coded gray/white values.
- **Do** use OKLCH for all color definitions. The perceptual uniformity is essential for a data-dense interface where colors must be distinguishable at every lightness level.
- **Do** maintain 44px minimum touch targets on all interactive elements. Pharmacists use touchscreens at the counter.
- **Do** show both AD and Bikram Sambat dates. Nepal uses both calendars.
- **Do** format currency as NPR with lakh/crore notation (रू 1,00,000).
- **Do** use Inter's tabular figures for all numeric data.
- **Do** keep the sidebar dark and the content area light. The contrast establishes spatial hierarchy.
- **Do** use focus-visible rings on all interactive elements for keyboard accessibility.

### Don't:
- **Don't** use gradient text, glassmorphism, or decorative blur effects. PRODUCT.md explicitly rejects these as consumer app aesthetics.
- **Don't** use identical card grids with icon + big number + small label. This is the hero-metric template that PRODUCT.md identifies as an AI-generated pattern.
- **Don't** use purple gradients, neon accents, or crypto-aesthetic. PRODUCT.md explicitly rejects dark-mode SaaS clichés.
- **Don't** use playful fonts, rounded bubbly shapes, or gamification. This is a professional healthcare tool, not a consumer app.
- **Don't** use em dashes in UI copy. Use commas, colons, semicolons, periods, or parentheses.
- **Don't** wrap everything in cards. Cards are for discrete content blocks, not for wrapping entire pages.
- **Don't** use bounce or elastic easing animations. Transitions are ease-out, 150-300ms.
- **Don't** hide data behind progressive disclosure in the dispensing workflow. Pharmacists need to see batch numbers, expiry dates, and prices simultaneously.
- **Don't** use `#000` or `#fff` directly. Tint every neutral toward the brand hue (chroma 0.005-0.01).
