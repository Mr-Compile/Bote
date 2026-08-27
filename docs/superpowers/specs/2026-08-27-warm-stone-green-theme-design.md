# Global Warm-Stone + Green Theme Design

## Goal
Replace the current mixed cool-gray/blue/green Tailwind classes across the BoteBank web app with a single, unified warm-stone and green palette that is consistent for all user roles (public, admin, resident, worker).

## Approach
Option A: Tailwind CDN + one shared JS config.

A new `assets/ts/theme.ts` will register a complete custom color palette under `window.tailwind.config.theme.extend.colors`. The compiled `assets/js/theme.js` will be loaded in every PHP page immediately after the Tailwind CDN. Existing color classes will be mapped to the new palette.

## Color Palette

### Warm Stone (`bote-stone`)
Replaces `slate-*`, `gray-*`, and `neutral-*` usage for backgrounds, cards, text, and borders.

| Token | Hex |
|-------|-----|
| `bote-stone-50`  | `#fafaf9` |
| `bote-stone-100` | `#f5f5f4` |
| `bote-stone-200` | `#e7e5e4` |
| `bote-stone-300` | `#d7d3d0` |
| `bote-stone-400` | `#a89f99` |
| `bote-stone-500` | `#797169` |
| `bote-stone-600` | `#5b5450` |
| `bote-stone-700` | `#44403c` |
| `bote-stone-800` | `#2a2724` |
| `bote-stone-900` | `#1c1917` |
| `bote-stone-950` | `#0c0a09` |

### Bote Green (`bote-green`)
Replaces `green-*`, `emerald-*`, `barangay-green`, `light-green`, and primary `blue-*` buttons.

| Token | Hex |
|-------|-----|
| `bote-green-50`  | `#f4fbf2` |
| `bote-green-100` | `#e3f5df` |
| `bote-green-200` | `#c5e8bf` |
| `bote-green-300` | `#9ed696` |
| `bote-green-400` | `#72b968` |
| `bote-green-500` | `#4f9f45` |
| `bote-green-600` | `#3d7f35` |
| `bote-green-700` | `#31652b` |
| `bote-green-800` | `#275124` |
| `bote-green-900` | `#214220` |
| `bote-green-950` | `#0f2210` |

### Semantic Aliases
| Token | Maps to |
|-------|---------|
| `bote-success` | `bote-green-600` |
| `bote-warning` | `#ca8a04` |
| `bote-danger`  | `#dc2626` |
| `bote-info`    | `#0d9488` |

## Implementation

### 1. Create `assets/ts/theme.ts`
- Export a single `setupBoteTheme(): void` function.
- Build the color record for `bote-stone-*` and `bote-green-*` scales plus the four semantic aliases.
- Merge into `window.tailwind.config.theme.extend.colors` before `tailwindCSS?.refresh()` is called.
- Include a matching `assets/ts/global.d.ts` declaration update if needed.

### 2. Compile with the existing build
- `assets/tsconfig.json` already compiles `assets/ts/index.ts` and `shared.ts`; add `theme.ts` to the build or rely on `tsc` to include it via the tsconfig glob.
- Run `npm run build` (or `build.bat`) to produce `assets/js/theme.js`.

### 3. Load `theme.js` on every page
Add this immediately after the Tailwind CDN `<script>` in every page `<head>`:

```html
<script type="module" src="assets/js/theme.js"></script>
```

Pages to update:
- `index.php`
- `login.php`
- `register.php`
- `admin/*.php`
- `resident/*.php`
- `worker/*.php`
- `shared/upload_photo.php`

### 4. Class mapping
Replace the most common color classes globally. A script or careful find/replace pass will be used to avoid breaking status badges.

| From | To |
|------|-----|
| `bg-gray-50` | `bg-bote-stone-50` |
| `bg-gray-100` | `bg-bote-stone-100` |
| `bg-gray-200` | `bg-bote-stone-200` |
| `bg-gray-300` | `bg-bote-stone-300` |
| `border-gray-100` | `border-bote-stone-100` |
| `border-gray-200` | `border-bote-stone-200` |
| `border-gray-300` | `border-bote-stone-300` |
| `text-gray-500` | `text-bote-stone-500` |
| `text-gray-600` | `text-bote-stone-600` |
| `text-gray-700` | `text-bote-stone-700` |
| `text-gray-800` | `text-bote-stone-800` |
| `text-gray-900` | `text-bote-stone-900` |
| `bg-barangay-green` | `bg-bote-green-700` |
| `text-barangay-green` | `text-bote-green-700` |
| `bg-light-green` | `bg-bote-green-200` |
| `text-light-green` | `text-bote-green-700` |
| `hover:bg-green-700` | `hover:bg-bote-green-800` |
| `bg-blue-600`, `bg-green-600` for primary CTAs | `bg-bote-green-600` |
| `hover:bg-blue-700`, `hover:bg-green-700` for primary CTAs | `hover:bg-bote-green-700` |
| `focus:ring-green-500`, `focus:ring-blue-500` | `focus:ring-bote-green-500` |
| `focus:border-green-500`, `focus:border-blue-500` | `focus:border-bote-green-500` |

### 5. Preserve semantic/status colors
Status badges and alerts will keep clear color coding, but where they currently use generic `bg-green-100 text-green-800` etc., they will use the new `bote-green-*` equivalents. Examples:
- Completed/Active/In Stock → `bg-bote-green-100 text-bote-green-800`
- Pending/Scheduled → keep amber/yellow (`bg-amber-100 text-amber-800`) to avoid confusion
- Cancelled/Rejected → keep red (`bg-red-100 text-red-800`)
- Info → use `bote-info` (`bg-cyan-100 text-cyan-800` or `bg-teal-100 text-teal-800`)

## Files expected to change
- `assets/ts/theme.ts` (new)
- `assets/js/theme.js` (generated)
- `assets/ts/index.ts` (remove its inline `setupTailwindConfig` and `TAILWIND_COLORS` in favor of `theme.js`)
- `assets/js/index.js` (regenerated)
- `assets/ts/global.d.ts` (add window.tailwind type if not already present)
- Every `*.php` page that loads Tailwind CDN (script tag insertion + class renames)
- Optional `admin/assets/css/*.css`, `resident/assets/css/*.css`, `worker/assets/css/*.css` if they contain hardcoded colors

## Verification
1. `npm run build` succeeds with no TypeScript errors.
2. `assets/js/theme.js` is generated and contains the full color map.
3. No `barangay-green` or `light-green` references remain in `*.php` files.
4. Spot-check public landing, login, admin dashboard, resident dashboard, worker dashboard in a browser for consistent warm-stone backgrounds and green CTAs.
5. Run a grep for leftover `bg-gray-`, `text-gray-`, `bg-blue-600`, `bg-green-600` in `*.php` and confirm they are only in status contexts that should stay colored.
