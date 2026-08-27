# Testimonials Manager

A complete, self-contained WordPress plugin for managing, importing, and displaying customer testimonials — with a CSV/XLSX bulk importer, categories, featured testimonials, and shortcode-driven carousel, grid, and list layouts.

Built by **Muideen Saliu** ([github.com/saliu74](https://github.com/saliu74/)).

No Composer, no npm build step, no external services required. Everything runs on a stock PHP 7.4+ / WordPress 5.8+ install, including typical shared hosting / cPanel environments.

---

## Table of contents

- [Installation](#installation)
- [Activation](#activation)
- [Creating testimonials](#creating-testimonials)
- [Creating categories](#creating-categories)
- [Importing from CSV](#importing-from-csv)
- [Importing from XLSX](#importing-from-xlsx)
- [Column mapping](#column-mapping)
- [Duplicate handling](#duplicate-handling)
- [Shortcode reference](#shortcode-reference)
- [Carousel examples](#carousel-examples)
- [Grid examples](#grid-examples)
- [List examples](#list-examples)
- [Gutenberg block](#gutenberg-block)
- [Settings](#settings)
- [Styling & customization](#styling--customization)
- [Troubleshooting](#troubleshooting)
- [Developer documentation](#developer-documentation)
- [File structure](#file-structure)
- [Changelog](#changelog)

---

## Installation

1. Copy the `testimonials-manager` folder into `wp-content/plugins/`, or zip the folder and upload it via **Plugins → Add New → Upload Plugin**.
2. Activate **Testimonials Manager** from the Plugins screen.

No build step is required — every asset is already plain CSS/JS/PHP ready to run.

## Activation

On activation the plugin:

- Registers the `testimonial` custom post type and `testimonial_category` taxonomy.
- Seeds a default settings option (only if one doesn't already exist).
- Flushes rewrite rules once.

It does **not** create any sample content automatically. Deactivating the plugin never deletes your testimonials, categories, or settings — and neither does deleting the plugin, unless you've explicitly opted in under **Testimonials → Settings → Data → "Delete all data on uninstall"**.

## Creating testimonials

Go to **Testimonials → Add New**:

- **Title** — used internally / for admin search; not required to be shown on the front end.
- **Content editor** — the testimonial text itself.
- **Featured Image** — the customer's photo (shown as their avatar).
- **Customer Details** box — name (required), title, company, location, email (never shown publicly), website.
- **Rating & Display** box — 1–5 star rating and a Featured toggle.
- Normal WordPress **Publish** controls — Draft / Pending / Published, scheduling, etc.

## Creating categories

Go to **Testimonials → Categories** (standard WordPress taxonomy screen) to add your own categories — e.g. *Customers*, *Clients*, *Students*, *Partners*, *Investors*, *Google Reviews*. Nothing is hard-coded; create as many as you like.

## Importing from CSV

1. Go to **Testimonials → Import**.
2. Drag & drop (or click to choose) a `.csv` file, then **Upload & Preview**.
3. Review the detected columns and the first ~20 rows.

The importer auto-detects the CSV delimiter (comma, semicolon, tab, or pipe) and strips a UTF-8 BOM if present, so exports from Excel, Google Sheets, and most CRMs work without adjustment.

## Importing from XLSX

Same flow — choose a `.xlsx` file instead. The plugin reads the first worksheet using a small built-in reader (PHP's `ZipArchive` + `SimpleXML`, both bundled with PHP by default) — **no Composer or PhpSpreadsheet dependency required**, so it works out of the box on shared hosting.

> If your host has disabled the `zip` or `simplexml` PHP extensions (rare), you'll see a clear error asking you to export as CSV instead.

## Column mapping

Spreadsheets rarely use the plugin's exact field names, so step 2 lets you map each spreadsheet column to a destination field:

| Destination field | Auto-detected from headers like |
|---|---|
| Customer Name *(required)* | Name, Customer, Client, Customer Name, Client Name, Full Name |
| Testimonial Content *(required)* | Testimonial, Review, Comment, Feedback, Review Text |
| Customer Title | Position, Job Title, Title, Role |
| Company | Company, Business, Organisation, Organization |
| Location | Location, City, Address |
| Email | Email, E-mail |
| Website | Website, URL, Link |
| Rating | Rating, Stars, Score |
| Date | Date, Review Date, Submitted |
| Category | Category, Categories, Type |
| Featured | Featured, Is Featured, Highlight |

Unmapped columns are simply ignored. You can always override the automatic guess before continuing.

## Duplicate handling

Duplicates are detected by comparing a **normalized hash of Customer Name + Testimonial Content** — not the generated post title — so re-running the same import (or importing an updated export of the same reviews) behaves predictably. Choose one of:

- **Skip duplicates** (default) — leave existing matching testimonials untouched.
- **Update existing testimonials** — refresh the matched testimonial's fields.
- **Import duplicates anyway** — always create a new testimonial.

Large files are processed in batches of 100 rows via background AJAX requests, so imports of thousands of rows won't hit a PHP timeout. Progress, and the running imported/updated/skipped/failed counts, are shown live. **The server — not the browser — tracks which rows have been processed**, so refreshing the browser mid-import can never cause the same rows to be imported twice; it simply pauses the progress bar until you return.

## Shortcode reference

```
[testimonials]
```

Attributes (all optional — unset attributes fall back to **Testimonials → Settings**):

| Attribute | Values | Description |
|---|---|---|
| `layout` | `grid` \| `carousel` \| `list` | Display layout |
| `limit` | integer | Number of testimonials per page/view |
| `category` | slug (comma-separated for multiple) | Filter by category |
| `featured` | `true` \| `false` | Only featured / exclude featured |
| `rating` | `1`–`5` | Minimum star rating |
| `columns` | `1`–`6` | Grid columns (desktop) |
| `pagination` | `true` \| `false` | Grid pagination |
| `orderby` | `date` \| `title` \| `rating` \| `menu_order` \| `rand` | Sort field |
| `order` | `ASC` \| `DESC` | Sort direction |
| `autoplay` | `true` \| `false` | Carousel autoplay |
| `interval` | milliseconds | Autoplay speed |
| `arrows` | `true` \| `false` | Carousel arrows |
| `dots` | `true` \| `false` | Carousel dots |
| `slides_desktop` / `slides_tablet` / `slides_mobile` | integer | Carousel slides per view per breakpoint |
| `show_images` | `true` \| `false` | Show/hide customer photos |
| `show_ratings` | `true` \| `false` | Show/hide star ratings |

## Carousel examples

```
[testimonials layout="carousel"]

[testimonials
    layout="carousel"
    limit="6"
    featured="true"
    autoplay="true"
    interval="5000"
    arrows="true"
    dots="true"
]
```

The carousel is a lightweight vanilla-JS implementation (no jQuery/Slick/Swiper dependency) that supports touch swipe, arrow-key navigation, a pause/play control, and honors `prefers-reduced-motion` by disabling autoplay for users who've requested reduced motion.

## Grid examples

```
[testimonials layout="grid"]

[testimonials
    layout="grid"
    limit="12"
    columns="3"
    pagination="true"
    category="customers"
]
```

Grid columns automatically drop to 2 on tablet and 1 on mobile regardless of the configured desktop column count. Pagination is handled with real WordPress queries (never loading more than one page of testimonials into the browser at a time) and is progressively enhanced with AJAX — it still works with JavaScript disabled via plain page links.

## List examples

```
[testimonials layout="list" limit="10"]
```

A simple vertical stack — handy for sidebars or long-form service pages.

## Gutenberg block

A basic **Testimonials** block is available in the block inserter (search "Testimonials"), with controls for layout, count, category, and featured-only. It renders through the exact same code path as the shortcode, so there's only one rendering implementation to maintain. The shortcode remains the primary, fully-supported API and works inside Elementor's Shortcode widget or any other page builder without any special integration.

## Settings

**Testimonials → Settings** lets you set plugin-wide defaults for layout, grid columns/pagination, carousel autoplay/arrows/dots/slides-per-view, card appearance (radius, image size, alignment), and import defaults (publishing status, featured handling) — so most shortcodes on your site can omit those attributes entirely.

## Styling & customization

All markup is namespaced (`tm-testimonials`, `tm-testimonial-card`, `tm-testimonial-carousel`, `tm-testimonial-grid`, `tm-rating`, etc.) to avoid clashing with theme classes like `.card` or `.title`. Colors, radius, and spacing are exposed as CSS custom properties on `.tm-testimonials`:

```css
.tm-testimonials {
  --tm-card-background: #ffffff;
  --tm-card-border: #e5e7eb;
  --tm-text-color: #1f2937;
  --tm-muted-color: #6b7280;
  --tm-accent-color: #2563eb;
  --tm-star-color: #f5a623;
  --tm-card-radius: 12px;
  --tm-card-shadow: 0 1px 3px rgba(0,0,0,.08);
}
```

Override these from your theme's stylesheet, the Customizer's Additional CSS box, or a child theme. Theme developers can also override any template file by copying it to `your-theme/testimonials-manager/{template-name}.php`.

CSS/JS assets are only enqueued on pages that actually use the shortcode or block, and carousel JS/CSS is skipped entirely on pages that only use the grid or list layout.

## Troubleshooting

- **"The uploaded file does not appear to be a valid CSV or XLSX file."** — the file's real content doesn't match its extension. Re-save/re-export the file and try again.
- **XLSX import fails immediately** — your host may have the `zip` or `simplexml` PHP extension disabled. Export the spreadsheet as CSV instead.
- **Import seems stuck** — check your browser console for blocked AJAX requests (security plugins/firewalls occasionally block `admin-ajax.php`); refreshing the Import page will show the true server-side progress rather than restarting it.
- **Carousel doesn't move** — if you've enabled "reduce motion" at the OS level, autoplay is intentionally disabled; use the arrow buttons or dots instead.
- **Styling looks off** — check for theme CSS targeting very generic class names; the plugin avoids this on its own side, but some themes apply global styles to plain tags like `img` or `button` inside `.entry-content`.

## Developer documentation

- **Custom post type:** `testimonial` (public, REST-enabled at `/wp-json/wp/v2/testimonials`, excluded from core search).
- **Taxonomy:** `testimonial_category` (hierarchical, REST-enabled).
- **Post meta:** `_tm_customer_name`, `_tm_customer_title`, `_tm_company`, `_tm_location`, `_tm_email`, `_tm_website`, `_tm_rating` (0–5), `_tm_featured` (`'1'`/`''`), `_tm_testimonial_date`, `_tm_import_hash` (internal, duplicate detection).
- **Filters:**
  - `tm_testimonial_post_type_args`, `tm_testimonial_taxonomy_args` — adjust CPT/taxonomy registration.
  - `tm_testimonials_query_args` — adjust the `WP_Query` args built from shortcode attributes.
  - `tm_no_testimonials_message` — customize the empty-state message.
  - `tm_template_path` — override which template file is loaded.
  - `tm_import_max_file_size` — adjust the importer's max upload size (default 20MB).
- **Template overrides:** copy any file from `templates/` into `your-theme/testimonials-manager/` to override it (uses `locate_template()`).
- **Extensibility:** the codebase is intentionally modular (one class per concern) so future features — CSV/JSON export, a REST endpoint, additional layouts, an Elementor widget, frontend submission — can be added as new classes without touching existing ones.

## File structure

```
testimonials-manager/
├── testimonials-manager.php       Plugin bootstrap, autoloader, activation/deactivation
├── uninstall.php                  Opt-in data cleanup
├── readme.txt                     WordPress.org-style readme
├── README.md                      This file
│
├── includes/
│   ├── class-tm-plugin.php        Core singleton wiring up every module
│   ├── class-tm-post-type.php     `testimonial` CPT + admin list table columns
│   ├── class-tm-taxonomy.php      `testimonial_category` taxonomy
│   ├── class-tm-meta-boxes.php    Customer details / rating / featured meta boxes
│   ├── class-tm-shortcodes.php    [testimonials] shortcode + AJAX grid pagination
│   ├── class-tm-settings.php      Plugin-wide default settings
│   ├── class-tm-assets.php        Conditional CSS/JS enqueueing
│   ├── class-tm-importer.php      Import AJAX orchestration (upload/start/batch/status/cancel)
│   ├── class-tm-demo-data.php     Optional demo testimonial generator
│   ├── class-tm-block.php         Gutenberg block (renders via the shortcode)
│   ├── tm-template-functions.php  Template loader + helper functions
│   └── importer/
│       ├── class-tm-csv-reader.php        Dependency-free CSV reader
│       ├── class-tm-xlsx-reader.php       Dependency-free XLSX reader (Zip+SimpleXML)
│       ├── class-tm-import-validator.php  Column auto-detection + row validation
│       └── class-tm-import-processor.php  Row → post insert/update + duplicate detection
│
├── admin/
│   ├── class-tm-admin.php         Admin menu + page rendering
│   ├── views/
│   │   ├── import.php             Import wizard (4-step UI)
│   │   └── settings.php           Settings page
│   ├── css/admin.css
│   └── js/
│       ├── admin.js               Import wizard logic (AJAX-driven)
│       └── block.js               Gutenberg block editor script
│
├── public/
│   ├── css/
│   │   ├── testimonials.css       Base styles + CSS custom properties
│   │   ├── carousel.css
│   │   └── grid.css
│   └── js/
│       └── testimonials.js        Carousel + AJAX grid pagination
│
└── templates/
    ├── testimonial-card.php       Single card partial (shared by all layouts)
    ├── carousel.php
    ├── grid.php
    ├── grid-items.php             Inner cards partial (shared with AJAX pagination)
    └── list.php
```

## Changelog

### 1.0.0
- Initial release: manual testimonial management, categories, CSV/XLSX bulk importer with column mapping and duplicate detection, carousel/grid/list shortcode layouts, Gutenberg block, settings page, accessibility support, and full documentation.
