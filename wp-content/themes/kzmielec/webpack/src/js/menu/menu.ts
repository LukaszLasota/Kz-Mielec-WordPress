export class HamburgerMenu {
    private readonly mobileBreakpoint: number = 800;
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
