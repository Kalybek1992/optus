const uploadButton = document.getElementById('upload_button');
const closeButton = document.getElementById('close_button');
const fileInput = document.getElementById('file_input');
const modal = document.getElementById('my_modal_2');
const ready = document.getElementById('ready');

const openErrorModal = (message) => {
    errorText.innerHTML = message;
    document.getElementById('errorUpload').showModal();
}

const openSuccessModal = (transactions, newAccounts, updatedAccounts) => {
    document.getElementById('transactionsCount').textContent = transactions;
    document.getElementById('newAccountsCount').textContent = newAccounts;
    document.getElementById('updatedAccountsCount').textContent = updatedAccounts;

    document.getElementById('successUpload').showModal();
}


uploadButton.addEventListener('click', async function () {
    const file = fileInput.files[0];
    modal.close();

    if (!file) {
        openErrorModal('Пожалуйста, выберите файл.');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    const result = await postDataFile(formData);


    if (result.status === 'error') {
        switch (result.value) {
            case 'error_file_format':
                openErrorModal('Неправильный формат файла!!!');
                break;
            case 'failed_to_save_file':
                openErrorModal('Не удалось сохранить файл, попробуйте еще раз!!!');
                break;
            case 'no_extracts_files':
                openErrorModal('Не нашли транзакции!!!');
                break;
            case 'some_error':
                openErrorModal('Какая-то ошибка!!!');
                break;
            case 'processed_payments':
                openErrorModal('Эти счета уже обработаны!!!');
                break;
        }
    }

    if (result.status === 'ok' && result.success === 'ok') {
        openSuccessModal(
            result.transactions_count,
            result.new_bank_accounts_count,
            result.bank_accounts_updated
        )
    }
});

closeButton.addEventListener('click', function () {
    modal.close();
});

ready.addEventListener('click', function () {
    location.reload();
});