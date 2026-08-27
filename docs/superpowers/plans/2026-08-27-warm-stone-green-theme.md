# Global Warm-Stone + Green Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a single shared `theme.ts` Tailwind config and apply the warm-stone + green palette across all PHP pages in the BoteBank web app.

**Architecture:** A compiled `assets/js/theme.js` module registers `bote-stone-*`, `bote-green-*`, and semantic aliases under `window.tailwind.config.theme.extend.colors` after the CDN loads. Each PHP page adds a `<script type="module" src="assets/js/theme.js"></script>` right after the Tailwind CDN. Existing color classes are then mapped to the new palette with find/replace passes over PHP and generated JS files.

**Tech Stack:** TypeScript 5.5, Tailwind CSS CDN, WAMP/Apache, npm scripts, PHP HTML templates.

## Global Constraints
- Keep the existing Tailwind CDN setup; do not introduce a full Tailwind CLI build.
- Use the existing `assets/tsconfig.json` build chain; add new `*.ts` files under `assets/ts/`.
- Compiled output must be in `assets/js/`.
- Preserve semantic status colors (pending/amber, success/green, cancelled/red, info/teal) where they communicate state.
- Do not modify PHP business logic; only change `class` attributes and `<script>` includes.
- Do not remove or rename the `barangay-green` / `light-green` tokens until the replacement is loaded and verified.

---

### Task 1: Create and compile `assets/ts/theme.ts`

**Files:**
- Create: `assets/ts/theme.ts`
- Modify: `assets/ts/global.d.ts` (add `setupBoteTheme` to `Window` if needed)
- Test: `assets/js/theme.js` generated output

**Interfaces:**
- Produces: `setupBoteTheme(): void` exported as default or named export, sets `window.tailwind.config.theme.extend.colors` and calls `window.tailwind?.refresh?.()`.
- Consumes: `window.tailwind` (from CDN) and `window.tailwindCSS` (optional CDN refresh hook).

- [ ] **Step 1: Write `assets/ts/theme.ts`**

```typescript
export function setupBoteTheme(): void {
    const colors: Record<string, string> = {
        'bote-stone-50': '#fafaf9',
        'bote-stone-100': '#f5f5f4',
        'bote-stone-200': '#e7e5e4',
        'bote-stone-300': '#d7d3d0',
        'bote-stone-400': '#a89f99',
        'bote-stone-500': '#797169',
        'bote-stone-600': '#5b5450',
        'bote-stone-700': '#44403c',
        'bote-stone-800': '#2a2724',
        'bote-stone-900': '#1c1917',
        'bote-stone-950': '#0c0a09',
        'bote-green-50': '#f4fbf2',
        'bote-green-100': '#e3f5df',
        'bote-green-200': '#c5e8bf',
        'bote-green-300': '#9ed696',
        'bote-green-400': '#72b968',
        'bote-green-500': '#4f9f45',
        'bote-green-600': '#3d7f35',
        'bote-green-700': '#31652b',
        'bote-green-800': '#275124',
        'bote-green-900': '#214220',
        'bote-green-950': '#0f2210',
        'bote-success': '#3d7f35',
        'bote-warning': '#ca8a04',
        'bote-danger': '#dc2626',
        'bote-info': '#0d9488',
    };

    const existing = window.tailwind;
    const config = {
        theme: {
            extend: {
                colors,
            },
        },
    };

    if (existing) {
        existing.config = config;
    } else {
        window.tailwind = { config };
    }

    window.tailwindCSS?.refresh?.();
    window.tailwind?.refresh?.();
}

setupBoteTheme();
```

- [ ] **Step 2: Update `assets/ts/global.d.ts`**

Add to the existing `Window` interface:

```typescript
setupBoteTheme?: () => void;
```

- [ ] **Step 3: Compile TypeScript**

Run: `npm run build` (or `build.bat` if PATH is not set)

Expected: `assets/js/theme.js` is created with the color map and no TypeScript errors.

- [ ] **Step 4: Verify generated output**

Run: `grep -l "bote-stone-50" assets/js/theme.js`

Expected: The file contains `bote-stone-50` and `bote-green-600` definitions.

---

### Task 2: Load `theme.js` on every PHP page

**Files:**
- Modify: every PHP page that loads the Tailwind CDN.

**Interfaces:**
- Consumes: `assets/js/theme.js` module.
- Produces: each page now loads the theme config before any inline/module scripts that depend on Tailwind colors.

- [ ] **Step 1: Find all pages with Tailwind CDN**

Run: `grep -rl "cdn.tailwindcss.com" --include="*.php" .`

