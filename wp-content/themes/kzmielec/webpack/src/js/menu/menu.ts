export class HamburgerMenu {
    private hamburger: HTMLElement;
    private siteNavNavigation: HTMLElement;
    private siteNavContainer: HTMLElement;
    private siteNav: HTMLElement;
    private scrollPos: number;
    private readonly desktopWidth: number = 1025;

    constructor() {
        this.hamburger = this.getElement('.hamburger');
        this.siteNavNavigation = this.getElement('.site-nav__navigation');
        this.siteNavContainer = this.getElement('.site-header__container');
        this.siteNav = this.getElement('.site-nav');
        this.scrollPos = window.scrollY;

        this.init();
    }

    private getElement(selector: string): HTMLElement {
        const element = document.querySelector(selector);
        if (!element) {
            throw new Error(`Element not found: ${selector}`);
        }
        return element as HTMLElement;
    }

    private init(): void {
        this.hamburger.addEventListener('click', this.handleClick.bind(this));
        window.addEventListener('scroll', this.handleScroll.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
        window.addEventListener('orientationchange', this.handleOrientationChange.bind(this));
        this.updateMenuBackground();
        this.updateNavigationPosition();
    }

    private handleClick(): void {
        this.hamburger.classList.toggle('hamburger--active');
        const expanded = this.hamburger.classList.contains('hamburger--active');
        this.hamburger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        this.siteNavNavigation.setAttribute('aria-hidden', expanded ? 'false' : 'true');
        const isActive = this.siteNavNavigation.classList.toggle('site-nav__navigation--active');
        this.siteNavContainer.classList.toggle('site-header__container--active');

        if (this.scrollPos < 10) {
            this.siteNav.classList.add('site-nav--background');
        }

        this.updateNavigationPosition(isActive);
    }

    private addClassOnScroll(): void {
        this.siteNav.classList.add('site-nav--background');
    }

    private removeClassOnScroll(): void {
        this.siteNav.classList.remove('site-nav--background');
    }

    private handleScroll(): void {
        if (window.innerWidth >= this.desktopWidth) {
            this.updateMenuBackground();
        }
    }

    private handleResize(): void {
        this.updateNavigationPosition();
        if (window.innerWidth >= this.desktopWidth) {
            this.resetMenu();
        }
    }

    private handleOrientationChange(): void {
        this.resetMenu();
    }

    private updateMenuBackground(): void {
        this.scrollPos = window.scrollY;
        if (this.scrollPos > 10) {
            this.addClassOnScroll();
        } else {
            this.removeClassOnScroll();
        }
    }

    private updateNavigationPosition(isActive: boolean = false): void {
        const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        if (width < this.desktopWidth) {
            const navHeight = this.siteNav.offsetHeight;
            this.siteNavNavigation.style.top = `${navHeight}px`;

            if (isActive) {
                this.siteNavNavigation.style.height = `calc(100vh - ${navHeight}px)`;
            } else {
                this.siteNavNavigation.style.removeProperty('height');
            }
        } else {
            this.siteNavNavigation.style.removeProperty('height');
            this.siteNavNavigation.style.removeProperty('top');
        }
    }

    private resetMenu(): void {
        this.hamburger.classList.remove('hamburger--active');
        this.hamburger.setAttribute('aria-expanded', 'false');
        this.siteNavNavigation.setAttribute('aria-hidden', 'true');
        this.siteNavNavigation.classList.remove('site-nav__navigation--active');
        this.siteNavContainer.classList.remove('site-header__container--active');
    }
}
