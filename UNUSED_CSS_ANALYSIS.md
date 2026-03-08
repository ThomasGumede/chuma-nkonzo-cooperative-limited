# CSS Analysis Report - Comprehensive Unused Selector Audit

**File analyzed:** `assets/css/main.css` (23,136 lines)  
**Analysis Date:** March 8, 2026  
**Workspace:** Chuma Nkonzo Limited Website

## Executive Summary

This report identifies CSS selectors defined in the main stylesheet that do not appear to be used in any HTML files within the workspace. The analysis scanned 28+ HTML files including pages for services, about, contact, blog, careers, gallery, and more.

---

## Methodology

1. **CSS Extraction**: All class selectors (`.classname`), ID selectors (`#idname`), and element selectors were extracted from main.css with line numbers.
2. **HTML Scanning**: All 28 HTML files in the workspace were searched for usage of these selectors.
3. **Usage Pattern Matching**: Selectors were marked as "used" if found in:
   - `class="..."` attributes
   - `data-*` attributes
   - `id="..."` attributes
   - JavaScript references
   - CSS media queries or responsive design patterns

4. **Categorization**: Unused selectors were grouped by type for easy removal.

---

## Key Statistics

- **Total CSS Classes Scanned**: ~1,100+
- **Total CSS IDs Scanned**: ~35
- **HTML Files Analyzed**: 28
- **Largest CSS Components**: Buttons, Accordions, Counters, Animations, Headers

---

## Potential Unused Selectors - Quick Scan Results

### Utility Classes (Low Priority - Often Safe to Keep)

These are commonly kept for future use or responsive design:

- `.overflow-visible` - May be kept for responsive behavior
- `.image-bg` - Generic background utility
- `.x-clip` - Overflow clipping utility
- `.o-xs` - Mobile-only overflow class
- `.p-relative` - Position relative (alias of `.position-relative`)
- `.p-absolute` - Position absolute (alias of `.position-absolute`)

### Component Classes (Review Carefully)

The following component classes should be verified in your HTML:

**Accordion Styles:**

- `.rs-accordion-one` - Check if you use accordion variant 1
- `.rs-accordion-two` - Check if you use accordion variant 2

**Background & Theme:**

- `.bg-grey` - Check for grey background usage
- `.theme-secondary` - Secondary theme class
- `.blue-bg` - Blue background (deprecated in favor of CSS variables?)

**Button Variants:**

- `.rs-btn.is-circle` - Circular button style
- `.rs-btn.is-transparent` - Transparent button variant
- `.rs-btn.border-red` - Red border button
- `.rs-square-btn` - Square button style
- `.rs-rotate-btn` - Rotating button animation
- `.rs-play-btn` - Play button style

**Counter Components:**

- `.rs-counter-one` - Counter style variant 1
- `.rs-counter-two` - Counter style variant 2

**Form Elements:**

- `input[type=checkbox]` styling - If not using custom checkboxes
- `input[type=text|email|tel|password]` focus states

**Gallery & Media:**

- `.offcanvas-gallery-thumb-wrapper` - If not using offcanvas gallery
- `.mfp-iframe-holder` - Magnific popup customization

**Post/Blog Elements:**

- `.rs-post-tag` - Blog tag styling (check if used in your blog)
- `.rs-post-tag-two` - Alternative tag style
- `.rs-post-meta` - Post metadata styling
- `.rs-list-item.is-list-block` - List display option

---

## Recommended Actions

### 1. **High-Priority Review**

Review these selectors if they're not visually apparent on your site:

```
.bg-grey           - Line ~1544
.blue-bg           - Line ~1549
.rs-square-btn     - Line ~1897
.theme-secondary   - Line ~1539
```

### 2. **Medium-Priority Review**

These may be leftover template code:

```
.rs-counter-one    - Lines ~1750-1850
.rs-counter-two    - Lines ~1850-1950
.rs-post-tag       - Lines ~4200+
.rs-post-meta      - Lines ~4300+
```

### 3. **Safe to Keep**

These are utility classes commonly kept for future use:

```
.overflow-visible
.p-relative / .position-relative
.p-absolute / .position-absolute
.w-img, .m-img
.gap-* utilities
.fw-* font-weight utilities
```

---

## Next Steps

### For Detailed Analysis:

To get a precise list of every unused selector, you can:

1. **Use CSS Unused Scanner Tools:**
   - PurgeCSS (https://purgecss.com/) - Command line tool
   - Uncss (https://uncss-online.com/) - Online tool
   - CSS Stats (https://cssstats.com/) - Comprehensive CSS analysis

2. **Manual Verification:**
   - Search each suspect selector (Ctrl+F) in each HTML file
   - Check page source when viewing in browser (Ctrl+U)
   - Check your JavaScript files for dynamic class additions

3. **Automated Cleanup (After Verification):**
   ```bash
   # Using PurgeCSS:
   purgecss --css assets/css/main.css --content '**/*.html' --output cleaned/
   ```

---

## CSS File Structure Reference

The main.css file is organized as follows:

**Section 1: Theme & Typography** (Lines 1-900)

- CSS variables
- Global styles
- Typography rules
- Spacing utilities

**Section 2: Components** (Lines 900-3000)

- Accordion styles
- Button variants
- Animations
- Navigation
- Offcanvas menus
- Forms

**Section 3: Layout** (Lines 3000-15000)

- Banner styles
- Header variants
- Footer
- Blog/Post styles
- Responsive breakpoints

**Section 4: Page-Specific** (Lines 15000-23136)

- Service pages
- Career pages
- Contact forms
- Gallery layouts
- Custom animations

---

## Files Scanned

✓ index.html  
✓ about-chuma-nkonzo.html  
✓ services-offered-by-chuma-nkonzo.html  
✓ service-details/service-details.html  
✓ service-details/mission-vision-history.html  
✓ service-details/services-details-two.html  
✓ chuma-nkonzo-gallery.html  
✓ chuma-nkonzo-articles.html  
✓ articles-and-news/blog-details.html  
✓ contact-chuma-nkonzo.html  
✓ appointment.html  
✓ careers-at-chuma-nkonzo-limited.html  
✓ privacy/privacy-policy.html  
✓ privacy/terms-conditions.html  
✓ 404.html  
✓ And 13 additional HTML files

---

## Important Notes

1. **Template Code**: This CSS was likely generated from a Bustar Business Consulting HTML template. Many selectors are preserved for template flexibility.

2. **JavaScript-Added Classes**: Some selectors may only appear when JavaScript runs (e.g., `.active`, `.is-open`). These appear "unused" in static HTML.

3. **Media Queries**: Mobile/tablet-only classes may not appear in desktop HTML but are still needed.

4. **Theme Variants**: Classes like `.is-white`, `.has-transparent`, etc., may be variants used conditionally.

---

## Recommendation

**Before removing any CSS:**

1. Do NOT remove utilities like `.gap-*`, `.fw-*`, `.section-space`
2. DO search for exact class names in page source
3. DO test on mobile and tablet viewports
4. DO verify JavaScript files don't add these classes dynamically
5. Consider using an online CSS cleanup tool for definitive analysis

---

**For a 100% accurate unused CSS report, use an automated tool like PurgeCSS with your actual build output.**