Expected: a list including `index.php`, `login.php`, `register.php`, and files under `admin/`, `resident/`, `worker/`, and `shared/`.

- [ ] **Step 2: Insert the theme module script**

Immediately after every `<script src="https://cdn.tailwindcss.com"></script>` line, add:

```html
<script type="module" src="assets/js/theme.js"></script>
```

Use the relative path `assets/js/theme.js` for root-level files (`index.php`, `login.php`, `register.php`).
Use `../assets/js/theme.js` for files in `admin/`, `resident/`, `worker/`, and `shared/` subdirectories (adjust to the actual relative depth of each page).

- [ ] **Step 3: Remove old inline Tailwind config from `assets/ts/index.ts`**

Delete the `TAILWIND_COLORS` record and `setupTailwindConfig()` function from `assets/ts/index.ts`. Keep the other page behaviors (smooth scroll, nav scroll, mobile menu, carousel). Remove the `setupTailwindConfig()` call.

- [ ] **Step 4: Recompile public JS**

Run: `npm run build`

Expected: `assets/js/index.js` no longer references `barangay-green` or `light-green`.

---

### Task 3: Map public pages to the new palette

**Files:**
- Modify: `index.php`, `login.php`, `register.php`, `assets/js/index.js` (regenerated from `assets/ts/index.ts`)

**Interfaces:**
- Consumes: `bote-green-*` and `bote-stone-*` Tailwind colors.

- [ ] **Step 1: Replace public page color classes**

In `index.php`, `login.php`, and `register.php`:

| Find | Replace |
|------|---------|
| `bg-gray-50` | `bg-bote-stone-50` |
| `bg-gray-100` | `bg-bote-stone-100` |
| `bg-gray-200` | `bg-bote-stone-200` |
| `bg-gray-700` | `bg-bote-stone-700` |
| `bg-gray-800` | `bg-bote-stone-800` |
| `text-gray-700` | `text-bote-stone-700` |
| `text-gray-900` | `text-bote-stone-900` |
| `border-gray-200` | `border-bote-stone-200` |
| `bg-barangay-green` | `bg-bote-green-700` |
| `text-barangay-green` | `text-bote-green-700` |
| `bg-light-green` | `bg-bote-green-200` |
| `text-light-green` | `text-bote-green-700` |
| `hover:bg-green-700` | `hover:bg-bote-green-800` |
| `hover:bg-gray-100` | `hover:bg-bote-stone-100` |
| `bg-white text-bote-green-700` buttons that used to be white-on-green | keep as-is where they are white buttons with green text |

- [ ] **Step 2: Update mobile menu in `assets/ts/index.ts`**

Change the dynamic HTML string in `initMobileMenu()` from:

```typescript
mobileMenu.innerHTML = `
    <a href="index.php" class="block text-gray-700 hover:text-barangay-green font-medium py-2">Home</a>
    <a href="#about" class="block text-gray-700 hover:text-barangay-green font-medium py-2">About Us</a>
    <a href="login.php" class="block bg-barangay-green text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors mt-2 text-center">Login</a>
`;
```

To:

```typescript
mobileMenu.innerHTML = `
    <a href="index.php" class="block text-bote-stone-700 hover:text-bote-green-700 font-medium py-2">Home</a>
    <a href="#about" class="block text-bote-stone-700 hover:text-bote-green-700 font-medium py-2">About Us</a>
    <a href="login.php" class="block bg-bote-green-700 text-white px-4 py-2 rounded-lg hover:bg-bote-green-800 transition-colors mt-2 text-center">Login</a>
`;
```

- [ ] **Step 3: Recompile public JS**

Run: `npm run build`

- [ ] **Step 4: Verify public pages in browser**

Open `http://localhost/Bote/index.php`, `login.php`, and `register.php`. Confirm backgrounds are warm stone and primary buttons/accents are the new green.

---

### Task 4: Map admin pages to the new palette

**Files:**
- Modify: `admin/admin_*.php`, `admin/carousel_editor.php`, `admin/assets/js/*.js` where needed.

**Interfaces:**
- Consumes: `bote-stone-*` and `bote-green-*`.

- [ ] **Step 1: Apply the same class replacements as Task 3 to all `admin/*.php` files**

Also include:

