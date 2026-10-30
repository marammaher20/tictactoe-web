(function () {
    const cfg = JSON.parse(document.getElementById('roomCfg').textContent);
    const $ = id => document.getElementById(id);

    const cells = document.querySelectorAll('.cell');
    const waitingBox = $('waitingBox');
    const gameBox = $('gameBox');
    const turnLabel = $('turnLabel');
    const scoreLabel = $('scoreLabel');
    const overlay = $('resultOverlay');
    const toast = $('toast');
    const cards = { X: $('cardX'), O: $('cardO') };

    let version = -1;
    let prevBoard = null;
    let busy = false;
    let shownFor = -1;
    let last = null;

    // ===== الأصوات =====
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

    const themeSounds = {
        neon:   { wave: 'square',   x: 520, o: 780 },
        space:  { wave: 'sine',     x: 330, o: 495 },
        forest: { wave: 'triangle', x: 440, o: 660 }
    };

    function playClick(symbol) {
        if (audioCtx.state === 'suspended') audioCtx.resume();
        const theme = document.documentElement.dataset.theme || 'neon';
        const s = themeSounds[theme] || themeSounds.neon;
        beep(symbol === 'X' ? s.x : s.o, 0.12, s.wave);
    }

    function playWin() {
        [523, 659, 784, 1047].forEach((f, i) => beep(f, 0.25, 'triangle', i * 0.15));
    }

    function playLose() {
        beep(300, 0.3, 'sawtooth');
        beep(220, 0.4, 'sawtooth', 0.25);
    }

    // ===== أدوات =====
    function showMsg(msg) {
        toast.textContent = msg;
        toast.classList.remove('show');
        void toast.offsetWidth;
        toast.classList.add('show');
    }

    function fireConfetti() {
        const end = Date.now() + 2500;
        const colors = ['#ff2bd6', '#00f0ff', '#ffffff', '#7a2cff'];
        (function frame() {
            confetti({ particleCount: 6, angle: 60,  spread: 60, origin: { x: 0 }, colors: colors, zIndex: 200 });
            confetti({ particleCount: 6, angle: 120, spread: 60, origin: { x: 1 }, colors: colors, zIndex: 200 });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    // ===== عرض الحالة =====
    function render(s) {
        last = s;
        version = s.version;

        if (s.status === 'waiting') {
            waitingBox.classList.remove('d-none');
            gameBox.classList.add('d-none');
            return;
        }
        waitingBox.classList.add('d-none');
        gameBox.classList.remove('d-none');

        // كروت اللاعبين
        ['X', 'O'].forEach(sym => {
            const p = s.players[sym];
            const card = cards[sym];
            card.querySelector('.avatar').textContent = p.avatar || '';
            card.querySelector('.pname').textContent = (p.name || '') + (s.me === sym ? ' (إنتِ)' : '');
            card.classList.toggle('active', s.status === 'playing' && s.turn === sym);
        });

        // سطر الدور
        if (s.status === 'playing') {
            turnLabel.textContent = (s.turn === s.me)
                ? '🟢 دورك! (' + s.me + ')'
                : '⏳ دور ' + s.players[s.turn].name + ' (' + s.turn + ')';
        } else {
            turnLabel.textContent = 'انتهت الجولة';
        }

        // النقاط
        scoreLabel.textContent =
            s.players.X.name + ': ' + s.scores.X + ' | ' +
            s.players.O.name + ': ' + s.scores.O + ' | ' +
            'تعادل: ' + s.scores.draws;

        // اللوح
        const winSet = new Set(s.winLine);
        cells.forEach((cell, i) => {
            const sym = s.board[i];
            const current = cell.dataset.sym || '-';

            if (sym !== current) {
                cell.dataset.sym = sym;
                cell.textContent = (sym === '-') ? '' : sym;
                cell.classList.remove('x', 'o', 'win');
                if (sym !== '-') {
                    cell.classList.add(sym.toLowerCase());
                    if (prevBoard) playClick(sym); // صوت لحركتك وحركة الخصم
                }
            }

            cell.classList.toggle('win', winSet.has(i));
            cell.disabled = !(s.status === 'playing' && s.turn === s.me && sym === '-');
        });
        prevBoard = s.board;

        // نافذة النتيجة
        if (s.status === 'finished') {
            if (shownFor !== s.version) {
                shownFor = s.version;
                setTimeout(() => {
                    if (last && last.status === 'finished') showResult(last);
                }, 1100);
            }
        } else {
            overlay.classList.remove('show');
        }
    }

    function showResult(s) {
        const emoji = $('resultEmoji');
        const text = $('resultText');

        if (s.result === 'Draw') {
            emoji.textContent = '🤝';
            text.textContent = 'تعادل!';
            playLose();
        } else if (s.result === s.me) {
            emoji.textContent = '🏆';
            text.textContent = 'مبروك! فزتِ 🎉';
            playWin();
            fireConfetti();
        } else {
            emoji.textContent = '😅';
            text.textContent = 'فاز ' + s.players[s.result].name + '!';
            playLose();
        }
        overlay.classList.add('show');
    }

    // ===== إرسال الأوامر للسيرفر =====
    async function send(payload) {
        if (busy) return;
        busy = true;
        try {
            const res = await fetch('api/room_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': cfg.csrf
                },
                body: JSON.stringify(Object.assign({ code: cfg.code }, payload))
            });
            const s = await res.json();
            if (!s.success) {
                showMsg(s.message);
                return;
            }
            render(s);
        } catch (err) {
            showMsg('حصل خطأ بالاتصال بالسيرفر');
            console.error(err);
        } finally {
            busy = false;
        }
    }

    cells.forEach(cell => {
        cell.addEventListener('click', () => {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            send({ action: 'move', cell: Number(cell.dataset.i) });
        });
    });

    $('nextRoundBtn').addEventListener('click', () => send({ action: 'next' }));

    $('copyBtn').addEventListener('click', () => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(cfg.code).then(() => showMsg('تم نسخ الكود ✅'));
        } else {
            showMsg('انسخي الكود يدوياً: ' + cfg.code);
        }
    });

    // ===== السؤال كل ثانية (Polling) =====
    async function poll() {
        let delay = 1000;
        try {
            const res = await fetch(
                'api/room_state.php?code=' + cfg.code + '&v=' + version,
                { cache: 'no-store' }
            );
            const s = await res.json();
            if (!s.success) {
                showMsg(s.message);
                delay = 3000;
            } else if (s.changed) {
                render(s);
            }
        } catch (err) {
            delay = 3000;
        }
        setTimeout(poll, delay);
    }

    poll();
})();