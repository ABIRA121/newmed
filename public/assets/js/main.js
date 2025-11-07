// MedPortal Main JavaScript
class MedPortal {
    constructor() {
        this.themeButtons = [];
        this.backButton = null;
        this.homeButton = null;
        this.utilityContainer = null;
        this.currentTheme = 'light';
        this.init();
    }

    init() {
        this.initTheme();
        this.initGlobalControls();
        this.initHeaderNavigation();
        this.initForms();
        this.initNotifications();
        this.initDateTimePickers();
    }

    // Theme management
    initTheme() {
        const { theme, persist } = this.getInitialTheme();
        this.applyTheme(theme, persist);

        const headerToggle = document.getElementById('themeToggle');
        if (headerToggle) {
            this.registerThemeButton(headerToggle);
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            if (button !== headerToggle) {
                this.registerThemeButton(button);
            }
        });

        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            mediaQuery.addEventListener('change', (event) => {
                const storedTheme = localStorage.getItem('theme');
                if (!storedTheme) {
                    this.applyTheme(event.matches ? 'dark' : 'light', false);
                }
            });
        }
    }

    getInitialTheme() {
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme === 'dark' || storedTheme === 'light') {
            return { theme: storedTheme, persist: true };
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return { theme: 'dark', persist: false };
        }

        return { theme: 'light', persist: false };
    }

    applyTheme(theme, persist = true) {
        this.currentTheme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        if (persist) {
            localStorage.setItem('theme', theme);
        } else {
            localStorage.removeItem('theme');
        }
        this.themeButtons.forEach(button => this.updateThemeButton(button, theme));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme, true);
    }

    registerThemeButton(button) {
        if (!button) {
            return;
        }

        if (!this.themeButtons.includes(button)) {
            button.addEventListener('click', () => this.toggleTheme());
            this.themeButtons.push(button);
        }

        this.updateThemeButton(button, this.currentTheme);
    }

    updateThemeButton(button, theme) {
        const isDark = theme === 'dark';
        const nextThemeLabel = isDark ? 'Light Mode' : 'Dark Mode';
        const icon = isDark ? '☀️' : '🌙';
        const label = `${icon} ${nextThemeLabel}`;

        if (button.classList.contains('utility-button')) {
            button.innerHTML = `<span class="icon">${icon}</span><span>${nextThemeLabel}</span>`;
        } else {
            button.textContent = label;
        }

        const titleText = `Switch to ${isDark ? 'light' : 'dark'} mode`;
        button.setAttribute('aria-label', titleText);
        button.setAttribute('title', titleText);
    }

    // Form handling
    initForms() {
        // AJAX form submission
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', this.handleAjaxForm.bind(this));
        });

        // Real-time validation
        document.querySelectorAll('[data-validate]').forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this));
            input.addEventListener('input', this.clearFieldError.bind(this, input));
        });

        // Password strength indicator
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', this.checkPasswordStrength.bind(this));
        });
    }

    async handleAjaxForm(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.innerHTML || 'Submit';

        try {
            // Show loading state
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="loading"></span> Processing...';
                submitBtn.disabled = true;
            }

            const formData = new FormData(form);
            
            // Add AJAX header if not present
            const headers = {};
            if (!formData.has('X-Requested-With')) {
                headers['X-Requested-With'] = 'XMLHttpRequest';
            }

            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: formData,
                headers: headers
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const result = await response.json();

                if (result.success) {
                    this.showNotification(result.message || 'Operation completed successfully', 'success');
                    
                    // Handle redirects
                    if (result.redirect) {
                        setTimeout(() => {
                            window.location.href = result.redirect;
                        }, 1000);
                    } else if (form.dataset.redirect) {
                        setTimeout(() => {
                            window.location.href = form.dataset.redirect;
                        }, 1000);
                    }
                    
                    // Reset form if needed
                    if (form.dataset.reset || result.reset) {
                        form.reset();
                    }
                    
                    // Reload page if needed
                    if (result.reload) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    this.showNotification(result.message || 'An error occurred', 'error');
                    if (result.errors) {
                        this.showFormErrors(form, result.errors);
                    }
                }
            } else {
                // If not JSON, assume success and redirect or reload
                this.showNotification('Operation completed successfully', 'success');
                if (form.dataset.redirect) {
                    setTimeout(() => {
                        window.location.href = form.dataset.redirect;
                    }, 1000);
                } else {
                    window.location.reload();
                }
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    }

    validateField(e) {
        const field = e.target;
        const value = field.value.trim();
        const rules = field.dataset.validate ? field.dataset.validate.split(' ') : [];

        // Skip validation if field is empty and not required
        if (value === '' && !rules.includes('required')) {
            this.clearFieldError(field);
            return;
        }

        for (const rule of rules) {
            const isValid = this.validateRule(value, rule, field);
            if (!isValid) {
                this.showFieldError(field, this.getErrorMessage(rule, field));
                return;
            }
        }

        this.clearFieldError(field);
    }

    validateRule(value, rule, field) {
        switch (rule) {
            case 'required':
                return value !== '';
            case 'email':
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            case 'phone':
                return /^[\+]?[1-9][\d]{0,15}$/.test(value.replace(/[\s\-\(\)]/g, ''));
            case 'min8':
                return value.length >= 8;
            case 'date':
                return !isNaN(Date.parse(value));
            case 'match':
                const matchField = document.getElementById(field.dataset.match);
                return matchField && value === matchField.value;
            default:
                return true;
        }
    }

    getErrorMessage(rule, field) {
        const messages = {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            phone: 'Please enter a valid phone number',
            min8: 'Must be at least 8 characters long',
            date: 'Please enter a valid date',
            match: 'Fields do not match'
        };
        
        return field.dataset.errorMessage || messages[rule] || 'Invalid value';
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        field.classList.add('error');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.textContent = message;
        errorElement.style.cssText = 'color: var(--error-color); font-size: 0.875rem; margin-top: 0.25rem;';
        
        field.parentNode.appendChild(errorElement);
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    showFormErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.field-error').forEach(error => error.remove());
        form.querySelectorAll('.form-control').forEach(field => field.classList.remove('error'));

        // Show new errors
        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(fieldName => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    this.showFieldError(field, errors[fieldName]);
                }
            });
        }
    }

    checkPasswordStrength(e) {
        const password = e.target.value;
        const strengthIndicator = e.target.parentNode.querySelector('.password-strength');
        
        if (!strengthIndicator) return;

        let strength = 0;
        let feedback = '';

        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        switch (strength) {
            case 0:
            case 1:
                feedback = 'Very Weak';
                strengthIndicator.style.color = 'var(--error-color)';
                break;
            case 2:
                feedback = 'Weak';
                strengthIndicator.style.color = 'var(--warning-color)';
                break;
            case 3:
                feedback = 'Good';
                strengthIndicator.style.color = 'var(--warning-color)';
                break;
            case 4:
                feedback = 'Strong';
                strengthIndicator.style.color = 'var(--success-color)';
                break;
            case 5:
                feedback = 'Very Strong';
                strengthIndicator.style.color = 'var(--success-color)';
                break;
        }

        strengthIndicator.textContent = feedback;
    }

    // Notifications
    initNotifications() {
        // Auto-hide flash messages
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-persistent)').forEach(alert => {
                this.hideNotification(alert);
            });
        }, 5000);

        // Add close buttons to alerts
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.querySelector('.alert-close')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.style.cssText = 'background: none; border: none; font-size: 1.2rem; cursor: pointer; float: right;';
                closeBtn.addEventListener('click', () => this.hideNotification(alert));
                alert.appendChild(closeBtn);
            }
        });
    }

    showNotification(message, type = 'info', persistent = false) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} ${persistent ? 'alert-persistent' : ''}`;
        notification.innerHTML = `
            ${message}
            <button class="alert-close" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; float: right;">&times;</button>
        `;
        notification.style.cssText = `
            position: fixed; 
            top: 20px; 
            right: 20px; 
            z-index: 10000; 
            min-width: 300px; 
            max-width: 500px;
            box-shadow: var(--shadow-lg);
        `;

        document.body.appendChild(notification);

        const closeBtn = notification.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => this.hideNotification(notification));

        if (!persistent) {
            setTimeout(() => {
                this.hideNotification(notification);
            }, 5000);
        }

        return notification;
    }

    hideNotification(notification) {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Date time pickers
    initDateTimePickers() {
        // Initialize any date/time pickers
        document.querySelectorAll('input[type="date"]').forEach(input => {
            // Set min date to today if not already set
            if (!input.min) {
                const today = new Date().toISOString().split('T')[0];
                input.min = today;
            }
        });

        document.querySelectorAll('input[type="datetime-local"]').forEach(input => {
            // Set min date to today if not already set
            if (!input.min) {
                const now = new Date();
                // Round to nearest 15 minutes
                const minutes = Math.ceil(now.getMinutes() / 15) * 15;
                now.setMinutes(minutes);
                now.setSeconds(0);
                now.setMilliseconds(0);
                
                const localDateTime = now.toISOString().slice(0, 16);
                input.min = localDateTime;
            }
        });
    }

    // Utility functions
    formatDate(dateString) {
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    }

    formatDateTime(dateString) {
        try {
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // API helper method
    async apiCall(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }

    initGlobalControls() {
        if (document.body && document.body.dataset.disableUtilities === 'true') {
            return;
        }

        if (this.utilityContainer) {
            this.updateBackButtonState();
            return;
        }

        const container = document.createElement('div');
        container.className = 'utility-buttons';

        const themeButton = this.createUtilityButton('theme', '🌙', 'Dark Mode', 'Toggle dark or light mode');
        const backButton = this.createUtilityButton('back', '↩', 'Back', 'Go back to the previous page');
        const homeButton = this.createUtilityButton('home', '🏠', 'Home', 'Return to the home page');

        container.append(themeButton, backButton, homeButton);
        document.body.appendChild(container);

        this.utilityContainer = container;
        this.backButton = backButton;
        this.homeButton = homeButton;

        this.registerThemeButton(themeButton);

        backButton.addEventListener('click', () => this.handleBackAction());
        homeButton.addEventListener('click', () => {
            window.location.href = this.getHomeUrl();
        });

        this.updateBackButtonState();

        window.addEventListener('popstate', () => this.updateBackButtonState());
        window.addEventListener('pageshow', () => this.updateBackButtonState());
    }

    createUtilityButton(action, icon, text, ariaLabel) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'utility-button';
        button.dataset.action = action;
        button.setAttribute('aria-label', ariaLabel);
        button.setAttribute('title', ariaLabel);
        button.innerHTML = `<span class="icon">${icon}</span><span>${text}</span>`;
        return button;
    }

    updateBackButtonState() {
        if (!this.backButton) {
            return;
        }

        const canGoBack = window.history.length > 1 || document.referrer !== '';
        this.backButton.disabled = !canGoBack;
        this.backButton.classList.toggle('disabled', !canGoBack);
    }

    handleBackAction() {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        if (document.referrer) {
            window.location.href = document.referrer;
            return;
        }

        window.location.href = this.getHomeUrl();
    }

    getHomeUrl() {
        if (document.body && document.body.dataset && document.body.dataset.homeUrl) {
            return document.body.dataset.homeUrl;
        }

        const path = window.location.pathname;
        const publicIndex = path.toLowerCase().indexOf('/public/');

        if (publicIndex !== -1) {
            const base = path.substring(0, publicIndex + 8); // include '/public/'
            return `${window.location.origin}${base}index.php`;
        }

        return `${window.location.origin}/`;
    }

    initHeaderNavigation() {
        document.querySelectorAll('[data-nav-back]').forEach(button => {
            button.addEventListener('click', () => this.handleBackAction());
        });

        document.querySelectorAll('[data-nav-home]').forEach(button => {
            button.addEventListener('click', () => {
                window.location.href = this.getHomeUrl();
            });
        });

        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            this.registerThemeButton(button);
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.medPortal = new MedPortal();
});

// Global utility functions
window.formatDate = (dateString) => window.medPortal?.formatDate(dateString) || dateString;
window.formatDateTime = (dateString) => window.medPortal?.formatDateTime(dateString) || dateString;

// Make API client available globally
window.ApiClient = {
    async request(endpoint, options = {}) {
        return window.medPortal?.apiCall(endpoint, options);
    },

    async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
};

// Add some CSS for new elements
const additionalCSS = `
.password-strength {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    font-weight: 600;
}

.alert-persistent {
    position: fixed !important;
}

.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    vertical-align: middle;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

[data-theme="dark"] .loading {
    border-top-color: var(--text-color);
}
`;

// Inject additional CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);