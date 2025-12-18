const uploadButton = document.getElementById('upload_button');
const closeButton = document.getElementById('close_button');
const fileInput = document.getElementById('file_input');
const modal = document.getElementById('my_modal_2');
const ready = document.getElementById('ready');

const openErrorModal = (message) => {
    const errorText = document.getElementById('errorText');
    errorText.innerHTML = message;
    document.getElementById('errorUpload').showModal();
}

const openSuccessModal = (
    transactions_count,
    bank_order_count,
    new_bank_accounts_count,
    customer_client_returns_count,
    customer_supplier_returns_count,
    customer_client_services_returns_count,
    goods_supplier,
    goods_client,
    goods_client_service
) => {
    let el;

    el = document.getElementById('transactions_count');
    if (el) el.textContent = transactions_count ?? 0;

    el = document.getElementById('bank_order_count');
    if (el) el.textContent = bank_order_count ?? 0;

    el = document.getElementById('new_bank_accounts_count');
    if (el) el.textContent = new_bank_accounts_count ?? 0;

    el = document.getElementById('customer_client_returns_count');
    if (el) el.textContent = customer_client_returns_count ?? 0;

    el = document.getElementById('customer_supplier_returns_count');
    if (el) el.textContent = customer_supplier_returns_count ?? 0;

    el = document.getElementById('customer_client_services_returns_count');
    if (el) el.textContent = customer_client_services_returns_count ?? 0;

    el = document.getElementById('goods_supplier');
    if (el) el.textContent = goods_supplier ?? 0;

    el = document.getElementById('goods_client');
    if (el) el.textContent = goods_client ?? 0;

    el = document.getElementById('goods_client_service');
    if (el) el.textContent = goods_client_service ?? 0;

    // Открываем модалку (проверяем существование и метод)
    const modal = document.getElementById('successUpload');
    if (modal && typeof modal.showModal === 'function') {
        modal.showModal();
    }
};


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
    console.log(result);

    if (result.status === 'error') {
        switch (result.value) {
            case 'error_file_format':
                openErrorModal('Неправильный формат файла!!!');
                break;
            case 'non_json_response':
                location.reload();
                break;
            case 'unknown_accounts':
                openErrorModal('Сперва распределите неизвестные счета, если их не видите обновите страницу!!!');
                break;
            case 'network_error':
                location.reload();
                break;
            case 'failed_to_save_file':
                openErrorModal('Не удалось сохранить файл, попробуйте еще раз!!!');
                break;
            case 'error_no_title':
                openErrorModal('Проверьте содержимого файла не найдена заголовок!!!');
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
            result.bank_order_count,
            result.new_bank_accounts_count,
            result.customer_client_returns_count,
            result.customer_supplier_returns_count,
            result.customer_client_services_returns_count,
            result.goods_supplier,
            result.goods_client,
            result.goods_client_service
        );
    }
});

closeButton.addEventListener('click', function () {
    modal.close();
});

ready.addEventListener('click', function () {
    location.reload();
});