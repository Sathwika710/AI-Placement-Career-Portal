document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle setup
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
        // Check current localstorage or system preference
        const currentTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', currentTheme);
        themeBtn.innerHTML = currentTheme === 'dark' ? '☀️' : '🌙';

        themeBtn.addEventListener('click', () => {
            const nowTheme = document.documentElement.getAttribute('data-theme');
            const nextTheme = nowTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            themeBtn.innerHTML = nextTheme === 'dark' ? '☀️' : '🌙';
        });
    }

    // Notification dropdown setup
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    if (bell && dropdown) {
        bell.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('active');
            
            // Mark notifications as read
            fetch('api_notifications.php?action=read')
                .then(res => res.json())
                .then(data => {
                    const badge = bell.querySelector('.notification-badge');
                    if (badge) badge.style.display = 'none';
                })
                .catch(err => console.error("Error clearing notifications: ", err));
        });

        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && e.target !== bell) {
                dropdown.classList.remove('active');
            }
            const switchDropdown = document.getElementById('role-switcher-dropdown');
            const switchBtn = document.getElementById('role-switcher-btn');
            if (switchDropdown && switchBtn && !switchDropdown.contains(e.target) && e.target !== switchBtn) {
                switchDropdown.classList.remove('active');
            }
        });
    } else {
        // Fallback for pages without notification bells (but with switcher)
        document.addEventListener('click', (e) => {
            const switchDropdown = document.getElementById('role-switcher-dropdown');
            const switchBtn = document.getElementById('role-switcher-btn');
            if (switchDropdown && switchBtn && !switchDropdown.contains(e.target) && e.target !== switchBtn) {
                switchDropdown.classList.remove('active');
            }
        });
    }
});

// Modal toggle helper
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}
