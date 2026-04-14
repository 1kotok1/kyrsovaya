// =====================================================
//  Сервис бронирования — клиентский JavaScript
// =====================================================

'use strict';

// Авто-закрытие алертов через 6 сек
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 6000);
});

// Подсветка текущего времени в сетке
(function markCurrentTime() {
    const now   = new Date();
    const hh    = String(now.getHours()).padStart(2, '0');
    const mm    = now.getMinutes() < 30 ? '00' : '30';
    const slot  = `${hh}:${mm}`;
    document.querySelectorAll('.time-cell').forEach(cell => {
        if (cell.textContent.trim() === slot) {
            cell.closest('tr').style.outline = '2px solid #2563eb';
            cell.style.color = '#2563eb';
            cell.style.fontWeight = '700';
        }
    });
})();

// Быстрый перейти к дате из клавиатуры (Ctrl/Cmd + стрелки)
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
        const link = document.querySelector(e.key === 'ArrowLeft' ? 'a[href*="date="]:first-of-type' : 'a[href*="date="]:last-of-type');
        if (link) { e.preventDefault(); window.location.href = link.href; }
    }
});

// Установка минимального времени конца на основе начала
const startInput = document.getElementById('startTime');
const endInput   = document.getElementById('endTime');
if (startInput && endInput) {
    startInput.addEventListener('change', function() {
        const [h, m] = this.value.split(':').map(Number);
        const totalMins = h * 60 + m + 30;
        const endH = String(Math.floor(totalMins / 60)).padStart(2, '0');
        const endM = String(totalMins % 60).padStart(2, '0');
        endInput.min   = `${endH}:${endM}`;
        if (!endInput.value || endInput.value <= this.value) {
            endInput.value = `${endH}:${endM}`;
        }
    });
}
