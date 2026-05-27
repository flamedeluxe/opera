(function () {
    var btnAttr = 'data-pagination-load-next';
    var gridAttr = 'list-paginated';
    var wrapAttr = 'data-pagination';

    document.querySelectorAll('[' + btnAttr + ']').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var key = btn.getAttribute(btnAttr);
            var href = btn.getAttribute('href');
            var grid = key ? document.querySelector('[' + gridAttr + '="' + key + '"]') : null;
            if (!href || !grid) {
                return;
            }

            btn.classList.add('loading');

            fetch(href, { method: 'GET', headers: { Accept: 'text/html' } })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var srcGrid = doc.querySelector('[' + gridAttr + '="' + key + '"]');
                    if (!srcGrid) {
                        return;
                    }

                    Array.prototype.forEach.call(srcGrid.children, function (child) {
                        grid.appendChild(child.cloneNode(true));
                    });

                    var nextBtn = doc.querySelector('[' + btnAttr + '="' + key + '"]');
                    var nextHref = nextBtn ? nextBtn.getAttribute('href') : '';
                    var wrap = btn.closest('[' + wrapAttr + '="' + key + '"]');

                    if (nextHref) {
                        btn.setAttribute('href', nextHref);
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({}, '', href);
                        }
                    } else if (wrap) {
                        wrap.remove();
                    } else {
                        btn.remove();
                    }
                })
                .catch(function () {})
                .finally(function () {
                    btn.classList.remove('loading');
                });
        }, true);
    });
})();
