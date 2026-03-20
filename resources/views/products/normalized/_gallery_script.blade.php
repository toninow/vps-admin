@push('scripts')
<script>
    if (!window.initNormalizedProductGalleries) {
        window.initNormalizedProductGalleries = function initNormalizedProductGalleries(root = document) {
            root.querySelectorAll('[data-gallery-root]').forEach((gallery) => {
                if (gallery.dataset.galleryBound === '1') {
                    return;
                }

                gallery.dataset.galleryBound = '1';

                const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
                const main = gallery.querySelector('[data-gallery-main]');
                const counter = gallery.querySelector('[data-gallery-counter]');
                const prev = gallery.querySelector('[data-gallery-prev]');
                const next = gallery.querySelector('[data-gallery-next]');
                const open = gallery.querySelector('[data-gallery-open]');

                const images = thumbs.map((thumb) => thumb.dataset.imageUrl).filter(Boolean);
                let currentIndex = 0;

                const render = () => {
                    if (!images.length || !main) {
                        return;
                    }

                    const current = images[currentIndex];
                    main.src = current;
                    main.alt = `Imagen ${currentIndex + 1}`;

                    if (counter) {
                        counter.textContent = `${currentIndex + 1} / ${images.length}`;
                    }

                    if (open) {
                        open.href = current;
                    }

                    thumbs.forEach((thumb, index) => {
                        const active = index === currentIndex;
                        thumb.classList.toggle('border-[#E6007E]', active);
                        thumb.classList.toggle('ring-2', active);
                        thumb.classList.toggle('ring-[#E6007E]/20', active);
                        thumb.classList.toggle('shadow-sm', active);
                    });
                };

                thumbs.forEach((thumb, index) => {
                    thumb.addEventListener('click', () => {
                        currentIndex = index;
                        render();
                    });
                });

                if (prev) {
                    prev.addEventListener('click', () => {
                        if (!images.length) return;
                        currentIndex = (currentIndex - 1 + images.length) % images.length;
                        render();
                    });
                }

                if (next) {
                    next.addEventListener('click', () => {
                        if (!images.length) return;
                        currentIndex = (currentIndex + 1) % images.length;
                        render();
                    });
                }

                render();
            });
        };

        document.addEventListener('DOMContentLoaded', () => {
            window.initNormalizedProductGalleries(document);
        });
    }
</script>
@endpush
