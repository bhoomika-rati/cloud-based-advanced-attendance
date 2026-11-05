(function () {
    var menuToggle = document.querySelector('.landing-menu-toggle');
    var menu = document.getElementById('landing-menu');

    if (menuToggle && menu) {
        menuToggle.addEventListener('click', function () {
            var isOpen = menu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        });

        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                menu.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Open navigation menu');
            });
        });
    }

    var revealItems = document.querySelectorAll('.landing-reveal');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries, currentObserver) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('is-pending');
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach(function (item) {
            item.classList.add('is-pending');
            observer.observe(item);
        });
    }
})();
