(function () {
    function setBviCookie(name, value) {
        var expires = new Date();
        expires.setTime(expires.getTime() + 86400000);
        document.cookie = 'bvi_' + name + '=' + encodeURIComponent(String(value))
            + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
    }

    function openBviPanel() {
        if (!window.Bvi || !window.Bvi._config) {
            return;
        }
        var cfg = window.Bvi._config;
        Object.keys(cfg).forEach(function (key) {
            setBviCookie(key, cfg[key]);
        });
        setBviCookie('panelActive', 'true');
        window.Bvi._init();
    }

    function bindBviOpenButtons() {
        document.querySelectorAll('.bvi-open').forEach(function (btn) {
            if (btn.getAttribute('type') !== 'button') {
                btn.setAttribute('type', 'button');
            }
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                openBviPanel();
            }, true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindBviOpenButtons);
    } else {
        bindBviOpenButtons();
    }
})();
