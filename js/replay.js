(function () {
    const data = JSON.parse(document.getElementById('replayData').textContent);
    const events = data.events || [];

    const cells = document.querySelectorAll('.cell');
    const info = document.getElementById('info');
    const toast = document.getElementById('toast');
    const playBtn = document.getElementById('playBtn');
    const restartBtn = document.getElementById('restartBtn');
    const speedBtn = document.getElementById('speedBtn');

    let idx = 0;
    let moveNo = 0;
    let locks = [];
    let timer = null;
    let finished = false;
    let fast = false;

    // ===== صوت بسيط =====
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    function beep(freq, dur, type, delay) {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = type || 'sine';
        osc.frequency.value = freq;
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        const start = audioCtx.currentTime + (delay || 0);
        gain.gain.setValueAtTime(0.15, start);
        gain.gain.exponentialRampToValueAtTime(0.001, start + dur);
        osc.start(start);
        osc.stop(start + dur);
    }

    // ===== أدوات =====
    function getCell(r, c) {
        return document.querySelector('.cell[data-row="' + r + '"][data-col="' + c + '"]');
    }

    function nameOf(symbol) {
        return symbol === 'X' ? data.p1 : data.p2;
    }

    function showToast(msg) {
        toast.textContent = msg;
        toast.classList.remove('show');
        void toast.offsetWidth;
        toast.classList.add('show');
    }

    function renderLocks() {
        const set = new Set(locks.map(l => l.r + '-' + l.c));
        cells.forEach(cell => {
            if (cell.classList.contains('x') || cell.classList.contains('o')) return;
            const isLocked = set.has(cell.dataset.row + '-' + cell.dataset.col);
            cell.classList.toggle('locked', isLocked);
            cell.textContent = isLocked ? '🔒' : '';
        });
    }

    // ===== تنفيذ حدث واحد =====
    function step() {
        if (idx >= events.length) {
            finish();
            return;
        }

        const ev = events[idx++];

        if (ev.t === 'move') {
            const cell = getCell(ev.r, ev.c);
            if (cell) {
                cell.classList.remove('locked');
                cell.textContent = ev.s;
                cell.classList.add(ev.s.toLowerCase());
            }
            moveNo++;
            info.textContent = 'حركة ' + moveNo + ': ' + nameOf(ev.s) + ' (' + ev.s + ')';
            beep(ev.s === 'X' ? 520 : 780, 0.12, 'square');

            // نفس ترتيب السيرفر: الأقفال القديمة بتنقص بعد كل حركة
            locks = locks
                .map(l => ({ r: l.r, c: l.c, ttl: l.ttl - 1 }))
                .filter(l => l.ttl > 0);
            renderLocks();

        } else if (ev.t === 'lock') {
            locks.push({ r: ev.r, c: ev.c, ttl: 2 });
            renderLocks();
            showToast('🔒 تم قفل خانة لحركتين!');
            [200, 420].forEach((f, i) => beep(f, 0.12, 'sawtooth', i * 0.08));

        } else if (ev.t === 'vanish') {
            const cell = getCell(ev.r, ev.c);
            if (cell) {
                cell.classList.add('vanishing');
                setTimeout(() => {
                    cell.textContent = '';
                    cell.classList.remove('x', 'o', 'vanishing');
                    renderLocks();
                }, 600);
            }
            showToast('💨 اختفى رمز من اللوح!');
            [200, 420, 160].forEach((f, i) => beep(f, 0.12, 'sawtooth', i * 0.08));

        } else if (ev.t === 'double') {
            showToast('⚡ دور مزدوج لـ ' + nameOf(ev.s) + '!');
            [200, 520].forEach((f, i) => beep(f, 0.12, 'sawtooth', i * 0.08));
        }

        if (idx >= events.length) {
            // نخلّي آخر حركة تبان لحظة قبل النتيجة
            stopTimer();
            setTimeout(finish, fast ? 500 : 900);
        }
    }

    function finish() {
        if (finished) return;
        finished = true;
        stopTimer();

        if (data.result === 'Draw') {
            info.textContent = '🤝 انتهت الجولة بالتعادل';
            beep(300, 0.3, 'sawtooth');
        } else {
            (data.winLine || []).forEach(function (pos) {
                const cell = getCell(pos[0], pos[1]);
                if (cell) cell.classList.add('win');
            });
            info.textContent = '🏆 فاز ' + data.winner + '!';
            [523, 659, 784, 1047].forEach((f, i) => beep(f, 0.25, 'triangle', i * 0.15));
        }
        playBtn.textContent = '▶ تشغيل';
    }

    // ===== التحكم =====
    function interval() {
        return fast ? 450 : 900;
    }

    function startTimer() {
        stopTimer();
        timer = setInterval(step, interval());
    }

    function stopTimer() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function resetBoard() {
        stopTimer();
        cells.forEach(cell => {
            cell.textContent = '';
            cell.classList.remove('x', 'o', 'win', 'locked', 'vanishing');
        });
        idx = 0;
        moveNo = 0;
        locks = [];
        finished = false;
        info.textContent = 'جاهزة للتشغيل';
        playBtn.textContent = '▶ تشغيل';
    }

    playBtn.addEventListener('click', function () {
        if (audioCtx.state === 'suspended') audioCtx.resume();

        if (timer) {                 // شغّالة؟ نوقفها
            stopTimer();
            playBtn.textContent = '▶ تشغيل';
            return;
        }
        if (finished) resetBoard();  // خلصت؟ نبدأ من أول
        playBtn.textContent = '⏸ إيقاف';
        step();
        if (idx < events.length) startTimer();
    });

    restartBtn.addEventListener('click', function () {
        resetBoard();
        playBtn.click();
    });

    speedBtn.addEventListener('click', function () {
        fast = !fast;
        speedBtn.textContent = fast ? '⏩ ×2' : '⏩ ×1';
        if (timer) startTimer();
    });
})();