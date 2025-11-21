// ФУНКЦИЯ: показать лоудер
const showLoader = () => {
    const overlay = document.createElement('div');
    overlay.id = 'global_loader_overlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.background = 'rgba(0,0,0,0.3)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '999999';
    overlay.style.backdropFilter = 'blur(2px)';
    overlay.style.pointerEvents = 'all';

    overlay.innerHTML = `
        <span class="loading loading-ring loading-xl"></span>
    `;

    document.body.appendChild(overlay);
};

// ФУНКЦИЯ: скрыть лоудер
const hideLoader = () => {
    const overlay = document.getElementById('global_loader_overlay');
    if (overlay) overlay.remove();
};


// ОБНОВЛЕННЫЙ postData С ПРОВЕРКОЙ JSON
const postData = async (url, data) => {
    showLoader();

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const text = await response.text();

        // Пробуем распарсить JSON
        try {
            const json = JSON.parse(text);
            return json; // валидный JSON — возвращаем
        } catch (jsonError) {
            // Если это не JSON → значит ошибка PHP или HTML
            console.log('Server returned non-JSON response:', text);
            return 'error'; // Можно вернуть текст, если нужно
        }

    } catch (error) {
        console.log('Network error:', error);
        return 'error';

    } finally {
        hideLoader();
    }
};

const postDataRes = async (url, data) => {
    showLoader();

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const text = await response.text();

        // Пробуем распарсить JSON
        try {
            const json = JSON.parse(text);
            return json; // валидный JSON — возвращаем
        } catch (jsonError) {
            // Если это не JSON → значит ошибка PHP или HTML
            console.log('Server returned non-JSON response:', text);
            return 'error'; // Можно вернуть текст, если нужно
        }

    } catch (error) {
        console.log('Network error:', error);
        return 'error';

    } finally {
        hideLoader();
    }
};


const postDataFile = async (formData) => {
    showLoader();

    try {
        const response = await fetch('file/upload', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();

        // проверяем JSON
        try {
            const json = JSON.parse(text);
            return json; // валидный JSON
        } catch (e) {
            // ошибка: сервер вернул текст (PHP error, HTML и т.п.)
            console.log('Server returned non-JSON response:', text);
            return { status: 'error', value: 'non_json_response', raw: text };
        }

    } catch (error) {
        console.log('Network error:', error);
        return { status: 'error', value: 'network_error' };

    } finally {
        hideLoader();
    }
};