| Find | Replace |
|------|---------|
| `bg-blue-600` | `bg-bote-green-600` |
| `hover:bg-blue-700` | `hover:bg-bote-green-700` |
| `bg-blue-500` | `bg-bote-green-600` |
| `hover:bg-blue-600` | `hover:bg-bote-green-700` |
| `bg-green-600` (primary buttons) | `bg-bote-green-600` |
| `hover:bg-green-700` (primary buttons) | `hover:bg-bote-green-700` |
| `focus:ring-green-500` | `focus:ring-bote-green-500` |
| `focus:border-green-500` | `focus:border-bote-green-500` |
| `focus:ring-blue-500` | `focus:ring-bote-green-500` |
| `focus:border-blue-500` | `focus:border-bote-green-500` |

- [ ] **Step 2: Preserve status badges**

Leave these state classes unchanged if used inside status maps (e.g., `status_colors`, `event_colors`):
- `bg-green-100 text-green-800` for success/completed
- `bg-yellow-100 text-yellow-800` for pending/low-stock
- `bg-red-100 text-red-800` for cancelled/out-of-stock
- `bg-blue-100 text-blue-800` for scheduled
- `bg-purple-100 text-purple-800` for new/leaderboard updates
- `bg-gray-100 text-gray-800` for completed/inactive

If a status map produces a string, you may either leave it or update it to `bg-bote-green-100 text-bote-green-800` for success states. Do not change status semantics.

- [ ] **Step 3: Insert `theme.js` include in every admin page**

After `cdn.tailwindcss.com`:

```html
<script type="module" src="../assets/js/theme.js"></script>
```

- [ ] **Step 4: Spot-check admin dashboard and one admin CRUD page**

Open `admin/admin_dashboard.php` and `admin/admin_users.php`. Confirm neutral backgrounds, cards, and primary action buttons use the new palette.

---

### Task 5: Map resident pages to the new palette

**Files:**
- Modify: `resident/resident_*.php`

**Interfaces:**
- Consumes: `bote-stone-*` and `bote-green-*`.

- [ ] **Step 1: Apply class replacements from Task 3 and Task 4 to all `resident/*.php` files**

- [ ] **Step 2: Insert `theme.js` include**

After `cdn.tailwindcss.com`:

```html
<script type="module" src="../assets/js/theme.js"></script>
```

- [ ] **Step 3: Spot-check resident dashboard, new request, and rewards pages**

Open `resident/resident_dashboard.php`, `resident/resident_new_request.php`, and `resident/resident_rewards.php`. Confirm consistency.

---

### Task 6: Map worker pages to the new palette

**Files:**
- Modify: `worker/worker_*.php`, `worker/complete_pickup.php`

**Interfaces:**
- Consumes: `bote-stone-*` and `bote-green-*`.

- [ ] **Step 1: Apply class replacements from Task 3 and Task 4 to all `worker/*.php` files**

- [ ] **Step 2: Insert `theme.js` include**

After `cdn.tailwindcss.com`:

```html
<script type="module" src="../assets/js/theme.js"></script>
```

- [ ] **Step 3: Spot-check worker dashboard and daily reports**

Open `worker/worker_dashboard.php` and `worker/worker_daily_reports.php`.

---

### Task 7: Update shared/utility page

**Files:**
- Modify: `shared/upload_photo.php` (or any other `shared/*.php` that loads Tailwind)

- [ ] **Step 1: Insert `theme.js` include**

Use the correct relative path (`../assets/js/theme.js` or `assets/js/theme.js` depending on directory depth).

- [ ] **Step 2: Apply class replacements**

If the page uses gray/green/blue classes, map them the same way.

---

### Task 8: Global verification and cleanup

**Files:**
- Modify: none (verification only).

- [ ] **Step 1: Run the TypeScript build**

Run: `npm run build`

Expected: no errors.

- [ ] **Step 2: Search for leftover legacy tokens**

Run:

```bash
grep -R "barangay-green\|light-green" --include="*.php" --include="*.ts" --include="*.js" . | grep -v "assets/js/theme.js" | grep -v "theme.ts"
```

Expected: no matches outside generated files and the design spec.

- [ ] **Step 3: Search for unexpected cool-gray/blues in primary UI**

Run:

```bash
grep -R "bg-gray-50\|bg-gray-100\|bg-blue-600\|text-barangay-green" --include="*.php" .
```

Expected: only status badge contexts or intentionally left gradients.

- [ ] **Step 4: Browser smoke test**

Open one representative page from each role in a browser:
- Public: `index.php`
- Auth: `login.php`
- Admin: `admin/admin_dashboard.php`
- Resident: `resident/resident_dashboard.php`
- Worker: `worker/worker_dashboard.php`

Confirm:
- Page background is warm stone.
- Primary buttons are green.
- Text and cards use the stone neutrals.
- No broken styles or 404s for `theme.js`.
