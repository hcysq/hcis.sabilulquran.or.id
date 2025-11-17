# Accessibility Testing Procedures

The following checklist documents how this theme is validated against WCAG 2.1 AA requirements. QA can re-run these steps to confirm compliance.

## 1. Keyboard navigation
1. Load the site and press `Tab` once to confirm the "Lewati ke konten utama" skip link becomes visible and moves focus to `#main-content` when activated.
2. Continue pressing `Tab` to traverse header navigation, sidebar menus, pagination links, and publication filters. Focus outlines should be clearly visible thanks to the shared focus style in `style.css`.
3. Open any form (search form, dashboard forms) and verify each input is reachable and announced with its associated `<label>` element.

## 2. Screen reader landmarks and roles
1. Confirm that `<header role="banner">`, `<main id="main-content">`, sidebar `<aside role="complementary">`, and footer landmarks are exposed in the rotor.
2. Trigger empty-state messages (e.g., visit `/publikasi/` with no posts or perform an empty search) to ensure the polite `role="status"` regions announce updates without stealing focus.

## 3. Color contrast validation
Run the palette audit script to confirm the shared colors meet AA (≥ 4.5:1) contrast requirements:

```bash
python tools/contrast_check.py
```

The script prints the ratio for each foreground/background pair defined in `PALETTE`; all rows should display `PASS`. Update `tools/contrast_check.py` whenever new theme colors are introduced.

## 4. Additional manual checks
- Use the browser's responsive design mode to ensure focus states remain visible on mobile breakpoints.
- Validate marquee announcements and alerts expose `aria-live` regions so assistive technologies hear status changes.
- When adding new templates, prefer semantic HTML5 elements (`<article>`, `<section>`, `<nav>`) and link every interactive control to either a `<label>` or `aria-label`.

Document any deviations or follow-up issues directly in the project's task tracker so regressions can be triaged quickly.
