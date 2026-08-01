import { cssVarPx } from '../utils/css-vars';

export class HamburgerMenu {
    // Mirrors $bp-tablet from src/scss/abstracts/_tokens.scss via the --bp-tablet
    // custom property, so the mobile/desktop switch is defined in one place.
    private readonly mobileBreakpoint: number = cssVarPx('--bp-tablet', 800);
    private readonly stickyScrollThreshold: number = 240;
    private fixedMenu: HTMLElement;
    private hamburgers: NodeListOf<HTMLElement>;
    private navs: NodeListOf<HTMLElement>;
    private mainMenu: HTMLElement;

    constructor() {
        this.fixedMenu = document.querySelector('.menu.fixed') as HTMLElement;
        this.hamburgers = document.querySelectorAll('.hamburger');
        this.navs = document.querySelectorAll('.nav');
        this.mainMenu = document.querySelector('.site-header > .menu:not(.fixed)') as HTMLElement;

        if (!this.fixedMenu || !this.mainMenu) {
            return;
        }

        this.init();
    }

    private init(): void {
        // Hamburger toggle
        this.hamburgers.forEach((hamburger) => {
            hamburger.addEventListener('click', () => this.handleHamburgerClick(hamburger));
        });

        // Close menu on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeMenu();
            }
        });

        // Sticky menu on scroll
        window.addEventListener('scroll', () => this.handleScroll());

        // Mobile: menu always fixed
        this.applyMobileFixed();
        window.addEventListener('resize', () => this.applyMobileFixed());

        // Publish the sticky menu's height so anchors can clear it.
        this.publishMenuHeight();
        window.addEventListener('resize', () => this.publishMenuHeight());
    }

    /**
     * Writes the height of whichever menu is stuck to the top into
     * `--menu-height`, which `scroll-margin-top` reads so an anchor target does
     * not land underneath it.
     *
     * The height cannot be a constant in the stylesheet: the two menus differ
     * (the sticky one uses a smaller logo) and the mobile bar collapses to just
     * the hamburger. It also cannot be read from the sticky menu directly while
     * it is `display: none`, which it is until the page has scrolled 240px — a
     * hidden element measures 0. Hence the brief visibility swap below, which
     * costs one forced layout at startup.
     */
    private publishMenuHeight(): void {
        const isMobile = window.innerWidth <= this.mobileBreakpoint;
        const menu = isMobile ? this.mainMenu : this.fixedMenu;
        let height = menu.offsetHeight;

        if (height === 0) {
            const { display, visibility } = menu.style;
            menu.style.visibility = 'hidden';
            menu.style.display = 'block';
            height = menu.offsetHeight;
            menu.style.display = display;
            menu.style.visibility = visibility;
        }

        if (height > 0) {
            document.documentElement.style.setProperty('--menu-height', `${height}px`);
        }
    }

    private closeMenu(): void {
        const isOpen = this.navs[0]?.classList.contains('activ');
        if (!isOpen) return;

        this.hamburgers.forEach((h) => {
            h.classList.remove('is-active');
            h.setAttribute('aria-expanded', 'false');
        });
        this.navs.forEach((nav) => {
            nav.classList.remove('activ');
        });

        // Return focus to the active hamburger
        const visibleHamburger = Array.from(this.hamburgers).find(
            (h) => h.offsetParent !== null
        );
        visibleHamburger?.focus();
    }

    private handleHamburgerClick(hamburger: HTMLElement): void {
        const isActive = hamburger.classList.toggle('is-active');
        this.hamburgers.forEach((h) => {
            if (h !== hamburger) {
                h.classList.toggle('is-active', isActive);
            }
            h.setAttribute('aria-expanded', String(isActive));
        });

        this.navs.forEach((nav) => {
            nav.classList.toggle('activ', isActive);
        });
    }

    private handleScroll(): void {
        const shouldFix = window.scrollY >= this.stickyScrollThreshold && window.innerWidth > this.mobileBreakpoint;
        this.fixedMenu.classList.toggle('is-fixed', shouldFix);
        this.fixedMenu.setAttribute('aria-hidden', String(!shouldFix));

        // Toggle tabindex on sticky menu links
        this.fixedMenu.querySelectorAll('a, button').forEach((el) => {
            (el as HTMLElement).tabIndex = shouldFix ? 0 : -1;
        });
    }

    private applyMobileFixed(): void {
        if (window.innerWidth <= this.mobileBreakpoint) {
            this.mainMenu.classList.add('fix');
        } else {
            this.mainMenu.classList.remove('fix');
        }
    }
}
