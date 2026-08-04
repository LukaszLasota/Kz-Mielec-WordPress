type TextSize = 'normal' | 'large' | 'xlarge';

/**
 * Wires the accessibility bar to the two data attributes on <html>.
 *
 * The attributes are the single source of truth, and they are already set when
 * this runs: the inline script in `RegisterAssets` applies the stored settings
 * in the head so the page never paints at the wrong size first. This class
 * therefore reads the current state off the document rather than out of
 * storage, and writes to storage only when the visitor actually changes it.
 */
export class AccessibilityBar {
    private static readonly SIZE_KEY = 'kzmielec-a11y-size';
    private static readonly CONTRAST_KEY = 'kzmielec-a11y-contrast';
    private static readonly SIZES: readonly TextSize[] = ['normal', 'large', 'xlarge'];

    private readonly root: HTMLElement = document.documentElement;
    private readonly sizeButtons: HTMLButtonElement[];
    private readonly contrastButton: HTMLButtonElement | null;

    constructor() {
        const bar = document.querySelector('.a11y-bar');

        // Queries are scoped to the bar, never to the document: <html> carries
        // these very attributes as state, so a document-wide query would return
        // the root element alongside the buttons and treat it as one of them.
        this.sizeButtons = bar
            ? Array.from(bar.querySelectorAll<HTMLButtonElement>('[data-a11y-size]'))
            : [];
        this.contrastButton =
            bar?.querySelector<HTMLButtonElement>('[data-a11y-contrast]') ?? null;

        if (!bar) {
            return;
        }

        this.syncPressedState();

        this.sizeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                this.applySize(this.readSize(button.dataset.a11ySize));
            });
        });

        this.contrastButton?.addEventListener('click', () => {
            this.applyContrast(this.root.getAttribute('data-a11y-contrast') !== 'on');
        });
    }

    /**
     * Narrows an arbitrary string to one of the three steps.
     *
     * Anything unrecognised — a stale attribute, a tampered storage entry —
     * falls back to the default rather than reaching the DOM, so the attribute
     * value is always one of three known literals.
     */
    private readSize(value: string | null | undefined): TextSize {
        return AccessibilityBar.SIZES.includes(value as TextSize)
            ? (value as TextSize)
            : 'normal';
    }

    /**
     * Brings `aria-pressed` in line with the attributes the head script set.
     *
     * The markup ships with the default step pressed, which is wrong for a
     * returning visitor whose setting was restored before this ran — the
     * buttons would announce a state the page is not in.
     */
    private syncPressedState(): void {
        const current = this.readSize(this.root.getAttribute('data-a11y-size'));

        this.sizeButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.a11ySize === current));
        });

        this.contrastButton?.setAttribute(
            'aria-pressed',
            String(this.root.getAttribute('data-a11y-contrast') === 'on')
        );
    }

    private applySize(size: TextSize): void {
        if (size === 'normal') {
            this.root.removeAttribute('data-a11y-size');
        } else {
            this.root.setAttribute('data-a11y-size', size);
        }

        this.sizeButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.a11ySize === size));
        });

        this.store(AccessibilityBar.SIZE_KEY, size);

        // The sticky menu is taller once the text grows, and `--menu-height` —
        // which anchor targets clear — is measured by HamburgerMenu on resize.
        // Changing a font size fires no resize event, so without this an anchor
        // jump would land under the bar at any setting but the default.
        window.dispatchEvent(new Event('resize'));
    }

    private applyContrast(enabled: boolean): void {
        if (enabled) {
            this.root.setAttribute('data-a11y-contrast', 'on');
        } else {
            this.root.removeAttribute('data-a11y-contrast');
        }

        this.contrastButton?.setAttribute('aria-pressed', String(enabled));
        this.store(AccessibilityBar.CONTRAST_KEY, enabled ? 'on' : 'off');
    }

    /**
     * Persists a setting, tolerating a browser that refuses storage.
     *
     * Writing to `localStorage` throws outright in Safari's private mode and
     * wherever storage is blocked by policy. The setting is worth losing on the
     * next page load; the bar is not worth breaking over.
     */
    private store(key: string, value: string): void {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage unavailable — the setting simply does not persist.
        }
    }
}
