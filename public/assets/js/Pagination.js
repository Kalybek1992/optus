// Получаем текущие параметры из URL
const urlParams = new URLSearchParams(window.location.search);

document.querySelectorAll('a[href^="?page"]').forEach(link => {

    const hrefParams = new URLSearchParams(link.getAttribute('href'));
    const newPage = hrefParams.get('page');

    const newParams = new URLSearchParams(urlParams.toString());
    newParams.set('page', newPage);

    // Обновляем href ссылки с сохранением остальных параметров и новым page
    link.setAttribute('href', '?' + newParams.toString());
});