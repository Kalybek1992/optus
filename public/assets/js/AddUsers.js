const postData = async (url, data) => {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        return await response.json();
    } catch (error) {
        return 'error';
    }
};

document.getElementById('generatePassword').addEventListener('click', function () {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let password = "";
    for (let i = 0; i < 12; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.getElementById('password').value = password;
});

document.getElementById('copyPassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('password');
    passwordInput.select();
    passwordInput.setSelectionRange(0, 99999);
    document.execCommand("copy");
});


document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');

    roleSelect.addEventListener('change', function () {
        const firstOption = roleSelect.querySelector('option[value=""]');
        if (firstOption) {
            firstOption.remove(); // Удаляем "Выберите роль"
        }
    });
});

document.getElementById('role').addEventListener('change', function () {
    const percent = document.getElementById('percent');
    if (this.value === 'client') {
        percent.classList.remove('hidden');
    } else {
        percent.classList.add('hidden');
    }
});

const form = document.getElementById('addUserForm');

form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;
    const percent = parseFloat(document.getElementById('percentage').value) || 0;

    const result = await postData('/user/addUserRole', {
        name: name,
        email: email,
        role: role,
        password: password,
        percent: percent
    });

    if (result.status === 'error' && result.value === 'repeat_email') {
        document.getElementById('error_email').showModal();
        return;
    }

    if (result.status === 'error') {
        document.getElementById('error').showModal();
        return;
    }

    if (result === 'error') {
        document.getElementById('error').showModal();
        return;
    }

    document.getElementById('success').showModal();

    const roleTranslations = {
        admin: 'Администратор',
        client: 'Клиент',
        supplier: 'Поставщик',
        courier: 'Курьер'
    };

    // Заполняем данные в модалке
    document.getElementById('displayName').textContent = name;
    document.getElementById('displayRole').textContent = roleTranslations[role];
    document.getElementById('displayEmail').textContent = email;
    document.getElementById('displayPassword').textContent = password;


    // Обработчики для кнопок копирования
    document.getElementById('copyEmail').addEventListener('click', function () {
        const email = document.getElementById('displayEmail').textContent;
        navigator.clipboard.writeText(email);
    });

    document.getElementById('copyPassword').addEventListener('click', function () {
        const password = document.getElementById('displayPassword').textContent;
        navigator.clipboard.writeText(password);
    });

    form.reset();

});