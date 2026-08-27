=== Testimonials Manager ===
Contributors: saliu74
Tags: testimonials, reviews, carousel, import, csv
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage, bulk-import, and display customer testimonials with shortcodes for carousel, grid, and list layouts.

== Description ==

Testimonials Manager lets you create unlimited testimonials manually or import them in bulk from a CSV or XLSX spreadsheet, organize them into categories, mark favorites as featured, and display them anywhere on your site using a simple shortcode — with a beautiful, accessible carousel for your homepage and a paginated grid for a dedicated testimonials page.

**Key features**

* Manual testimonial creation with customer name, title, company, location, rating, and photo.
* Unlimited custom categories — nothing is hard-coded.
* Bulk import from CSV or XLSX with a guided column-mapping wizard, automatic column detection, and duplicate handling (skip / update / import anyway).
* Large imports are processed safely in the background in batches, with a live progress bar — no PHP timeouts, and refreshing the browser never creates duplicate records.
* Three shortcode layouts: `carousel`, `grid`, and `list` — fully responsive, with configurable columns and slides-per-view for desktop/tablet/mobile.
* A lightweight, dependency-free carousel: touch-swipe, keyboard navigation, pause/play control, and respects `prefers-reduced-motion`.
* Accessible star ratings with proper screen-reader text.
* Grid pagination that never loads more than one page of testimonials into the browser at once.
* A simple Gutenberg block, and full compatibility with the Classic Editor, Elementor's Shortcode widget, and any standards-compliant theme.
* CSS custom properties for easy theming, and namespaced classes that won't clash with your theme.
* No Composer, no npm build step, no external services — works out of the box on typical shared hosting.

= No hard dependencies =

The XLSX importer uses PHP's built-in `ZipArchive` and `SimpleXML` extensions rather than bundling a large third-party library, so there is nothing extra to install and nothing that can conflict with other plugins.

== Installation ==

1. Upload the `testimonials-manager` folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Testimonials → Add New** to create your first testimonial, or **Testimonials → Import** to bring in existing reviews from a spreadsheet.
4. Place `[testimonials]` on any page or post, or add the **Testimonials** block.

== Frequently Asked Questions ==

= Does this plugin require Composer or any external library? =

No. Everything — including the XLSX importer — runs using PHP's built-in extensions.

= Will importing a large spreadsheet time out my server? =

No. Rows are processed in batches of 100 via background AJAX requests with a live progress bar, so imports of thousands of rows are safe even on shared hosting with strict execution-time limits.

= What happens if I refresh the browser during an import? =

Nothing is lost or duplicated. The server tracks exactly how many rows have been processed; refreshing the page simply pauses the visible progress bar rather than restarting the import.

= Can I use this with Elementor? =

Yes. The `[testimonials]` shortcode works inside Elementor's Shortcode widget. The plugin does not require Elementor and has no dependency on any specific page builder.

= What happens to my testimonials if I deactivate or delete the plugin? =

Deactivating never deletes anything. Deleting the plugin also preserves your testimonials, categories, and settings unless you've explicitly checked "Delete all data on uninstall" under **Testimonials → Settings**.

= Is the customer's email address ever shown publicly? =

No. It's stored for your own records only and is never rendered on the front end.

== Screenshots ==

1. All Testimonials list table with customer, rating, category, and featured columns.
2. Add New Testimonial screen with customer details and rating meta boxes.
3. Import wizard — upload and preview step.
4. Import wizard — column mapping step.
5. Import wizard — live progress bar.
6. Front-end carousel layout.
7. Front-end grid layout with pagination.
8. Settings screen.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
