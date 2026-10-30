// ===== الأصوات =====
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function beep(freq, duration, type = 'sine', delay = 0) {
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = type;
    osc.frequency.value = freq;
    osc.connect(gain);
    gain.connect(audioCtx.destination);

    const start = audioCtx.currentTime + delay;
    gain.gain.setValueAtTime(0.15, start);
    gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
    osc.start(start);
    osc.stop(start + duration);
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

function playDraw() {
    beep(300, 0.3, 'sawtooth');
    beep(220, 0.4, 'sawtooth', 0.25);
}

function playChaos() {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    [200, 420, 160, 520].forEach((f, i) => beep(f, 0.12, 'sawtooth', i * 0.08));
}

// ===== عناصر الصفحة =====
const cells = document.querySelectorAll('.cell');
const turnLabel = document.getElementById('turnLabel');
const scoreLabel = document.getElementById('scoreLabel');
const overlay = document.getElementById('resultOverlay');
const resultEmoji = document.getElementById('resultEmoji');
const resultText = document.getElementById('resultText');
const nextRoundBtn = document.getElementById('nextRoundBtn');
const chaosToast = document.getElementById('chaosToast');
const resetBtn = document.getElementById('resetBtn');

let busy = false;
let pendingNextTurn = null; // الدور القادم بعد نهاية الجولة

cells.forEach(cell => {
    cell.addEventListener('click', () => handleClick(cell));
});

nextRoundBtn.addEventListener('click', () => {
    overlay.classList.remove('show');
    clearBoard();
    if (pendingNextTurn) {
        updateTurn(pendingNextTurn.name, pendingNextTurn.symbol);
    }
});

async function handleClick(btn) {
    if (busy) return;
    busy = true;

    const row = Number(btn.dataset.row);
    const col = Number(btn.dataset.col);

    try {
        const response = await fetch('api/move.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ row, col })
        });
        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            return;
        }

        btn.textContent = data.symbol;
        btn.classList.add(data.symbol.toLowerCase());
        btn.disabled = true;
        playClick(data.symbol);
        updateScores(data.scores);

        if (data.result === 'Continue') {
            updateTurn(data.nextName, data.nextTurn);
            applyChaos(data);
        } else {
            pendingNextTurn = { name: data.nextName, symbol: data.nextTurn };
            highlightWin(data.winLine);
            // ننتظر شوي عشان تشوفي التوهّج قبل النافذة
            setTimeout(() => showResult(data), 1300);
        }
    } catch (err) {
        alert('حصل خطأ بالاتصال بالسيرفر');
        console.error(err);
    } finally {
        busy = false;
    }
}

function showResult(data) {
    if (data.result === 'Draw') {
        resultEmoji.textContent = '🤝';
        resultText.textContent = 'تعادل!';
        playDraw();
    } else {
        resultEmoji.textContent = '🏆';
        resultText.textContent = 'فاز ' + data.winnerName + '!';
        playWin();
        fireConfetti();
    }
    overlay.classList.add('show');
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

function updateTurn(name, symbol) {
    turnLabel.textContent = 'دور ' + name + ' — ' + symbol;
    document.querySelectorAll('.player-card').forEach(card => {
        card.classList.toggle('active', card.dataset.symbol === symbol);
    });
}

function updateScores(s) {
    scoreLabel.textContent =
        s.player1 + ': ' + s.score1 + ' نقاط | ' +
        s.player2 + ': ' + s.score2 + ' نقاط | ' +
        'تعادل: ' + s.draws;
}

function clearBoard() {
    cells.forEach(cell => {
        cell.textContent = '';
        cell.classList.remove('x', 'o', 'win', 'locked', 'vanishing');
        cell.disabled = false;
    });
}

function highlightWin(winLine) {
    if (!winLine || winLine.length === 0) return;
    winLine.forEach(([r, c]) => {
        const cell = getCell(r, c);
        if (cell) cell.classList.add('win');
    });
}

// ===== وضع الفوضى =====
function getCell(r, c) {
    return document.querySelector('.cell[data-row="' + r + '"][data-col="' + c + '"]');
}

function showToast(msg) {
    chaosToast.textContent = msg;
    chaosToast.classList.remove('show');
    void chaosToast.offsetWidth; // يعيد تشغيل الأنيميشن
    chaosToast.classList.add('show');
}

// تحدّث الخانات المقفلة (الفاضية فقط)
function syncLocked(locked) {
    const set = new Set((locked || []).map(([r, c]) => r + '-' + c));
    cells.forEach(cell => {
        if (cell.classList.contains('x') || cell.classList.contains('o')) return;
        const isLocked = set.has(cell.dataset.row + '-' + cell.dataset.col);
        cell.classList.toggle('locked', isLocked);
        cell.disabled = isLocked;
        cell.textContent = isLocked ? '🔒' : '';
    });
}

function applyChaos(data) {
    if (data.event) {
        if (data.event.type === 'vanish') {
            const [r, c] = data.event.cell;
            const cell = getCell(r, c);
            if (cell) {
                cell.classList.add('vanishing');
                setTimeout(() => {
                    cell.textContent = '';
                    cell.classList.remove('x', 'o', 'vanishing');
                    cell.disabled = false;
                    syncLocked(data.locked);
                }, 600);
            }
        }
        showToast(data.event.message);
        playChaos();
    }
    syncLocked(data.locked);
}

// ===== تصفير النقاط =====
resetBtn.addEventListener('click', async () => {
    if (!confirm('متأكدة بدك تصفّري النقاط؟')) return;

    try {
        const response = await fetch('api/reset.php', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken }
        });
        const data = await response.json();

        if (!data.success) {
            alert(data.message);
            return;
        }

        clearBoard();
        updateScores(data.scores);
        updateTurn(data.nextName, 'X');
    } catch (err) {
        alert('حصل خطأ بالاتصال بالسيرفر');
        console.error(err);
    }
});