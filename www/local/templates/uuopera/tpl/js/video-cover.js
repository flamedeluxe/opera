document.addEventListener('DOMContentLoaded', function () {
    var sliderEl = document.querySelector('[data-event-slider]');

    function isPortrait() {
        return window.matchMedia('(orientation: portrait)').matches;
    }

    function getVideoSrc(video) {
        return isPortrait()
            ? (video.dataset.portrait || video.dataset.landscape)
            : video.dataset.landscape;
    }

    function applyVideoSrc(video) {
        var src = getVideoSrc(video);
        if (!src) return;
        if (video.getAttribute('src') !== src) {
            video.src = src;
            video.load();
        }
    }

    function playVideo(video) {
        if (!video) return;
        applyVideoSrc(video);
        var p = video.play();
        if (p && typeof p.catch === 'function') p.catch(function () {});
    }

    function pauseVideo(video) {
        if (!video) return;
        video.pause();
    }

    // Скрываем fallback-картинку когда видео начало воспроизводиться
    document.querySelectorAll('[data-video-cover]').forEach(function (cover) {
        var video = cover.querySelector('[data-video]');
        var img   = cover.querySelector('[data-image]');
        if (!video) return;

        applyVideoSrc(video);

        video.addEventListener('playing', function () {
            if (img) img.style.opacity = '0';
        });

        // Обновляем src при смене ориентации
        window.addEventListener('resize', function () {
            applyVideoSrc(video);
        });
    });

    // Если нет слайдера — просто запускаем все видео
    if (!sliderEl) {
        document.querySelectorAll('[data-video]').forEach(function (v) { playVideo(v); });
        return;
    }

    // Ждём, пока Swiper-инстанс появится на элементе (page-index.js инициализирует его раньше)
    function withSwiper(cb) {
        if (sliderEl.swiper) { cb(sliderEl.swiper); return; }
        var t = setInterval(function () {
            if (sliderEl.swiper) { clearInterval(t); cb(sliderEl.swiper); }
        }, 30);
        setTimeout(function () { clearInterval(t); }, 3000);
    }

    withSwiper(function (swiper) {
        var slides = sliderEl.querySelectorAll('[data-event-slider-slide]');

        // Запускаем видео активного слайда при инициализации
        var activeSlide = slides[swiper.realIndex];
        if (activeSlide) playVideo(activeSlide.querySelector('[data-video]'));

        swiper.on('slideChange', function () {
            slides.forEach(function (slide, idx) {
                var video = slide.querySelector('[data-video]');
                if (!video) return;
                if (idx === swiper.realIndex) {
                    playVideo(video);
                } else {
                    pauseVideo(video);
                }
            });
        });
    });
});
