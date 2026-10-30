(function () {
    var themes = ['neon', 'space', 'forest'];
    var icons  = { neon: '🌃', space: '🚀', forest: '🌲' };

    // نقرأ الثيم المحفوظ (مع حماية إذا التخزين مش متاح)
    var saved = 'neon';
    try {
        var s = localStorage.getItem('theme');
        if (themes.indexOf(s) !== -1) saved = s;
    } catch (e) {}

    // نطبّق الثيم فوراً قبل ما الصفحة تنرسم (عشان ما يصير وميض)
    document.documentElement.setAttribute('data-theme', saved);

    function applyTheme(name) {
        document.documentElement.setAttribute('data-theme', name);
        try { localStorage.setItem('theme', name); } catch (e) {}

        document.querySelectorAll('.theme-switcher button').forEach(function (b) {
            b.classList.toggle('active', b.dataset.theme === name);
        });

        document.body.classList.add('switching');
        setTimeout(function () { document.body.classList.remove('switching'); }, 500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var box = document.createElement('div');
        box.className = 'theme-switcher';

        themes.forEach(function (t) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.theme = t;
            btn.textContent = icons[t];
            btn.title = t;
            if (t === saved) btn.classList.add('active');
            btn.addEventListener('click', function () { applyTheme(t); });
            box.appendChild(btn);
        });

        document.body.appendChild(box);
    });
})();