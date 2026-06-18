document.addEventListener('DOMContentLoaded', () => {
    // Theme Switcher Initialization
    const initTheme = () => {
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', storedTheme);
        updateThemeToggleIcon(storedTheme);
    };

    const toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeToggleIcon(newTheme);
    };

    const updateThemeToggleIcon = (theme) => {
        const themeBtn = document.getElementById('theme-toggle-btn');
        if (themeBtn) {
            if (theme === 'dark') {
                themeBtn.innerHTML = '<i class="bi bi-sun-fill"></i>';
                themeBtn.classList.remove('btn-dark');
                themeBtn.classList.add('btn-light');
            } else {
                themeBtn.innerHTML = '<i class="bi bi-moon-stars-fill"></i>';
                themeBtn.classList.remove('btn-light');
                themeBtn.classList.add('btn-dark');
            }
        }
    };

    // Attach click handler to theme button if it exists
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
        themeBtn.addEventListener('click', toggleTheme);
    }

    // Run theme checker
    initTheme();

    // Fade-in animations trigger
    const animatedElements = document.querySelectorAll('.animate-fade-in-up');
    animatedElements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
});
