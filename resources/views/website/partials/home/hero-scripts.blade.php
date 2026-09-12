<script>
    document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
        const slides = Array.from(slider.querySelectorAll('[data-hero-slide]'));
        const dots = Array.from(slider.querySelectorAll('[data-hero-dot]'));
        let current = 0;

        const show = (index) => {
            current = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === current);
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === current);
            });
        };

        dots.forEach((dot) => {
            dot.addEventListener('click', () => show(Number(dot.dataset.heroDot)));
        });

        window.setInterval(() => show(current + 1), 6500);
    });
</script>
