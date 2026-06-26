document.addEventListener('DOMContentLoaded', () => {
    const userModal = document.getElementById('user-modal');
    const userModalForm = document.getElementById('user-modal-form');
    const userModalTitle = document.getElementById('user-modal-title');
    const userModalId = document.getElementById('user-modal-id');
    const userModalMethod = document.getElementById('user-modal-method');
    const userModalName = document.getElementById('user-modal-name');
    const userModalEmail = document.getElementById('user-modal-email');
    const userModalPassword = document.getElementById('user-modal-password');
    const userModalPasswordConfirmation = document.getElementById('user-modal-password-confirmation');
    const editButtons = document.querySelectorAll('.js-user-edit');
    const closeButtons = document.querySelectorAll('.js-user-modal-close');

    if (!userModal || !userModalForm || !userModalTitle || !userModalId || !userModalMethod) {
        return;
    }

    const openUserModal = () => {
        userModal.classList.add('is-open');
        userModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeUserModal = () => {
        userModal.classList.remove('is-open');
        userModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const setValue = (field, value) => {
        if (field) {
            field.value = value ?? '';
        }
    };

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const user = JSON.parse(button.dataset.user || '{}');

            userModalTitle.textContent = 'Editar usuario';
            userModalForm.action = `/configuracion/usuarios/${user.id}`;
            userModalMethod.value = 'PUT';
            userModalId.value = user.id || '';
            setValue(userModalName, user.name);
            setValue(userModalEmail, user.email);
            setValue(userModalPassword, '');
            setValue(userModalPasswordConfirmation, '');

            openUserModal();
        });
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeUserModal));

    userModal.addEventListener('click', (event) => {
        if (event.target === userModal) {
            closeUserModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && userModal.classList.contains('is-open')) {
            closeUserModal();
        }
    });

    if (userModal.dataset.open === 'true') {
        openUserModal();
    }
});