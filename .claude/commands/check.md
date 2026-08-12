---
description: Verifies accessibility, i18n and security
---

Check WCAG 2.1 AA compliance, and go beyond it where that costs nothing and does
not disturb the design.

Check that every visitor-facing string is translatable (i18n) -- this site runs in
four languages, so an untranslated string is a visible defect, not a nicety.

Check that output is escaped and input validated: XSS and injection.

Code comments in English.

Code must satisfy the configured PHPStan level and PHPCS standard.
