document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Elementos del modal de nombre
    const btnChangeName = document.getElementById('btn-change-name');
    const nameModal = document.getElementById('name-modal');
    const cancelNameChange = document.getElementById('cancel-name-change');
    const confirmNameChange = document.getElementById('confirm-name-change');
    const newNameInput = document.getElementById('new-name');

    // Elementos del modal de contraseña
    const btnChangePassword = document.getElementById('btn-change-password');
    const passwordModal = document.getElementById('password-modal');
    const passwordForm = document.getElementById('password-change-form');
    const cancelPasswordChange = document.getElementById('cancel-password-change');
    const confirmPasswordChange = document.getElementById('confirm-password-change');
    const oldPasswordInput = document.getElementById('old-password');
    const newPasswordInput = document.getElementById('new-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const passwordFeedback = document.getElementById('password-feedback');

    // Modal de alerta
    const alertModal = document.getElementById('alert-modal');
    const alertTitle = document.getElementById('alert-title');
    const alertMessage = document.getElementById('alert-message');
    const alertClose = document.getElementById('alert-close');

    // Validación de contraseña en tiempo real usando el nuevo sistema
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        const validation = Validation.passwordStrength(password);

        if (password.length > 0) {
            if (validation.valid) {
                passwordFeedback.textContent = '✅ Contraseña válida';
                passwordFeedback.style.color = '#059669'; // verde
            } else {
                const checks = validation.checks;
                const passedChecks = Object.values(checks).filter(check => check).length;
                const totalChecks = Object.keys(checks).length;
                passwordFeedback.textContent = `${passedChecks}/${totalChecks} requisitos cumplidos`;
                passwordFeedback.style.color = '#dc2626'; // rojo
            }
        } else {
            passwordFeedback.textContent = '';
        }
    });

    // Modal de nombre
    btnChangeName.addEventListener('click', function() {
        newNameInput.value = '';
        newNameInput.focus();
        nameModal.style.display = 'flex';
    });

    cancelNameChange.addEventListener('click', function() {
        nameModal.style.display = 'none';
    });

    confirmNameChange.addEventListener('click', async function() {
        const newName = newNameInput.value.trim();

        if (!newName) {
            showAlert('Error', 'Por favor ingresa un nombre válido.');
            return;
        }

        try {
            const response = await fetch('index.php?action=update_name', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: newName,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('Éxito', 'Nombre de usuario actualizado correctamente.');
                nameModal.style.display = 'none';
                // Actualizar el campo de nombre actual
                document.getElementById('current-name').value = newName;
            } else {
                showAlert('Error', result.error || 'Error al actualizar el nombre.');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Error', 'Error de conexión. Inténtalo de nuevo.');
        }
    });

    // Modal de contraseña
    btnChangePassword.addEventListener('click', function() {
        oldPasswordInput.value = '';
        newPasswordInput.value = '';
        confirmPasswordInput.value = '';
        passwordFeedback.textContent = '';
        passwordModal.style.display = 'flex';
        oldPasswordInput.focus();
    });

    cancelPasswordChange.addEventListener('click', function() {
        passwordModal.style.display = 'none';
    });

    confirmPasswordChange.addEventListener('click', async function() {
        const oldPassword = oldPasswordInput.value;
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (!oldPassword || !newPassword || !confirmPassword) {
            showAlert('Error', 'Todos los campos son obligatorios.');
            return;
        }

        // Validar contraseña nueva usando el nuevo sistema
        const passwordValidation = Validation.passwordStrength(newPassword);
        if (!passwordValidation.valid) {
            showAlert('Error', 'La nueva contraseña no cumple con los requisitos mínimos.');
            return;
        }

        const passwordMatchValidation = Validation.passwordMatch(newPassword, confirmPassword);
        if (!passwordMatchValidation.valid) {
            showAlert('Error', passwordMatchValidation.message);
            return;
        }

        try {
            const response = await fetch('index.php?action=update_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();

            if (result.success) {
                showAlert('Éxito', 'Contraseña actualizada correctamente.');
                passwordModal.style.display = 'none';
            } else {
                showAlert('Error', result.error || 'Error al actualizar la contraseña.');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Error', 'Error de conexión. Inténtalo de nuevo.');
        }
    });

    // Función para mostrar alertas
    function showAlert(title, message) {
        alertTitle.textContent = title;
        alertMessage.textContent = message;
        alertModal.style.display = 'flex';
    }

    // Cerrar modal de alerta
    alertClose.addEventListener('click', function() {
        alertModal.style.display = 'none';
    });

    // Prevenir envío del formulario por defecto
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    // Cerrar modales solo al hacer click en el botón de cancelar o cerrar
    // Se eliminó el cierre al hacer click fuera para evitar pérdida accidental de datos
    // Los modales solo se pueden cerrar mediante los botones de cancelar/cerrar
});
