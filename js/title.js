(function () {
    const board = document.getElementById('board');
    if (!board) return;

    // إذا المستخدم مفعّل "تقليل الحركة" بجهازه، ما نحرّك شي
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const MAX = 18;          // أقصى زاوية ميلان بالدرجات
    let targetX = 0, targetY = 0;
    let currentX = 0, currentY = 0;
    let active = false;      // هل المؤشر بيتحرك هلق؟

    function onMove(e) {
        const nx = (e.clientX / window.innerWidth - 0.5) * 2;   // من -1 لـ 1
        const ny = (e.clientY / window.innerHeight - 0.5) * 2;
        targetY = nx * MAX;
        targetX = -ny * MAX;
        active = true;
    }

    function onLeave() {
        active = false;
    }

    window.addEventListener('pointermove', onMove);
    document.documentElement.addEventListener('mouseleave', onLeave);
    window.addEventListener('touchend', onLeave);

    function loop(time) {
        if (!active) {
            // حركة هادية لما ما في تفاعل
            const t = time / 1000;
            targetY = Math.sin(t * 0.8) * 6;
            targetX = Math.cos(t * 0.6) * 5;
        }
        // انتقال ناعم للهدف
        currentX += (targetX - currentX) * 0.08;
        currentY += (targetY - currentY) * 0.08;
        board.style.transform =
            'rotateX(' + currentX.toFixed(2) + 'deg) rotateY(' + currentY.toFixed(2) + 'deg)';
        requestAnimationFrame(loop);
    }
    requestAnimationFrame(loop);
})();