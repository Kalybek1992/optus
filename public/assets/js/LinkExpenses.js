const openErrorModal = (message) => {
    errorText.innerHTML = message;
    document.getElementById('errorModal').showModal();
};

const postData = async (url, data) => {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        return await response.json();
    } catch (error) {
        return 'error';
    }
};


const legalEntities = document.getElementById('legal_entities');
const comment = document.getElementById('comments');
const balance = document.getElementById('balance');

const commentsModal = document.getElementById('modal_comments');
const categoryModal = document.getElementById('modal_category');
const balanceModal = document.getElementById('modal_balance');
const innModal = document.getElementById('modal_inn');
const bankAccountModal = document.getElementById('modal_bank_account');

let legal_id;
let category_string;
let comments_string;

document.getElementById('save_expense').addEventListener('click', () => {

    legal_id = legalEntities.getAttribute('data-entity-le_id');
    category_string = document.getElementById('category')?.textContent;
    comments_string = comment.value;

    if (!category_string) {
        openErrorModal('Обязательно нужно выбрать категории!');
        return;
    }

    if (!comments_string) {
        openErrorModal('Укажите комментарий!');
        return;
    }


    categoryModal.innerHTML = category_string;
    commentsModal.innerHTML = comments_string;
    balanceModal.innerHTML = balance.innerHTML;
    innModal.innerHTML = legalEntities.getAttribute('data-entity-inn');
    bankAccountModal.innerHTML = legalEntities.getAttribute('data-entity-bank_account');


    document.getElementById('save_expense_modal').checked = true;
});

document.getElementById('confirmExpense').addEventListener('click', async () => {

    const result = await postData('/entities/addExpenses', {
        legal_id: legal_id,
        category: category_string,
        comment: comments_string,
    });

    document.getElementById('save_expense_modal').checked = false;

    if (result === 'error') {
        openErrorModal('Ошибка отправка данных!');
        return;
    }


    if (result.success) {
        document.getElementById('success_modal')?.showModal();
    } else {
        openErrorModal('Не удалось сохранить расход!');
    }

});

