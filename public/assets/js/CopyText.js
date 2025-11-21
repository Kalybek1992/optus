
function copyText(text) {
    // копируем текст в буфер
    navigator.clipboard.writeText(text).then(() => {
        // показываем уведомление
        showNotification(text);
    }).catch(err => {
        console.error('Ошибка копирования:', err);
    });
}

function showNotification(text) {
    // если уведомление уже есть — удаляем, чтобы не дублировалось
    const existing = document.getElementById('dynamic-notification');
    if (existing) existing.remove();

    // создаём контейнер
    const container = document.createElement('div');
    container.id = 'dynamic-notification';
    container.style.position = 'fixed';
    container.style.bottom = '20px';
    container.style.right = '20px';
    container.style.maxWidth = '300px';
    container.style.padding = '15px';
    container.style.background = 'white';
    container.style.border = '1px solid #ccc';
    container.style.borderRadius = '10px';
    container.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    container.style.fontFamily = 'sans-serif';
    container.style.zIndex = '9999';
    container.style.opacity = '0';
    container.style.transition = 'opacity 0.3s ease';

    // содержимое
    container.innerHTML = `
        <div style="display:flex; align-items:flex-start; gap:10px;">
            <svg style="width:24px; height:24px; color:#3b82f6;" fill="none" stroke="currentColor" stroke-width="1.5"
                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022 23.85 23.85 0 0 0 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0 3 3 0 1 0 5.714 0ZM3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5"/>
            </svg>
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:600; color:#111;">Уведомление</h3>
                <p style="margin:5px 0 0; font-size:14px; color:#555;">Текст скопирован: <strong>${text}</strong></p>
            </div>
        </div>
    `;

    document.body.appendChild(container);

    // плавно показываем
    requestAnimationFrame(() => {
        container.style.opacity = '1';
    });

    // через 5 секунд скрываем и удаляем
    setTimeout(() => {
        container.style.opacity = '0';
        container.addEventListener('transitionend', () => {
            container.remove();
        });
    }, 5000);
}
