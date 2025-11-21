document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
        const dropdown = toggle.closest('.dropdown');
        const content = dropdown.querySelector('.dropdown-content');

        if (!content) return;

        document.querySelectorAll('.dropdown-content').forEach(otherContent => {
            if (otherContent !== content) {
                otherContent.classList.add('hidden');
            }
        });

        content.classList.toggle('hidden');
    });
});

function userChoiceSelector() {

    document.querySelectorAll('.list_user li').forEach(item =>  {
        // Проверка, если уже назначен обработчик — пропускаем
        if (item.dataset.bound === 'true') return;

        item.addEventListener('click', async (e) => {
            e.preventDefault();

            const email = item.dataset.email;
            const entityId = item.dataset.entityId;

            const dropdown = item.closest('.dropdown');
            const selectedTextEl = dropdown.querySelector('.selected-user-text');
            const content = dropdown.querySelector('.dropdown-content');

            setChangeMail(email, selectedTextEl)

            if (content) {
                content.classList.add('hidden');
            }

            const result = await postData('/user/bindAccount', {
                email: email,
                entity_id: entityId,
            });

            setChangeValue(result, entityId)

        });

        item.dataset.bound = 'true';
    });
}

function setChangeValue(answer, entityId) {
    const percent = document.getElementById(entityId + '-percent')
    const balance = document.getElementById(entityId + '-balance')

    if (answer === 'error'){
        showCornerModal('Ошибка выбора пользователя')
    }

    if (answer.success) {
        showCornerModal('Пользователь успешно выбран')

        balance.textContent = 'Баланс: ' + answer.balance.replace(/,/g, '');

        if (answer.percent) {
            percent.textContent = 'Процент: ' + answer.percent + '%';
            percent.classList.remove('hidden');
        }else {
            percent.classList.add('hidden');
        }
    }

    if (answer.error) {
        showCornerModal('Ошибка выбора пользователя')
    }

}

function setChangeMail(email, selectedTextEl) {

    if (email === 'on_account' && selectedTextEl) {
        selectedTextEl.textContent = 'Наш счет!!!';
    }

    if (email === 'cancellation' && selectedTextEl) {
        selectedTextEl.textContent = 'Выберите пользователя';
    }

    if (selectedTextEl && email !== 'on_account' && email !== 'cancellation') {
        selectedTextEl.textContent = email;
    }
}

function showCornerModal(message) {
    const checkbox = document.getElementById('cornerModal');
    const txtModal = document.getElementById('txtModal');
    checkbox.checked = true;
    txtModal.innerHTML= message;


    setTimeout(() => {
        checkbox.checked = false;
    }, 5000);
}

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

userChoiceSelector()

// Статусы загрузки для каждой сущности
const dropdownStates = new Map();

document.querySelectorAll('.dropdown-content').forEach((dropdown, index) => {
    const userList = dropdown.querySelector('.list_user');
    const entityId = userList.querySelector('li')?.dataset.entityId || dropdown.dataset.entityId || '';

    // Инициализация состояния для текущего dropdown
    dropdownStates.set(entityId, {
        currentPage: 1,
        loading: false,
        allLoaded: false,
        dropdown,
        userList
    });

    // Назначить слушатель скролла
    dropdown.addEventListener('scroll', () => handleScroll(entityId));
});

function handleScroll(entityId) {
    const state = dropdownStates.get(entityId);
    if (!state || state.loading || state.allLoaded) return;

    const {dropdown} = state;
    const nearBottom = dropdown.scrollTop + dropdown.clientHeight >= dropdown.scrollHeight - 5;

    if (nearBottom) {
        loadNextPage(entityId);
    }
}

async function loadNextPage(entityId) {
    const state = dropdownStates.get(entityId);
    if (!state) return;

    state.loading = true;
    state.currentPage++;

    try {
        const response = await fetch(`/user/getUsersLinking?page=${state.currentPage}`);
        const data = await response.json();

        if (data.status === 'ok') {
            if (data.success === 'reached_the_end') {
                state.allLoaded = true;
                return;
            }

            if (Array.isArray(data.users)) {
                appendUsers(state.userList, entityId, data.users);
            }
        }
    } catch (err) {
        console.error('Ошибка при загрузке пользователей:', err);
    }

    state.loading = false;
}

function appendUsers(userList, entityId, users) {
    const template = document.getElementById('user-item-template');

    users.forEach(user => {
        const clone = template.content.cloneNode(true);
        const li = clone.querySelector('li');
        const emailSpan = clone.querySelector('.email');
        const roleSpan = clone.querySelector('.role');
        const roleTranslations = {
            admin: 'Администратор',
            client: 'Клиент',
            supplier: 'Поставщик',
            courier: 'Курьер'
        };


        li.dataset.email = user.email;
        li.dataset.entityId = entityId;

        emailSpan.textContent = user.email;
        roleSpan.textContent = roleTranslations[user.role];

        // Классы бейджа по роли
        roleSpan.className = 'badge ml-2 capitalize role';
        if (user.role === 'admin') {
            roleSpan.classList.add('badge-primary');
        } else if (user.role === 'supplier') {
            roleSpan.classList.add('badge-secondary');
        } else {
            roleSpan.classList.add('badge-neutral');
        }


        userList.appendChild(clone);
        userChoiceSelector()
    });
}