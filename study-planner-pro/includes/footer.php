        <!-- =============================================
        END MAIN CONTENT
        ============================================= -->
        </main>
        
        <!-- =============================================
        FOOTER
        ============================================= -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="footer-logo">📚 StudyHub</span>
                    <span class="footer-version">v<?php echo defined('APP_VERSION') ? APP_VERSION : '2.0'; ?></span>
                </div>
                <div class="footer-center">
                    <p>&copy; <?php echo date('Y'); ?> StudyHub Pro. Built with ❤️ for HNDIT Portfolio</p>
                </div>
                <div class="footer-right">
                    <a href="#" onclick="window.scrollTo({top:0,behavior:'smooth'}); return false;" 
                       class="back-to-top" title="Back to Top">
                        <i class="fas fa-arrow-up"></i>
                    </a>
                </div>
            </div>
        </footer>
        
    </div>
    <!-- =============================================
    END CONTAINER
    ============================================= -->
    
    <!-- =============================================
    JAVASCRIPT
    ============================================= -->
    <script src="<?php echo BASE_URL; ?>public/js/script.js"></script>
    
    <!-- =============================================
    ADDITIONAL SCRIPTS
    ============================================= -->
    <script>
        // =============================================
        // CONFIGURATION
        // =============================================
        const BASE_URL = '<?php echo BASE_URL; ?>';
        const APP_NAME = '<?php echo defined('APP_NAME') ? APP_NAME : 'StudyHub'; ?>';
        const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
        const USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        
        // =============================================
        // THEME TOGGLE (Dark/Light)
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            // Check saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                themeIcon.className = 'fas fa-sun';
            }
            
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        });
        
        // =============================================
        // MOBILE MENU TOGGLE
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.getElementById('mobileToggle');
            const mainNav = document.getElementById('mainNav');
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('mobile-open');
                    const icon = this.querySelector('i');
                    icon.className = mainNav.classList.contains('mobile-open') 
                        ? 'fas fa-times' 
                        : 'fas fa-bars';
                });
            }
        });
        
        // =============================================
        // USER DROPDOWN TOGGLE
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const userToggle = document.getElementById('userToggle');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userToggle) {
                userToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('show');
                });
                
                document.addEventListener('click', function() {
                    userDropdown.classList.remove('show');
                });
                
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // =============================================
        // NOTIFICATIONS DROPDOWN
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const notificationToggle = document.getElementById('notificationToggle');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationToggle) {
                notificationToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('show');
                    // Load notifications when opened
                    if (notificationDropdown.classList.contains('show')) {
                        loadNotifications();
                    }
                });
                
                document.addEventListener('click', function() {
                    notificationDropdown.classList.remove('show');
                });
                
                notificationDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
        
        // =============================================
        // LOAD NOTIFICATIONS (AJAX)
        // =============================================
        function loadNotifications() {
            const list = document.getElementById('notificationList');
            const badge = document.getElementById('notificationBadge');
            
            if (!list) return;
            
            fetch(BASE_URL + 'pages/ajax.php?action=get_notifications')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update badge
                        if (badge) {
                            const count = data.unread_count || 0;
                            badge.textContent = count;
                            badge.style.display = count > 0 ? 'flex' : 'none';
                        }
                        
                        // Update list
                        if (data.notifications && data.notifications.length > 0) {
                            list.innerHTML = data.notifications.map(n => `
                                <div class="notification-item ${n.is_read ? 'read' : 'unread'}">
                                    <div class="notification-icon">${n.icon || '📌'}</div>
                                    <div class="notification-content">
                                        <div class="notification-title">${n.title}</div>
                                        <div class="notification-message">${n.message}</div>
                                        <div class="notification-time">${n.time_ago}</div>
                                    </div>
                                    ${!n.is_read ? `<button class="mark-read" data-id="${n.id}">✓</button>` : ''}
                                </div>
                            `).join('');
                        } else {
                            list.innerHTML = `
                                <div class="notification-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No notifications</p>
                                </div>
                            `;
                        }
                    }
                })
                .catch(err => console.error('Error loading notifications:', err));
        }
        
        // =============================================
        // MARK NOTIFICATION AS READ
        // =============================================
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('mark-read')) {
                const id = e.target.dataset.id;
                fetch(BASE_URL + 'pages/ajax.php?action=mark_read&id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            loadNotifications();
                        }
                    });
            }
        });
        
        // =============================================
        // MARK ALL NOTIFICATIONS AS READ
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const markAllBtn = document.getElementById('markAllRead');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', function() {
                    fetch(BASE_URL + 'pages/ajax.php?action=mark_all_read')
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                loadNotifications();
                            }
                        });
                });
            }
        });
        
        // =============================================
        // AUTO REFRESH NOTIFICATIONS (Every 60 seconds)
        // =============================================
        setInterval(function() {
            if (document.getElementById('notificationDropdown')?.classList.contains('show')) {
                loadNotifications();
            }
        }, 60000);
        
        // =============================================
        // KEYBOARD SHORTCUTS
        // =============================================
        document.addEventListener('keydown', function(e) {
            // Alt + D = Dashboard
            if (e.altKey && e.key === 'd') {
                window.location.href = BASE_URL + 'pages/dashboard.php';
            }
            // Alt + S = Subjects
            if (e.altKey && e.key === 's') {
                window.location.href = BASE_URL + 'pages/subjects.php';
            }
            // Alt + T = Tasks
            if (e.altKey && e.key === 't') {
                window.location.href = BASE_URL + 'pages/tasks.php';
            }
            // Alt + P = Profile
            if (e.altKey && e.key === 'p') {
                window.location.href = BASE_URL + 'profile.php';
            }
            // Escape = Close dropdowns
            if (e.key === 'Escape') {
                document.querySelectorAll('.show').forEach(el => el.classList.remove('show'));
            }
        });
        
        // =============================================
        // LOADING SPINNER
        // =============================================
        window.addEventListener('beforeunload', function() {
            const spinner = document.getElementById('loading-spinner');
            if (spinner) spinner.style.display = 'flex';
        });
        
        console.log('🚀 ' + APP_NAME + ' loaded successfully!');
        console.log('📊 User ID: ' + (USER_ID || 'Not logged in'));
    </script>
</body>
</html>