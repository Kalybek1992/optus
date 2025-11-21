
let categoriesParent = 'not_parent';
let updateCategoryApp = null;


document.addEventListener('alpine:init', () => {
    updateCategoryApp = (path) => {
        const el = document.querySelector('#categoryApp');
        if (!el) return console.error('categoryApp not found');

        const data = Alpine.$data ? Alpine.$data(el) : Alpine.raw(el);
        if (!data) return console.error('Alpine data not found');

        data.open = true;
        data.level = Array.isArray(path) ? path.length : 0;
        data.path = Array.isArray(path) ? path : [];
    };
});

window.addEventListener('load', () => {
    if (sessionStorage.getItem('runUpdateAfterReload') === 'true') {
        const target = sessionStorage.getItem('updateCategoryAppTarget');
        const parsed = target ? JSON.parse(target) : null;

        if (typeof updateCategoryApp === 'function') {
            updateCategoryApp(parsed);
        }

        sessionStorage.removeItem('runUpdateAfterReload');
        sessionStorage.removeItem('updateCategoryAppTarget');
    }
});


const openErrorModal = (message) => {
    errorText.innerHTML = message;
    document.getElementById('errorModal').showModal();
}

function delCategories(text) {
    let displayText = document.getElementById("category");
    categoriesParent = text;

    if (displayText) {
        let categoryTxt = displayText.innerHTML.trim();

        if (!categoryTxt.endsWith(categoriesParent)) {
            categoryTxt += " > " + categoriesParent;
        }

        document.getElementById('modal_category').innerHTML = categoryTxt;
    } else {
        document.getElementById('modal_category').innerHTML = categoriesParent;
    }

    document.getElementById('delete_category').checked = true;
}

function addCategoriesAtLevel(text) {

    let displayText = document.getElementById("category");
    let pathArray;

    if (typeof text === 'string' && text.startsWith('[')) {
        pathArray = JSON.parse(text);
    } else {
        pathArray = Array.isArray(text) ? text : [text]; // даже если просто строка, обернём в массив
    }

    categoriesParent = Array.isArray(pathArray) && pathArray.length > 0 ?
        pathArray[pathArray.length - 1] : 'not_parent';

    if (displayText) {

        let categoryTxt = displayText.innerHTML.trim();

        if (!categoryTxt.endsWith(categoriesParent)) {
            categoryTxt += " > " + categoriesParent;
        }

        document.getElementById("category_parent").innerHTML = categoryTxt;
    } else {
        document.getElementById("category_parent").innerHTML = '';
    }


    document.getElementById('add_categories').checked = true;
}


document.getElementById('confirm_category').addEventListener('click', async () => {

    const newCategories = document.getElementById("new_categories").value;

    let input = document.getElementById('category_parent').innerHTML;
    const decoded = input.replace(/&gt;/g, '>');
    const parts = decoded.split('>').map(part => part.trim());

    // --- Проверяем categoriesParent ---
    if (Array.isArray(categoriesParent)) {
        categoriesParent = 'not_parent';
    } else if (
        typeof categoriesParent === 'object' &&
        categoriesParent !== null &&
        Object.keys(categoriesParent).length === 1 &&
        categoriesParent[0] === 0
    ) {
        categoriesParent = 'not_parent';
    }

    if (!newCategories) {
        document.getElementById('add_categories').checked = false;
        openErrorModal('Укажите название категории!');
        return;
    }

    const result = await postData('/categories/addCategory', {
        parent_category: categoriesParent,
        new_category: newCategories,
    });

    if (result === 'error') {
        openErrorModal('Ошибка отправка данных!');
        return;
    }

    if (result.success) {
        const isEmpty = parts.length === 0 || parts.every(item => item.trim() === '');

        if (!isEmpty) {
            sessionStorage.setItem('runUpdateAfterReload', 'true');
            sessionStorage.setItem('updateCategoryAppTarget', JSON.stringify(parts));
        }
        location.reload();
    } else {
        openErrorModal('Не удалось сохранить категории!');
    }

    document.getElementById('add_categories').checked = false;
});



document.getElementById('confirm_delete_category').addEventListener('click', async () => {

    const input = document.getElementById('modal_category').innerHTML;
    const decoded = input.replace(/&gt;/g, '>');
    const parts = decoded.split('>').map(part => part.trim());
    parts.pop();

    if (parts.length >= 1){
        parts.pop();
    }

    const result = await postData('/categories/delCategory', {
        category: categoriesParent,
    });

    if (!result.success) {
        openErrorModal('Не удалось удалить категории!')
    } else {
        sessionStorage.setItem('runUpdateAfterReload', 'true');
        sessionStorage.setItem('updateCategoryAppTarget', JSON.stringify(parts));
        location.reload();
    }

    document.getElementById('delete_category').checked = false;
});

