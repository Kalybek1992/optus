
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logoutBtn');
    if (getCookie('AuthToken')) {
        logoutBtn.classList.remove('hidden');
    }
    logoutBtn.addEventListener('click', e => {
        e.preventDefault();
        document.cookie = 'AuthToken=; Max-Age=0; path=/; SameSite=Lax';
        location.reload();
    });
});