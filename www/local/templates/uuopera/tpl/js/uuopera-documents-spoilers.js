(function () {
    document.querySelectorAll('.docs .su-spoiler-title').forEach(function (title) {
        title.addEventListener('click', function () {
            var root = title.closest('.su-spoiler');
            if (root) {
                root.classList.toggle('su-spoiler-closed');
            }
        });
        title.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                title.click();
            }
        });
    });
})();
