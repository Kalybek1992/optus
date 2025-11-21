const modalCheckbox = document.getElementById('change_email');
const changeEmail = document.getElementById('change');
const errorText = document.getElementById('errorText');
const newEmailInput = document.getElementById('new_email');
const passwordModalCheckbox = document.getElementById('change_password');
const unlinkAccount = document.getElementById('unlink_account');
const passwordInput = document.getElementById('new_password');
const percentageInput = document.getElementById('new_percentage');
const deleteAccount = document.getElementById('delete_account');
const changePercentage = document.getElementById('change_percentage');
let currentUserId = null;
let clientId = null;
let leId = null;

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

const openErrorModal = (message) => {
    errorText.innerHTML = message;
    document.getElementById('errorEmail').showModal();
};

const closeModal = (checkbox, input) => {
    checkbox.checked = false;

    if (input){
        input.value = '';
    }
};

const isValidEmail = (email) => !!email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const isValidPassword = (password) => typeof password === 'string' && password.length >= 6;


document.querySelectorAll('label[for="change_email"]').forEach(button => {
    button.addEventListener('click', () => {
        currentUserId = button.dataset.entityUser_id;
        changeEmail.innerHTML = button.dataset.entity_email;
    });
});


document.getElementById('submitChangeEmail').addEventListener('click', async () => {
    const email = newEmailInput.value.trim();

    if (!isValidEmail(email)) {
        closeModal(modalCheckbox, newEmailInput);
        openErrorModal('Неправильный формат почты!');
        return;
    }

    const result = await postData('/user/changeEmail', {
        user_id: currentUserId,
        email: email
    });


    switch (result.value) {
        case 'repeat_email':
            closeModal(modalCheckbox, newEmailInput);
            openErrorModal('Такая почта уже существует!');
            return;
        case 'Invalid parameters':
            closeModal(modalCheckbox, newEmailInput);
            openErrorModal('Неверные параметры!');
            return;
    }


    if (result === 'error') {
        closeModal(modalCheckbox, newEmailInput);
        openErrorModal('Ошибка запроса!');
    }else {
        location.reload();
    }

});


document.getElementById('generatePassword')?.addEventListener('click', () => {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let password = "";
    for (let i = 0; i < 12; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    passwordInput.value = password;
});


document.querySelectorAll('label[for="change_password"]').forEach(button => {
    button.addEventListener('click', () => {
        currentUserId = button.dataset.entityUser_id;
    });
});

document.querySelectorAll('label[for="delete_account"]').forEach(button => {
    button.addEventListener('click', () => {
        currentUserId = button.dataset.entityUser_id;

        const email = button.dataset.email;
        const name = button.dataset.name;

        document.getElementById('deleteEmail').innerText = email;
        document.getElementById('deleteName').innerText = name;
    });
});


document.getElementById('submitChangePassword').addEventListener('click', async () => {
    const password = passwordInput.value.trim();

    if (!isValidPassword(password)) {
        closeModal(passwordModalCheckbox, passwordInput);
        openErrorModal('Пароль должен содержать минимум 6 символов!');
        return;
    }

    const result = await postData('/user/changePassword', {
        user_id: currentUserId,
        password: password
    });

    if (result === 'error') {
        closeModal(passwordModalCheckbox, passwordInput);
        openErrorModal('Ошибка при отправке запроса!');
    }

    if (result.success) {
        location.reload();
    } else {
        closeModal(passwordModalCheckbox, passwordInput);
        openErrorModal('Не удалось изменить пароль!');
    }
});


document.querySelectorAll('label[for="unlink_account"]').forEach(button => {
    button.addEventListener('click', () => {
        clientId = button.dataset.clientId;
        leId = button.dataset.leId;
        const email = button.dataset.email;
        const account = button.dataset.account;

        document.getElementById('unlinkEmail').innerText = email;
        document.getElementById('unlinkAccount').innerText = account;
    });
});

document.querySelectorAll('label[for="change_percentage"]').forEach(button => {
    button.addEventListener('click', () => {
        currentUserId = button.dataset.entityUser_id;
    });
});


document.getElementById('confirmUnlink').addEventListener('click', async () => {
    const result = await postData('/entities/unlinkAccount', {
        client_id: clientId,
        legal_id: leId
    });

    if (result.success) {
        location.reload();
    } else {
        closeModal(unlinkAccount, '');
        openErrorModal('Не удалось отвязать счёт!');
    }
});


document.getElementById('changePasswordСlose')?.addEventListener('click', () => {
    closeModal(passwordModalCheckbox, passwordInput);
});


document.getElementById('changeEmailClose')?.addEventListener('click', () => {
    closeModal(changeEmail, newEmailInput);
});



document.getElementById('confirmDeleteAccount').addEventListener('click', async () => {
    const result = await postData('/user/userDelete', {
        user_id: currentUserId
    });


    if (result.success) {
        location.reload();
    } else {
        closeModal(deleteAccount, false);
        openErrorModal('Не удалось удалить пользователя!');
    }
});


document.getElementById('confirmNewPercentage').addEventListener('click', async () => {

    let percentage = percentageInput.value;

    const result = await postData('/user/changePercentage', {
        user_id: currentUserId,
        percentage: percentage
    });

    if (isNaN(percentage) || percentage < 0 || percentage > 100) {
        closeModal(changePercentage, percentageInput);
        openErrorModal('Введите корректный процент от 0 до 100.');
    }

    if (result.success) {
        location.reload();
    } else {
        closeModal(changePercentage, percentageInput);
        openErrorModal('Не удалось изменить процент у пользователя!');
    }

});

