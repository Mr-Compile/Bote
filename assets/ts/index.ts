import { selectElement, selectElements } from './shared.js';

let currentSlide = 0;
let carouselInterval: ReturnType<typeof setInterval> | null = null;

function initSmoothScroll(): void {
    selectElements<HTMLAnchorElement>('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', (event: MouseEvent) => {
            event.preventDefault();
            const href = anchor.getAttribute('href');
            if (!href) return;
            const target = document.querySelector<HTMLElement>(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

function initNavScroll(): void {
    const nav = document.querySelector<HTMLElement>('nav');
    if (!nav) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            nav.classList.add('shadow-lg');
        } else {
            nav.classList.remove('shadow-lg');
        }
    });
}

function initLogoutNotification(): void {
    const notification = selectElement<HTMLElement>('logout-notification');
    if (!notification) return;

    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    setTimeout(() => {
        notification.classList.add('translate-x-full');
    }, 4000);
}

function initMobileMenu(): void {
    const mobileMenuButton = document.querySelector<HTMLButtonElement>('.md\\:hidden button');
    const navContainer = document.querySelector<HTMLDivElement>('nav > div');
    if (!mobileMenuButton || !navContainer) return;

    const mobileMenu = document.createElement('div');
    mobileMenu.className = 'md:hidden bg-white border-t border-bote-stone-200 px-4 py-4 space-y-2';
    mobileMenu.innerHTML = `
        <a href="index.php" class="block text-bote-stone-700 hover:text-bote-green-700 font-medium py-2">Home</a>
        <a href="#about" class="block text-bote-stone-700 hover:text-bote-green-700 font-medium py-2">About Us</a>
        <a href="login.php" class="block bg-bote-green-700 text-white px-4 py-2 rounded-lg hover:bg-bote-green-800 transition-colors mt-2 text-center">Login</a>
    `;

    let mobileMenuOpen = false;
    mobileMenuButton.addEventListener('click', () => {
        if (!mobileMenuOpen) {
            navContainer.appendChild(mobileMenu);
            mobileMenuOpen = true;
        } else {
            mobileMenu.remove();
            mobileMenuOpen = false;
        }
    });
}

function showSlide(index: number): void {
    const slides = selectElements<HTMLDivElement>('.carousel-slide');
    const dots = selectElements<HTMLButtonElement>('.carousel-dot');
    if (slides.length === 0 || dots.length === 0) return;

    slides.forEach((slide) => slide.classList.remove('active'));
    dots.forEach((dot) => dot.classList.remove('active'));

    let nextIndex = index;
    if (nextIndex >= slides.length) {
        nextIndex = 0;
    } else if (nextIndex < 0) {
        nextIndex = slides.length - 1;
    }

    currentSlide = nextIndex;

    const activeSlide = slides[currentSlide];
    const activeDot = dots[currentSlide];
    if (!activeSlide || !activeDot) return;

    activeSlide.classList.add('active');
    activeDot.classList.add('active');
}

function nextSlide(): void {
    showSlide(currentSlide + 1);
    resetCarouselInterval();
}

function prevSlide(): void {
    showSlide(currentSlide - 1);
    resetCarouselInterval();
}

function goToSlide(index: number): void {
    showSlide(index);
    resetCarouselInterval();
}

function resetCarouselInterval(): void {
    if (carouselInterval !== null) {
        clearInterval(carouselInterval);
    }
    carouselInterval = setInterval(nextSlide, 5000);
}

function initCarousel(): void {
    const slides = selectElements<HTMLDivElement>('.carousel-slide');
    if (slides.length === 0) return;

    showSlide(0);
    resetCarouselInterval();

    const container = document.querySelector<HTMLDivElement>('.carousel-container');
    if (container) {
        container.addEventListener('mouseenter', () => {
            if (carouselInterval !== null) {
                clearInterval(carouselInterval);
            }
        });

        container.addEventListener('mouseleave', () => {
            resetCarouselInterval();
        });
    }

    window.nextSlide = nextSlide;
    window.prevSlide = prevSlide;
    window.goToSlide = goToSlide;
}

function initPage(): void {
    initSmoothScroll();
    initNavScroll();
    initLogoutNotification();
    initMobileMenu();
    initCarousel();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    initPage();
}

export {};
