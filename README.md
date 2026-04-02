# Elementor CPT FAQ Accordion

CPT-powered FAQ accordion widget for Elementor that mirrors the core Accordion UI and outputs FAQ schema.

## Features
- Custom Post Type (`ecfw_faq`) for FAQs.
- Optional taxonomy support for organising FAQs when categorisation is required.
- Elementor widget that renders FAQs as an accordion.
- FAQPage JSON-LD schema output (one schema per widget instance).
- Styling controls similar to Elementor’s Accordion (title, content, icon, spacing, item border).
- Configurable responsive column layouts for the accordion display.
- Icon selection, position (left/right), and optional rotate-on-active animation.

## Requirements
- WordPress 5.8+ (recommended)
- Elementor (free) installed and active
- PHP 7.4+ (recommended)

## Installation
1. Upload the plugin folder to `wp-content/plugins/elementor-cpt-faq-widget/`.
2. Activate **Elementor CPT FAQ Accordion** in the WordPress admin.
3. Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

## Usage
1. Create FAQs under **FAQs** in the WordPress admin (title = question, body = answer).
2. If needed, register one or more taxonomies against the `ecfw_faq` post type to group or filter FAQs.
3. In Elementor, add **FAQ Accordion (CPT)** to your page.
4. Configure query, display, icon, and style settings, including column count and taxonomy-based filtering when available.

## CPT Details
- Post type key: `ecfw_faq`
- Archive slug: `faqs`
- Single slug: `faqs` (via rewrite)
- Supports: title, editor
- Taxonomies: none are bundled by default, but custom taxonomies can be assigned to `ecfw_faq` if required

## Schema Output
The widget outputs a single FAQPage JSON‑LD schema block per widget instance. Questions are taken from the FAQ post title, answers from the post body (text-only in schema).

## Widget Controls (Summary)
- Query: source, taxonomy/manual selection, count, order, orderby
- Display: open first, animation duration
- Icon: icon, active icon, position (left/right), rotate-on-active
- Style: title, content, icon, spacing, responsive columns, item gap, item border

## Development
- Main plugin file: `elementor-cpt-faq-widget.php`
- Widget class: `includes/widgets/class-faq-accordion.php`
- Frontend JS: `assets/js/faq-accordion.js`
- Styles: `assets/css/faq-accordion.css`

## License
GPL-2.0-or-later
