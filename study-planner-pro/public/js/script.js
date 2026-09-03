/**
 * =============================================
 * 📚 STUDYHUB PRO - Advanced JavaScript
 * Version: 2.0.0
 * =============================================
 */

// =============================================
// 1. DOM READY - Main Application
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    
    'use strict';
    
    // =============================================
    // 2. CONFIGURATION
    // =============================================
    const CONFIG = {
        BASE_URL: window.BASE_URL || '/study-planner-pro/',
        APP_NAME: window.APP_NAME || 'StudyHub',
        CSRF_TOKEN: window.CSRF_TOKEN || '',
        USER_ID: window.USER_ID || null,
        NOTIFICATION_INTERVAL: 60000, // 60 seconds
        DEBOUNCE_DELAY: 300,
        ANIMATION_DELAY: 100
    };
    
    // =============================================
    // 3. DOM REFS (Cached selectors for performance)
    // =============================================
    const DOM = {
        body: document.body,
        html: document.documentElement,
        
        // Navigation
        mobileToggle: document.getElementById('mobileToggle'),
        mainNav: document.getElementById('mainNav'),
        
        // Theme
        themeToggle: document.getElementById('themeToggle'),
        themeIcon: document.getElementById('themeIcon'),
        
        // User
        userToggle: document.getElementById('userToggle'),
        userDropdown: document.getElementById('userDropdown'),
        
        // Notifications
        notificationToggle: document.getElementById('notificationToggle'),
        notificationDropdown: document.getElementById('notificationDropdown'),
        notificationList: document.getElementById('notificationList'),
        notificationBadge: document.getElementById('notificationBadge'),
        markAllRead: document.getElementById('markAllRead'),
        
        // Forms
        loginForm: document.getElementById('loginForm'),
        registerForm: document.getElementById('registerForm'),
        subjectForm: document.getElementById('subjectForm'),
        taskForm: document.getElementById('taskForm'),
        
        // Filters
        filterStatus: document.getElementById('filterStatus'),
        filterSubject: document.getElementById('filterSubject'),
        filterForm: document.getElementById('filterForm'),
        
        // Loading
        loadingSpinner: document.getElementById('loading-spinner'),
        
        // Back to top
        backToTop: document.querySelector('.back-to-top'),
        
        // Alerts
        alerts: document.querySelectorAll('.alert-dismissible')
    };
    
    // =============================================
    // 4. THEME MANAGEMENT
    // =============================================
    const ThemeManager = {
        init() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            this.setTheme(savedTheme);
            
            if (DOM.themeToggle) {
                DOM.themeToggle.addEventListener('click', () => this.toggle());
            }
        },
        
        setTheme(theme) {
            if (theme === 'dark') {
                DOM.body.classList.add('dark-mode');
                if (DOM.themeIcon) {
                    DOM.themeIcon.className = 'fas fa-sun';
                }
            } else {
                DOM.body.classList.remove('dark-mode');
                if (DOM.themeIcon) {
                    DOM.themeIcon.className = 'fas fa-moon';
                }
            }
            localStorage.setItem('theme', theme);
        },
        
        toggle() {
            const isDark = DOM.body.classList.contains('dark-mode');
            this.setTheme(isDark ? 'light' : 'dark');
        },
        
        getCurrent() {
            return DOM.body.classList.contains('dark-mode') ? 'dark' : 'light';
        }
    };
    
    // =============================================
    // 5. MOBILE MENU
    // =============================================
    const MobileMenu = {
        init() {
            if (DOM.mobileToggle && DOM.mainNav) {
                DOM.mobileToggle.addEventListener('click', () => this.toggle());
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (DOM.mainNav.classList.contains('mobile-open') &&
                        !DOM.mainNav.contains(e.target) &&
                        !DOM.mobileToggle.contains(e.target)) {
                        this.close();
                    }
                });
                
                // Close on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && DOM.mainNav.classList.contains('mobile-open')) {
                        this.close();
                    }
                });
            }
        },
        
        toggle() {
            DOM.mainNav.classList.toggle('mobile-open');
            const icon = DOM.mobileToggle.querySelector('i');
            if (icon) {
                icon.className = DOM.mainNav.classList.contains('mobile-open') 
                    ? 'fas fa-times' 
                    : 'fas fa-bars';
            }
        },
        
        close() {
            DOM.mainNav.classList.remove('mobile-open');
            const icon = DOM.mobileToggle.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-bars';
            }
        }
    };
    
    // =============================================
    // 6. USER DROPDOWN
    // =============================================
    const UserDropdown = {
        init() {
            if (DOM.userToggle && DOM.userDropdown) {
                DOM.userToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggle();
                });
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (DOM.userDropdown.classList.contains('show') &&
                        !DOM.userDropdown.contains(e.target) &&
                        !DOM.userToggle.contains(e.target)) {
                        this.close();
                    }
                });
                
                // Close on escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && DOM.userDropdown.classList.contains('show')) {
                        this.close();
                    }
                });
            }
        },
        
        toggle() {
            DOM.userDropdown.classList.toggle('show');
        },
        
        close() {
            DOM.userDropdown.classList.remove('show');
        }
    };
    
    // =============================================
    // 7. NOTIFICATION SYSTEM
    // =============================================
    const NotificationSystem = {
        intervalId: null,
        isOpen: false,
        
        init() {
            if (DOM.notificationToggle && DOM.notificationDropdown) {
                DOM.notificationToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggle();
                });
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (DOM.notificationDropdown.classList.contains('show') &&
                        !DOM.notificationDropdown.contains(e.target) &&
                        !DOM.notificationToggle.contains(e.target)) {
                        this.close();
                    }
                });
                
                // Mark all read
                if (DOM.markAllRead) {
                    DOM.markAllRead.addEventListener('click', () => this.markAllRead());
                }
                
                // Mark individual read (event delegation)
                if (DOM.notificationList) {
                    DOM.notificationList.addEventListener('click', (e) => {
                        const btn = e.target.closest('.mark-read');
                        if (btn) {
                            const id = btn.dataset.id;
                            if (id) this.markRead(id);
                        }
                    });
                }
                
                // Auto-refresh
                this.startAutoRefresh();
            }
        },
        
        toggle() {
            DOM.notificationDropdown.classList.toggle('show');
            if (DOM.notificationDropdown.classList.contains('show')) {
                this.load();
                this.isOpen = true;
            } else {
                this.isOpen = false;
            }
        },
        
        close() {
            DOM.notificationDropdown.classList.remove('show');
            this.isOpen = false;
        },
        
        load() {
            if (!DOM.notificationList) return;
            
            // Show loading
            DOM.notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading notifications...</p>
                </div>
            `;
            
            fetch(CONFIG.BASE_URL + 'pages/ajax.php?action=get_notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.render(data);
                    this.updateBadge(data.unread_count || 0);
                } else {
                    this.showError('Failed to load notifications');
                }
            })
            .catch(() => {
                this.showError('Connection error. Please try again.');
            });
        },
        
        render(data) {
            if (!DOM.notificationList) return;
            
            const notifications = data.notifications || [];
            
            if (notifications.length > 0) {
                DOM.notificationList.innerHTML = notifications.map(n => `
                    <div class="notification-item ${n.is_read ? 'read' : 'unread'}">
                        <div class="notification-icon">${n.icon || '📌'}</div>
                        <div class="notification-content">
                            <div class="notification-title">${this.escapeHtml(n.title)}</div>
                            <div class="notification-message">${this.escapeHtml(n.message)}</div>
                            <div class="notification-time">${n.time_ago || 'Just now'}</div>
                        </div>
                        ${!n.is_read ? `<button class="mark-read" data-id="${n.id}">✓</button>` : ''}
                    </div>
                `).join('');
            } else {
                DOM.notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications</p>
                    </div>
                `;
            }
        },
        
        updateBadge(count) {
            if (DOM.notificationBadge) {
                DOM.notificationBadge.textContent = count;
                DOM.notificationBadge.style.display = count > 0 ? 'flex' : 'none';
            }
        },
        
        markRead(id) {
            fetch(CONFIG.BASE_URL + 'pages/ajax.php?action=mark_read&id=' + id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.load();
                }
            })
            .catch(() => {
                // Silent fail
            });
        },
        
        markAllRead() {
            fetch(CONFIG.BASE_URL + 'pages/ajax.php?action=mark_all_read', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.load();
                }
            })
            .catch(() => {
                // Silent fail
            });
        },
        
        showError(message) {
            if (DOM.notificationList) {
                DOM.notificationList.innerHTML = `
                    <div class="notification-empty" style="color: #dc2626;">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>${message}</p>
                    </div>
                `;
            }
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        startAutoRefresh() {
            if (this.intervalId) clearInterval(this.intervalId);
            
            this.intervalId = setInterval(() => {
                if (this.isOpen) {
                    this.load();
                } else {
                    // Just update badge in background
                    this.updateBadgeInBackground();
                }
            }, CONFIG.NOTIFICATION_INTERVAL);
        },
        
        updateBadgeInBackground() {
            fetch(CONFIG.BASE_URL + 'pages/ajax.php?action=get_notifications', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.updateBadge(data.unread_count || 0);
                }
            })
            .catch(() => {
                // Silent fail
            });
        },
        
        destroy() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        }
    };
    
    // =============================================
    // 8. PASSWORD STRENGTH METER
    // =============================================
    const PasswordStrength = {
        init() {
            const passwordInput = document.getElementById('password');
            if (!passwordInput) return;
            
            const elements = {
                strengthDiv: document.getElementById('passwordStrength'),
                segments: [
                    document.getElementById('ps1'),
                    document.getElementById('ps2'),
                    document.getElementById('ps3'),
                    document.getElementById('ps4')
                ],
                text: document.getElementById('psText'),
                requirements: {
                    length: document.getElementById('req-length'),
                    uppercase: document.getElementById('req-uppercase'),
                    lowercase: document.getElementById('req-lowercase'),
                    number: document.getElementById('req-number')
                }
            };
            
            // Check if all required elements exist
            const hasAllElements = elements.segments.every(el => el !== null) && 
                                 elements.text !== null &&
                                 Object.values(elements.requirements).every(el => el !== null);
            
            if (!hasAllElements) return;
            
            passwordInput.addEventListener('input', () => {
                this.check(passwordInput.value, elements);
            });
        },
        
        check(password, elements) {
            const hasLength = password.length >= 6;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Update requirements
            this.updateRequirement(elements.requirements.length, hasLength);
            this.updateRequirement(elements.requirements.uppercase, hasUppercase);
            this.updateRequirement(elements.requirements.lowercase, hasLowercase);
            this.updateRequirement(elements.requirements.number, hasNumber);
            
            // Calculate score
            let score = 0;
            if (hasLength) score++;
            if (hasUppercase) score++;
            if (hasLowercase) score++;
            if (hasNumber) score++;
            
            // Show/hide strength meter
            if (password.length > 0) {
                elements.strengthDiv.style.display = 'block';
            } else {
                elements.strengthDiv.style.display = 'none';
                return;
            }
            
            // Update segments
            elements.segments.forEach((seg, i) => {
                seg.className = 'segment';
                if (i < score) {
                    seg.classList.add('active');
                    if (score <= 2) seg.classList.add('weak');
                    else if (score === 3) seg.classList.add('medium');
                    else seg.classList.add('strong');
                }
            });
            
            // Update text
            const labels = ['Weak Password', 'Weak Password', 'Medium Password', 'Strong Password', 'Very Strong Password'];
            const classes = ['weak', 'weak', 'medium', 'strong', 'very-strong'];
            
            elements.text.textContent = labels[score] || labels[0];
            elements.text.className = 'password-strength-text ' + (classes[score] || classes[0]);
        },
        
        updateRequirement(element, met) {
            if (!element) return;
            element.className = 'req';
            const text = element.textContent.trim();
            if (met) {
                element.classList.add('met');
                element.innerHTML = '<i class="fas fa-check-circle"></i> ' + text;
            } else {
                element.classList.add('unmet');
                element.innerHTML = '<i class="fas fa-circle"></i> ' + text;
            }
        }
    };
    
    // =============================================
    // 9. FORM VALIDATION
    // =============================================
    const FormValidator = {
        init() {
            // Login form
            if (DOM.loginForm) {
                DOM.loginForm.addEventListener('submit', (e) => {
                    if (!this.validateLogin(e.target)) {
                        e.preventDefault();
                    }
                });
            }
            
            // Register form
            if (DOM.registerForm) {
                DOM.registerForm.addEventListener('submit', (e) => {
                    if (!this.validateRegister(e.target)) {
                        e.preventDefault();
                    }
                });
            }
            
            // Subject form
            if (DOM.subjectForm) {
                DOM.subjectForm.addEventListener('submit', (e) => {
                    if (!this.validateSubject(e.target)) {
                        e.preventDefault();
                    }
                });
            }
            
            // Task form
            if (DOM.taskForm) {
                DOM.taskForm.addEventListener('submit', (e) => {
                    if (!this.validateTask(e.target)) {
                        e.preventDefault();
                    }
                });
            }
        },
        
        validateLogin(form) {
            const email = form.querySelector('#email');
            const password = form.querySelector('#password');
            let isValid = true;
            
            if (!email.value.trim()) {
                this.showError(email, 'Email is required');
                isValid = false;
            } else if (!this.isValidEmail(email.value.trim())) {
                this.showError(email, 'Please enter a valid email');
                isValid = false;
            } else {
                this.clearError(email);
            }
            
            if (!password.value.trim()) {
                this.showError(password, 'Password is required');
                isValid = false;
            } else {
                this.clearError(password);
            }
            
            return isValid;
        },
        
        validateRegister(form) {
            const name = form.querySelector('#name');
            const email = form.querySelector('#email');
            const password = form.querySelector('#password');
            const confirm = form.querySelector('#confirm_password');
            const terms = form.querySelector('#terms');
            let isValid = true;
            
            // Name
            if (!name.value.trim() || name.value.trim().length < 2) {
                this.showError(name, 'Name must be at least 2 characters');
                isValid = false;
            } else {
                this.clearError(name);
            }
            
            // Email
            if (!email.value.trim()) {
                this.showError(email, 'Email is required');
                isValid = false;
            } else if (!this.isValidEmail(email.value.trim())) {
                this.showError(email, 'Please enter a valid email');
                isValid = false;
            } else {
                this.clearError(email);
            }
            
            // Password
            if (!password.value.trim() || password.value.trim().length < 6) {
                this.showError(password, 'Password must be at least 6 characters');
                isValid = false;
            } else {
                this.clearError(password);
            }
            
            // Confirm
            if (confirm.value.trim() !== password.value.trim()) {
                this.showError(confirm, 'Passwords do not match');
                isValid = false;
            } else if (confirm.value.trim()) {
                this.clearError(confirm);
            }
            
            // Terms
            if (!terms.checked) {
                alert('⚠️ Please agree to the Terms & Conditions');
                isValid = false;
            }
            
            return isValid;
        },
        
        validateSubject(form) {
            const name = form.querySelector('#subject_name');
            let isValid = true;
            
            if (!name.value.trim()) {
                this.showError(name, 'Subject name is required');
                isValid = false;
            } else {
                this.clearError(name);
            }
            
            return isValid;
        },
        
        validateTask(form) {
            const name = form.querySelector('#task_name');
            const subject = form.querySelector('#subject_id');
            const dueDate = form.querySelector('#due_date');
            let isValid = true;
            
            if (!name.value.trim()) {
                this.showError(name, 'Task name is required');
                isValid = false;
            } else {
                this.clearError(name);
            }
            
            if (!subject.value) {
                this.showError(subject, 'Please select a subject');
                isValid = false;
            } else {
                this.clearError(subject);
            }
            
            if (!dueDate.value) {
                this.showError(dueDate, 'Due date is required');
                isValid = false;
            } else {
                this.clearError(dueDate);
            }
            
            return isValid;
        },
        
        showError(input, message) {
            if (!input) return;
            input.classList.add('error');
            input.classList.remove('success');
            
            const wrapper = input.closest('.auth-input-wrapper') || input.parentElement;
            const existing = wrapper.parentElement.querySelector('.field-error');
            if (existing) existing.remove();
            
            const error = document.createElement('span');
            error.className = 'field-error';
            error.textContent = '⚠️ ' + message;
            wrapper.parentElement.appendChild(error);
        },
        
        clearError(input) {
            if (!input) return;
            input.classList.remove('error');
            input.classList.add('success');
            
            const wrapper = input.closest('.auth-input-wrapper') || input.parentElement;
            const existing = wrapper.parentElement.querySelector('.field-error');
            if (existing) existing.remove();
        },
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };
    
    // =============================================
    // 10. FILTER SYSTEM (Tasks page)
    // =============================================
    const FilterSystem = {
        init() {
            // Auto-submit on filter change
            if (DOM.filterStatus) {
                DOM.filterStatus.addEventListener('change', () => {
                    this.submit();
                });
            }
            
            if (DOM.filterSubject) {
                DOM.filterSubject.addEventListener('change', () => {
                    this.submit();
                });
            }
            
            // Search with debounce
            const searchInput = document.querySelector('.filter-search input');
            if (searchInput) {
                let timeout;
                searchInput.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.submit();
                    }, CONFIG.DEBOUNCE_DELAY);
                });
            }
        },
        
        submit() {
            if (DOM.filterForm) {
                DOM.filterForm.submit();
            }
        }
    };
    
    // =============================================
    // 11. PASSWORD TOGGLE
    // =============================================
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (!input || !icon) return;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
    
    // Make function global for inline onclick
    window.togglePasswordVisibility = togglePasswordVisibility;
    
    // =============================================
    // 12. CONFIRM PASSWORD MATCH (Real-time)
    // =============================================
    const ConfirmPassword = {
        init() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            const matchDiv = document.getElementById('confirmMatch');
            
            if (!password || !confirm || !matchDiv) return;
            
            const checkMatch = () => {
                if (confirm.value.length > 0) {
                    if (confirm.value === password.value) {
                        matchDiv.textContent = '✅ Passwords match!';
                        matchDiv.className = 'field-success';
                        confirm.classList.remove('error');
                        confirm.classList.add('success');
                    } else {
                        matchDiv.textContent = '❌ Passwords do not match';
                        matchDiv.className = 'field-error';
                        confirm.classList.remove('success');
                        confirm.classList.add('error');
                    }
                } else {
                    matchDiv.textContent = '';
                    confirm.classList.remove('error', 'success');
                }
            };
            
            password.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);
        }
    };
    
    // =============================================
    // 13. KEYBOARD SHORTCUTS
    // =============================================
    const KeyboardShortcuts = {
        init() {
            document.addEventListener('keydown', (e) => {
                // Only work if not in input/textarea
                if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                    return;
                }
                
                const base = CONFIG.BASE_URL;
                
                // Alt + D = Dashboard
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = base + 'pages/dashboard.php';
                }
                // Alt + S = Subjects
                else if (e.altKey && e.key === 's') {
                    e.preventDefault();
                    window.location.href = base + 'pages/subjects.php';
                }
                // Alt + T = Tasks
                else if (e.altKey && e.key === 't') {
                    e.preventDefault();
                    window.location.href = base + 'pages/tasks.php';
                }
                // Alt + P = Profile
                else if (e.altKey && e.key === 'p') {
                    e.preventDefault();
                    window.location.href = base + 'profile.php';
                }
                // Escape = Close dropdowns
                else if (e.key === 'Escape') {
                    document.querySelectorAll('.show').forEach(el => {
                        el.classList.remove('show');
                    });
                }
            });
        }
    };
    
    // =============================================
    // 14. BACK TO TOP
    // =============================================
    const BackToTop = {
        init() {
            if (!DOM.backToTop) return;
            
            // Hide/show based on scroll
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    DOM.backToTop.style.display = 'flex';
                } else {
                    DOM.backToTop.style.display = 'none';
                }
            });
        }
    };
    
    // =============================================
    // 15. ALERT DISMISS
    // =============================================
    const AlertDismiss = {
        init() {
            document.querySelectorAll('.alert-dismissible .alert-close').forEach(btn => {
                btn.addEventListener('click', () => {
                    const alert = btn.closest('.alert');
                    if (alert) {
                        alert.style.transition = 'all 0.3s ease';
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateY(-20px)';
                        setTimeout(() => alert.remove(), 300);
                    }
                });
            });
        }
    };
    
    // =============================================
    // 16. AUTO DISMISS ALERTS (Auto-hide after 5 seconds)
    // =============================================
    const AutoDismissAlerts = {
        init() {
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-30px)';
                    setTimeout(() => {
                        if (alert.parentElement) {
                            alert.remove();
                        }
                    }, 500);
                }, 5000);
            });
        }
    };
    
    // =============================================
    // 17. PROGRESS BAR ANIMATION
    // =============================================
    const ProgressBarAnimation = {
        init() {
            document.querySelectorAll('.progress-bar-fill.animate-progress').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        }
    };
    
    // =============================================
    // 18. COLOR / ICON PICKER TOGGLE
    // =============================================
    const PickerToggle = {
        init() {
            // Color picker
            document.querySelectorAll('.color-option-mini input[type="radio"]').forEach(input => {
                input.addEventListener('change', function() {
                    const parent = this.closest('.color-option-mini');
                    if (parent) {
                        document.querySelectorAll('.color-option-mini').forEach(opt => {
                            opt.classList.remove('active');
                        });
                        parent.classList.add('active');
                    }
                });
            });
            
            // Icon picker
            document.querySelectorAll('.icon-option-mini input[type="radio"]').forEach(input => {
                input.addEventListener('change', function() {
                    const parent = this.closest('.icon-option-mini');
                    if (parent) {
                        document.querySelectorAll('.icon-option-mini').forEach(opt => {
                            opt.classList.remove('active');
                        });
                        parent.classList.add('active');
                    }
                });
            });
            
            // Set default active states
            document.querySelectorAll('.color-option-mini input:checked').forEach(input => {
                const parent = input.closest('.color-option-mini');
                if (parent) parent.classList.add('active');
            });
            
            document.querySelectorAll('.icon-option-mini input:checked').forEach(input => {
                const parent = input.closest('.icon-option-mini');
                if (parent) parent.classList.add('active');
            });
        }
    };
    
    // =============================================
    // 19. LIVE PREVIEW (Edit pages)
    // =============================================
    const LivePreview = {
        init() {
            // Subject name preview
            const nameInput = document.getElementById('subject_name');
            const previewBadge = document.querySelector('.page-badge');
            
            if (nameInput && previewBadge) {
                nameInput.addEventListener('input', () => {
                    previewBadge.textContent = nameInput.value || 'Untitled';
                });
            }
            
            // Task name preview
            const taskNameInput = document.getElementById('task_name');
            const taskPreviewBadge = document.querySelector('.page-badge');
            
            if (taskNameInput && taskPreviewBadge && !nameInput) {
                taskNameInput.addEventListener('input', () => {
                    taskPreviewBadge.textContent = taskNameInput.value || 'Untitled';
                });
            }
        }
    };
    
    // =============================================
    // 20. DATE VALIDATION (Tasks)
    // =============================================
    const DateValidation = {
        init() {
            const dueDate = document.getElementById('due_date');
            const startDate = document.getElementById('start_date');
            
            // Set min date for due date
            if (dueDate) {
                const today = new Date().toISOString().split('T')[0];
                dueDate.setAttribute('min', today);
                
                // Check if due date is in the past
                dueDate.addEventListener('change', function() {
                    const parent = this.closest('.form-group');
                    const existing = parent.querySelector('.form-hint.error');
                    
                    if (this.value < today && this.value !== '') {
                        if (!existing) {
                            const warning = document.createElement('small');
                            warning.className = 'form-hint error';
                            warning.textContent = '⚠️ Due date is in the past!';
                            parent.appendChild(warning);
                        }
                        this.classList.add('error');
                    } else {
                        if (existing) existing.remove();
                        this.classList.remove('error');
                    }
                });
            }
            
            // Set max date for start date
            if (startDate) {
                const today = new Date().toISOString().split('T')[0];
                startDate.setAttribute('max', today);
            }
        }
    };
    
    // =============================================
    // 21. LOADING SPINNER
    // =============================================
    const LoadingSpinner = {
        show() {
            if (DOM.loadingSpinner) {
                DOM.loadingSpinner.style.display = 'flex';
            }
        },
        
        hide() {
            if (DOM.loadingSpinner) {
                DOM.loadingSpinner.style.display = 'none';
            }
        },
        
        init() {
            // Hide on page load
            window.addEventListener('load', () => {
                this.hide();
            });
            
            // Show on navigation
            document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript:"])').forEach(link => {
                link.addEventListener('click', (e) => {
                    // Don't show for empty href or external links
                    const href = link.getAttribute('href');
                    if (href && !href.startsWith('http') && !href.startsWith('//')) {
                        // Small delay to let the link navigate
                        setTimeout(() => {
                            this.show();
                        }, 100);
                    }
                });
            });
        }
    };
    
    // =============================================
    // 22. SMOOTH SCROLL
    // =============================================
    const SmoothScroll = {
        init() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
    };
    
    // =============================================
    // 23. CONSOLE LOG (Development)
    // =============================================
    const DevLog = {
        init() {
            console.log(`🚀 ${CONFIG.APP_NAME} v2.0.0 loaded successfully!`);
            console.log(`📊 User ID: ${CONFIG.USER_ID || 'Not logged in'}`);
            console.log(`🌙 Theme: ${ThemeManager.getCurrent()}`);
            console.log(`📱 Browser: ${navigator.userAgent.split(' ').slice(-1)[0]}`);
            console.log('📚 StudyHub Pro is ready!');
        }
    };
    
    // =============================================
    // 24. INTERSECTION OBSERVER (Animations on scroll)
    // =============================================
    const ScrollAnimations = {
        init() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });
                
                document.querySelectorAll('.slide-up, .fade-in').forEach(el => {
                    observer.observe(el);
                });
            }
        }
    };
    
    // =============================================
    // 25. INITIALIZE ALL MODULES
    // =============================================
    try {
        // Core modules
        ThemeManager.init();
        MobileMenu.init();
        UserDropdown.init();
        NotificationSystem.init();
        KeyboardShortcuts.init();
        BackToTop.init();
        AlertDismiss.init();
        AutoDismissAlerts.init();
        LoadingSpinner.init();
        SmoothScroll.init();
        
        // Form modules
        PasswordStrength.init();
        FormValidator.init();
        FilterSystem.init();
        ConfirmPassword.init();
        DateValidation.init();
        
        // UI modules
        PickerToggle.init();
        LivePreview.init();
        ProgressBarAnimation.init();
        ScrollAnimations.init();
        
        // Dev
        DevLog.init();
        
    } catch (error) {
        console.error('❌ Error initializing modules:', error);
    }
    
    // =============================================
    // 26. CLEANUP ON PAGE UNLOAD
    // =============================================
    window.addEventListener('beforeunload', () => {
        NotificationSystem.destroy();
    });
    
}); // End DOMContentLoaded

// =============================================
// 27. EXPOSE FOR INLINE USE
// =============================================
// Some functions need to be accessible globally
window.togglePasswordVisibility = togglePasswordVisibility;
window.NotificationSystem = NotificationSystem;

// =============================================
// END OF FILE
// =============================================