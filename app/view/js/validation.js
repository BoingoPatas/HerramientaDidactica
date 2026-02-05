/**
 * validation.js - Sistema centralizado de validaciones para el frontend
 *
 * Este archivo contiene funciones de validación reutilizables para todos los formularios
 * de la aplicación, proporcionando validación en tiempo real y consistencia en la interfaz.
 */

class Validation {
    /**
     * Validar un campo requerido
     * @param {string} value - Valor del campo
     * @param {string} fieldName - Nombre del campo (para mensajes)
     * @returns {object} - { valid: boolean, message: string }
     */
    static required(value, fieldName) {
        if (value === null || value === undefined || value === '') {
            return {
                valid: false,
                message: `El campo ${fieldName} es obligatorio.`
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar longitud mínima
     * @param {string} value - Valor del campo
     * @param {number} minLength - Longitud mínima requerida
     * @param {string} fieldName - Nombre del campo
     * @returns {object} - { valid: boolean, message: string }
     */
    static minLength(value, minLength, fieldName) {
        if (value.length < minLength) {
            return {
                valid: false,
                message: `El campo ${fieldName} debe tener al menos ${minLength} caracteres.`
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar longitud máxima
     * @param {string} value - Valor del campo
     * @param {number} maxLength - Longitud máxima permitida
     * @param {string} fieldName - Nombre del campo
     * @returns {object} - { valid: boolean, message: string }
     */
    static maxLength(value, maxLength, fieldName) {
        if (value.length > maxLength) {
            return {
                valid: false,
                message: `El campo ${fieldName} no puede tener más de ${maxLength} caracteres.`
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar formato de correo electrónico
     * @param {string} email - Correo electrónico
     * @returns {object} - { valid: boolean, message: string }
     */
    static email(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            return {
                valid: false,
                message: 'El correo electrónico no es válido.'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar formato de correo electrónico en tiempo real
     * @param {string} email - Correo electrónico
     * @returns {boolean} - true si es válido, false si no lo es
     */
    static isValidEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validar longitud mínima en tiempo real
     * @param {string} value - Valor del campo
     * @param {number} minLength - Longitud mínima requerida
     * @returns {boolean} - true si es válido, false si no lo es
     */
    static hasMinLength(value, minLength) {
        return value.length >= minLength;
    }

    /**
     * Validar formato de nombre de usuario
     * @param {string} username - Nombre de usuario
     * @returns {object} - { valid: boolean, message: string }
     */
    static usernameFormat(username) {
        const usernameRegex = /^[A-Za-z0-9_\-.]{3,32}$/;
        if (!usernameRegex.test(username)) {
            return {
                valid: false,
                message: 'El nombre de usuario debe tener entre 3 y 32 caracteres y solo puede contener letras, números, guion, guion bajo y punto.'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar fuerza de contraseña
     * @param {string} password - Contraseña
     * @returns {object} - { valid: boolean, message: string, checks: object }
     */
    static passwordStrength(password) {
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)
        };

        const allPassed = Object.values(checks).every(check => check);

        if (!allPassed) {
            return {
                valid: false,
                message: 'La contraseña debe tener al menos 8 caracteres, incluyendo mayúscula, minúscula, número y carácter especial.',
                checks: checks
            };
        }

        return {
            valid: true,
            message: '',
            checks: checks
        };
    }

    /**
     * Validar que dos contraseñas coincidan
     * @param {string} password - Contraseña
     * @param {string} confirmPassword - Confirmación de contraseña
     * @returns {object} - { valid: boolean, message: string }
     */
    static passwordMatch(password, confirmPassword) {
        if (password !== confirmPassword) {
            return {
                valid: false,
                message: 'Las contraseñas no coinciden.'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar formato de cédula
     * @param {string} cedula - Cédula
     * @returns {object} - { valid: boolean, message: string }
     */
    static cedulaFormat(cedula) {
        if (!cedula || cedula.trim() === '') {
            return {
                valid: false,
                message: 'La cédula es obligatoria.'
            };
        }
        if (!/^\d+$/.test(cedula)) {
            return {
                valid: false,
                message: 'La cédula solo debe contener números.'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar que solo se ingresen números (para validación en tiempo real)
     * @param {string} value - Valor del campo
     * @returns {boolean} - true si es válido, false si contiene letras
     */
    static isNumericOnly(value) {
        return /^\d*$/.test(value);
    }

    /**
     * Validar formato de sección
     * @param {string} section - Sección
     * @returns {object} - { valid: boolean, message: string }
     */
    static sectionFormat(section) {
        const sectionRegex = /^[0-9]+(,[0-9]+)*$/;
        if (section && !sectionRegex.test(section)) {
            return {
                valid: false,
                message: 'La sección debe contener solo números separados por comas (ej: 22,23).'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar formato de slug
     * @param {string} slug - Slug
     * @returns {object} - { valid: boolean, message: string }
     */
    static slugFormat(slug) {
        const slugRegex = /^[a-z0-9\-]+$/;
        if (slug && !slugRegex.test(slug)) {
            return {
                valid: false,
                message: 'El slug solo puede contener letras minúsculas, números y guiones.'
            };
        }
        return { valid: true, message: '' };
    }

    /**
     * Validar formato de URL
     * @param {string} url - URL
     * @returns {object} - { valid: boolean, message: string }
     */
    static urlFormat(url) {
        try {
            new URL(url);
            return { valid: true, message: '' };
        } catch (e) {
            return {
                valid: false,
                message: 'La URL no es válida.'
            };
        }
    }

    /**
     * Validar datos de registro
     * @param {string} username - Nombre de usuario
     * @param {string} email - Correo electrónico
     * @param {string} password - Contraseña
     * @param {string} confirmPassword - Confirmación de contraseña
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateRegistration(username, email, password, confirmPassword) {
        const errors = {};

        // Validar nombre de usuario
        const usernameValidation = this.required(username, 'nombre de usuario');
        if (!usernameValidation.valid) {
            errors.username = usernameValidation.message;
        } else {
            const usernameFormatValidation = this.usernameFormat(username);
            if (!usernameFormatValidation.valid) {
                errors.username = usernameFormatValidation.message;
            }
        }

        // Validar correo electrónico
        const emailValidation = this.required(email, 'correo electrónico');
        if (!emailValidation.valid) {
            errors.email = emailValidation.message;
        } else {
            const emailFormatValidation = this.email(email);
            if (!emailFormatValidation.valid) {
                errors.email = emailFormatValidation.message;
            }
        }

        // Validar contraseña
        const passwordValidation = this.required(password, 'contraseña');
        if (!passwordValidation.valid) {
            errors.password = passwordValidation.message;
        } else {
            const passwordStrengthValidation = this.passwordStrength(password);
            if (!passwordStrengthValidation.valid) {
                errors.password = passwordStrengthValidation.message;
            }
        }

        // Validar confirmación de contraseña
        if (confirmPassword) {
            const passwordMatchValidation = this.passwordMatch(password, confirmPassword);
            if (!passwordMatchValidation.valid) {
                errors.confirmPassword = passwordMatchValidation.message;
            }
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Validar datos de inicio de sesión
     * @param {string} username - Nombre de usuario
     * @param {string} password - Contraseña
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateLogin(username, password) {
        const errors = {};

        const usernameValidation = this.required(username, 'nombre de usuario');
        if (!usernameValidation.valid) {
            errors.username = usernameValidation.message;
        } else if (username.length > 64) {
            errors.username = 'El nombre de usuario es demasiado largo.';
        }

        const passwordValidation = this.required(password, 'contraseña');
        if (!passwordValidation.valid) {
            errors.password = passwordValidation.message;
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Validar datos de usuario/docente
     * @param {string} cedula - Cédula
     * @param {string} email - Correo electrónico (opcional)
     * @param {string} password - Contraseña (opcional)
     * @param {string} role - Rol
     * @param {string} section - Sección (opcional)
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateUserData(cedula, email = '', password = '', role = 'Usuario', section = '') {
        const errors = {};

        // Validar cédula
        const cedulaValidation = this.cedulaFormat(cedula);
        if (!cedulaValidation.valid) {
            errors.cedula = cedulaValidation.message;
        }

        // Validar correo electrónico (opcional)
        if (email) {
            const emailValidation = this.email(email);
            if (!emailValidation.valid) {
                errors.email = emailValidation.message;
            }
        }

        // Validar contraseña (opcional)
        if (password) {
            const passwordValidation = this.minLength(password, 8, 'contraseña');
            if (!passwordValidation.valid) {
                errors.password = passwordValidation.message;
            }
        }

        // Validar rol
        const validRoles = ['Usuario', 'Docente', 'Administrador'];
        if (!validRoles.includes(role)) {
            errors.role = 'Rol inválido.';
        }

        // Validar sección (opcional)
        if (section) {
            const sectionValidation = this.sectionFormat(section);
            if (!sectionValidation.valid) {
                errors.section = sectionValidation.message;
            }
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Validar datos de unidad
     * @param {string} slug - Slug de la unidad
     * @param {string} title - Título de la unidad
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateUnitData(slug, title) {
        const errors = {};

        const titleValidation = this.required(title, 'título');
        if (!titleValidation.valid) {
            errors.title = titleValidation.message;
        }

        // Slug es opcional, pero si se proporciona debe ser válido
        if (slug) {
            const slugValidation = this.slugFormat(slug);
            if (!slugValidation.valid) {
                errors.slug = slugValidation.message;
            }
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Validar datos de evaluación
     * @param {string} key - Clave de la evaluación
     * @param {string} title - Título
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateEvaluationData(key, title) {
        const errors = {};

        const keyValidation = this.required(key, 'clave');
        if (!keyValidation.valid) {
            errors.key = keyValidation.message;
        }

        const titleValidation = this.required(title, 'título');
        if (!titleValidation.valid) {
            errors.title = titleValidation.message;
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Validar datos de contenido teórico
     * @param {string} type - Tipo de contenido
     * @param {string} title - Título
     * @param {string} content - Contenido (para texto)
     * @param {string} url - URL (para otros tipos)
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateContentData(type, title, content = '', url = '') {
        const errors = {};

        const validTypes = ['texto', 'imagen', 'documento', 'video', 'enlace'];
        if (!validTypes.includes(type)) {
            errors.type = 'Tipo de contenido inválido.';
        }

        const titleValidation = this.required(title, 'título');
        if (!titleValidation.valid) {
            errors.title = titleValidation.message;
        }

        // Para contenido de tipo texto, el contenido es obligatorio
        if (type === 'texto') {
            const contentValidation = this.required(content, 'contenido');
            if (!contentValidation.valid) {
                errors.content = contentValidation.message;
            }
        } else {
            // Para otros tipos, la URL es obligatoria
            const urlValidation = this.required(url, 'URL');
            if (!urlValidation.valid) {
                errors.url = urlValidation.message;
            } else {
                const urlFormatValidation = this.urlFormat(url);
                if (!urlFormatValidation.valid) {
                    errors.url = urlFormatValidation.message;
                }
            }
        }

        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    }

    /**
     * Sanitizar texto para prevenir XSS
     * @param {string} text - Texto a sanitizar
     * @returns {string} - Texto sanitizado
     */
    static sanitizeText(text) {
        if (typeof text !== 'string') {
            return '';
        }
        return text.replace(/[&<>"']/g, function(match) {
            return {
                '&': '&',
                '<': '<',
                '>': '>',
                '"': '"',
                "'": '&#39;'
            }[match];
        });
    }

    /**
     * Mostrar mensaje de error en un elemento
     * @param {string} elementId - ID del elemento
     * @param {string} message - Mensaje de error
     */
    static showError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = message;
            element.style.color = '#dc2626';
            element.style.display = 'block';
        }
    }

    /**
     * Limpiar mensaje de error
     * @param {string} elementId - ID del elemento
     */
    static clearError(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = '';
            element.style.display = 'none';
        }
    }

    /**
     * Aplicar validación en tiempo real a un campo de formulario
     * @param {HTMLInputElement} input - Elemento de entrada
     * @param {string} validationType - Tipo de validación ('email', 'numeric', 'required', etc.)
     * @param {object} options - Opciones adicionales para la validación
     */
    static applyRealTimeValidation(input, validationType, options = {}) {
        const errorElementId = `${input.id}-error`;
        let errorElement = document.getElementById(errorElementId);

        // Crear elemento de error si no existe
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = errorElementId;
            errorElement.className = 'validation-error';
            errorElement.style.color = '#dc2626';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '4px';
            errorElement.style.display = 'none';
            input.parentNode.appendChild(errorElement);
        }

        // Función para validar y mostrar/ocultar errores
        const validateAndShowError = () => {
            const value = input.value.trim();
            let isValid = true;
            let errorMessage = '';

            switch (validationType) {
                case 'email':
                    if (value === '') {
                        if (options.required) {
                            isValid = false;
                            errorMessage = 'El correo electrónico es obligatorio.';
                        }
                    } else if (!Validation.isValidEmailFormat(value)) {
                        isValid = false;
                        errorMessage = 'Formato de correo electrónico inválido.';
                    }
                    break;

                case 'numeric':
                    if (value === '') {
                        if (options.required) {
                            isValid = false;
                            errorMessage = 'Este campo es obligatorio.';
                        }
                    } else if (!Validation.isNumericOnly(value)) {
                        isValid = false;
                        errorMessage = 'Solo se permiten números.';
                    }
                    break;

                case 'section':
                    if (value !== '' && !Validation.sectionFormat(value).valid) {
                        isValid = false;
                        errorMessage = 'Formato inválido (ej: 20, 21).';
                    }
                    break;

                case 'required':
                    if (value === '') {
                        isValid = false;
                        errorMessage = 'Este campo es obligatorio.';
                    }
                    break;

                case 'minLength':
                    if (value.length > 0 && value.length < (options.minLength || 8)) {
                        isValid = false;
                        errorMessage = `Mínimo ${options.minLength || 8} caracteres.`;
                    }
                    break;

                case 'password':
                    if (value === '') {
                        if (options.required) {
                            isValid = false;
                            errorMessage = 'La contraseña es obligatoria.';
                        }
                    } else {
                        const checks = {
                            length: value.length >= 8,
                            uppercase: /[A-Z]/.test(value),
                            lowercase: /[a-z]/.test(value),
                            number: /\d/.test(value),
                            special: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(value)
                        };

                        if (!checks.length) {
                            isValid = false;
                            errorMessage = 'Mínimo 8 caracteres.';
                        } else if (!checks.uppercase) {
                            isValid = false;
                            errorMessage = 'Debe contener al menos una mayúscula.';
                        } else if (!checks.lowercase) {
                            isValid = false;
                            errorMessage = 'Debe contener al menos una minúscula.';
                        } else if (!checks.number) {
                            isValid = false;
                            errorMessage = 'Debe contener al menos un número.';
                        } else if (!checks.special) {
                            isValid = false;
                            errorMessage = 'Debe contener al menos un carácter especial.';
                        }
                    }
                    break;

                default:
                    break;
            }

            if (isValid) {
                input.style.borderColor = '';
                errorElement.style.display = 'none';
            } else {
                input.style.borderColor = '#dc2626';
                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            }

            return isValid;
        };

        // Aplicar validación en eventos clave
        input.addEventListener('input', validateAndShowError);
        input.addEventListener('blur', validateAndShowError);
        input.addEventListener('focus', () => {
            // Limpiar error al enfocar para dar oportunidad de corregir
            input.style.borderColor = '';
        });

        // Validación inicial
        validateAndShowError();
    }

    /**
     * Aplicar validación en tiempo real a un formulario completo
     * @param {HTMLFormElement} form - Formulario
     * @param {object} fieldConfig - Configuración de validación para cada campo
     */
    static applyFormRealTimeValidation(form, fieldConfig) {
        for (const fieldName in fieldConfig) {
            const input = form.elements[fieldName];
            if (input) {
                const config = fieldConfig[fieldName];
                Validation.applyRealTimeValidation(input, config.type, config.options || {});
            }
        }
    }

    /**
     * Validar un formulario completo
     * @param {HTMLFormElement} form - Elemento de formulario
     * @param {object} validationRules - Reglas de validación
     * @returns {object} - { valid: boolean, errors: object }
     */
    static validateForm(form, validationRules) {
        const errors = {};
        let isValid = true;

        for (const fieldName in validationRules) {
            const field = form.elements[fieldName];
            if (!field) continue;

            const rules = validationRules[fieldName];
            const value = field.value.trim();

            for (const rule of rules) {
                let validationResult;

                switch (rule.type) {
                    case 'required':
                        validationResult = this.required(value, fieldName);
                        break;
                    case 'email':
                        validationResult = this.email(value);
                        break;
                    case 'username':
                        validationResult = this.usernameFormat(value);
                        break;
                    case 'password':
                        validationResult = this.passwordStrength(value);
                        break;
                    case 'minLength':
                        validationResult = this.minLength(value, rule.value, fieldName);
                        break;
                    case 'maxLength':
                        validationResult = this.maxLength(value, rule.value, fieldName);
                        break;
                    case 'cedula':
                        validationResult = this.cedulaFormat(value);
                        break;
                    case 'section':
                        validationResult = this.sectionFormat(value);
                        break;
                    case 'slug':
                        validationResult = this.slugFormat(value);
                        break;
                    case 'url':
                        validationResult = this.urlFormat(value);
                        break;
                    default:
                        continue;
                }

                if (!validationResult.valid) {
                    errors[fieldName] = validationResult.message;
                    isValid = false;
                    break;
                }
            }
        }

        return {
            valid: isValid,
            errors: errors
        };
    }
}

// Exportar para uso en módulos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Validation;
}

// Hacer disponible globalmente en el navegador
window.Validation = Validation;
