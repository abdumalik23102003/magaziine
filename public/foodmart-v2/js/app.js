/**
 * FoodMart v2 - Main Application JS
 * Handles animations, interactions, and UX enhancements
 */

'use strict';

class FoodMart {
  constructor() {
    this.isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.isMobile = window.innerWidth < 768;
    
    this.init();
  }

  init() {
    this.setupCustomCursor();
    this.setupLenis();
    this.setupGSAP();
    this.initHeader();
    this.initMobileMenu();
    this.initThemeToggle();
    this.initScrollProgress();
    this.initBackToTop();
    this.initButtons();
    this.initParallax();
    this.bindEvents();
  }

  // Custom Cursor
  setupCustomCursor() {
    if (this.isMobile || this.isReducedMotion) return;

    const cursor = document.createElement('div');
    cursor.className = 'fm-cursor';
    document.body.appendChild(cursor);

    let mouseX = 0, mouseY = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;

      cursor.style.left = mouseX + 'px';
      cursor.style.top = mouseY + 'px';

      // Grow cursor on links/buttons
      const target = e.target;
      if (target.tagName === 'A' || target.tagName === 'BUTTON' || target.classList.contains('fm-btn')) {
        cursor.classList.add('fm-cursor-hover');
      } else {
        cursor.classList.remove('fm-cursor-hover');
      }
    });

    document.addEventListener('mouseleave', () => {
      cursor.style.display = 'none';
    });

    document.addEventListener('mouseenter', () => {
      cursor.style.display = 'block';
    });
  }

  setupLenis() {
    if (!this.isReducedMotion && typeof Lenis !== 'undefined') {
      const lenis = new Lenis({
        duration: 0.5,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        direction: 'vertical',
        gestureDirection: 'vertical',
        smooth: true,
        smoothTouch: false,
        touchMultiplier: 2,
      });

      function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
      }

      requestAnimationFrame(raf);
      this.lenis = lenis;
    }
  }

  setupGSAP() {
    if (typeof gsap === 'undefined') return;

    if (typeof ScrollTrigger !== 'undefined') {
      gsap.registerPlugin(ScrollTrigger);
    }

    this.revealOnScroll();
  }

  revealOnScroll() {
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

    const elements = document.querySelectorAll('[data-reveal]');
    elements.forEach((el) => {
      gsap.from(el, {
        scrollTrigger: {
          trigger: el,
          start: 'top 80%',
          end: 'top 50%',
          onEnter: () => el.classList.add('fm-fade-in'),
          once: true,
        },
        opacity: 0,
        y: 50,
        duration: 0.6,
      });
    });
  }

  initParallax() {
    if (this.isMobile || this.isReducedMotion) return;

    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    window.addEventListener('scroll', () => {
      parallaxElements.forEach((el) => {
        const speed = el.getAttribute('data-parallax') || 0.5;
        const yPos = window.scrollY * speed;
        el.style.transform = `translateY(${yPos}px)`;
      });
    }, { passive: true });
  }

  initHeader() {
    const header = document.querySelector('.fm-header');
    if (!header) return;

    const scrollHandler = () => {
      if (window.scrollY > 80) {
        header.classList.add('fm-header-scrolled');
      } else {
        header.classList.remove('fm-header-scrolled');
      }
    };

    window.addEventListener('scroll', scrollHandler, { passive: true });
  }

  initMobileMenu() {
    const toggleBtn = document.querySelector('.fm-menu-toggle');
    const mobileMenu = document.querySelector('.fm-mobile-menu');

    if (!toggleBtn || !mobileMenu) return;

    toggleBtn.addEventListener('click', () => {
      toggleBtn.classList.toggle('fm-active');
      mobileMenu.classList.toggle('fm-active');
    });

    mobileMenu.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        toggleBtn.classList.remove('fm-active');
        mobileMenu.classList.remove('fm-active');
      });
    });

    document.addEventListener('click', (e) => {
      if (!toggleBtn.contains(e.target) && !mobileMenu.contains(e.target)) {
        toggleBtn.classList.remove('fm-active');
        mobileMenu.classList.remove('fm-active');
      }
    });
  }

  initThemeToggle() {
    const themeToggle = document.querySelector('.fm-theme-toggle');
    if (!themeToggle) return;

    const isDark = localStorage.getItem('fm-theme') === 'dark' ||
                   window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (isDark) {
      document.documentElement.style.colorScheme = 'dark';
    }

    themeToggle.addEventListener('click', () => {
      const currentTheme = document.documentElement.style.colorScheme;
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

      document.documentElement.style.colorScheme = newTheme;
      localStorage.setItem('fm-theme', newTheme);

      themeToggle.setAttribute('aria-pressed', newTheme === 'dark');
    });
  }

  initScrollProgress() {
    const progressBar = document.querySelector('.fm-scroll-progress');
    if (!progressBar) return;

    const updateProgress = () => {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = (scrollTop / docHeight) * 100;

      progressBar.style.width = `${progress}%`;
    };

    window.addEventListener('scroll', updateProgress, { passive: true });
  }

  initBackToTop() {
    const button = document.querySelector('.fm-back-to-top');
    if (!button) return;

    const scrollHandler = () => {
      if (window.scrollY > 300) {
        button.classList.add('fm-visible');
      } else {
        button.classList.remove('fm-visible');
      }
    };

    button.addEventListener('click', () => {
      if (this.lenis) {
        this.lenis.scrollTo(0);
      } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });

    window.addEventListener('scroll', scrollHandler, { passive: true });
  }

  initButtons() {
    const buttons = document.querySelectorAll('.fm-btn');
    buttons.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        if (this.isReducedMotion) return;

        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const ripple = document.createElement('span');
        ripple.style.position = 'absolute';
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        ripple.style.width = '0';
        ripple.style.height = '0';
        ripple.style.borderRadius = '50%';
        ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
        ripple.style.pointerEvents = 'none';

        if (btn.style.position === '' || btn.style.position === 'static') {
          btn.style.position = 'relative';
        }

        btn.appendChild(ripple);

        if (typeof gsap !== 'undefined') {
          gsap.to(ripple, {
            width: 400,
            height: 400,
            opacity: 0,
            duration: 0.8,
            onComplete: () => ripple.remove(),
          });
        } else {
          ripple.remove();
        }
      });
    });

    if (!this.isMobile && !this.isReducedMotion) {
      this.initMagneticButtons();
    }
  }

  initMagneticButtons() {
    const buttons = document.querySelectorAll('[data-magnetic]');
    
    buttons.forEach((btn) => {
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;

        const distX = (e.clientX - centerX) * 0.3;
        const distY = (e.clientY - centerY) * 0.3;

        if (typeof gsap !== 'undefined') {
          gsap.to(btn, {
            x: distX,
            y: distY,
            duration: 0.4,
          });
        }
      });

      btn.addEventListener('mouseleave', () => {
        if (typeof gsap !== 'undefined') {
          gsap.to(btn, {
            x: 0,
            y: 0,
            duration: 0.4,
          });
        }
      });
    });
  }

  bindEvents() {
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        this.isMobile = window.innerWidth < 768;
      }, 250);
    });
  }

  // Utility: Animate number counting
  animateCounter(element, target, duration = 3000) {
    if (this.isReducedMotion || typeof gsap === 'undefined') {
      element.textContent = target;
      return;
    }

    gsap.to({ value: 0 }, {
      value: target,
      duration: duration / 1000,
      onUpdate: function () {
        element.textContent = Math.floor(this.targets()[0].value).toLocaleString();
      },
    });
  }

  // Utility: Show toast notification
  showToast(message, type = 'success', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `fm-toast fm-toast-${type}`;
    toast.innerHTML = `
      <span>${message}</span>
      <button class="fm-toast-close" aria-label="Close notification">×</button>
    `;

    document.body.appendChild(toast);

    const closeBtn = toast.querySelector('.fm-toast-close');
    closeBtn.addEventListener('click', () => toast.remove());

    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove();
      }
    }, duration);
  }

  // Utility: Add loading spinner
  setLoading(element, isLoading = true) {
    if (isLoading) {
      const spinner = document.createElement('div');
      spinner.className = 'fm-spinner';
      spinner.setAttribute('aria-label', 'Loading');
      element.appendChild(spinner);
      element.disabled = true;
    } else {
      const spinner = element.querySelector('.fm-spinner');
      if (spinner) spinner.remove();
      element.disabled = false;
    }
  }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  window.FoodMart = new FoodMart();
});

// Preload critical images
window.addEventListener('load', () => {
  const images = document.querySelectorAll('img[data-preload]');
  images.forEach((img) => {
    const src = img.getAttribute('src');
    if (src) {
      const preloadImg = new Image();
      preloadImg.src = src;
    }
  });
});
