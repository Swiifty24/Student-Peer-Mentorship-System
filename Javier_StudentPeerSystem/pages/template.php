<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? "Default Title";
$isTutor = $_SESSION['isTutorNow'] ?? false;
$userFirstName = $_SESSION['first_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | PeerMentor</title>
    <link href="../styles/styles.css" rel="stylesheet">
    <link href="../styles/components.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>
    <header>
        <h1><a href="findTutor.php" style="text-decoration: none; color: inherit;">PeerMentor Connect</a></h1>
        <nav>
            <div class="notification-bell" id="notificationBell">
                <span class="bell-icon">🔔</span>
                <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read" id="markAllRead">Mark all read</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="no-notifications">Loading notifications...</div>
                    </div>
                </div>
            </div>

            <?php if ($isTutor): ?>
                <a href="findTutor.php" class="nav-button tertiary-button">Student View</a>
                <a href="tutorRequests.php" class="nav-button primary-button">View Requests</a>
                <a href="setupTutorProfile.php" class="nav-button tertiary-button">Update Profile</a>
                <a href="toggleRole.php?role=tutor&status=0" class="nav-button danger-button">Deactivate Role</a>
            <?php endif; ?>
            <button id="logoutButton" class="nav-button danger-button">Log Out</button>
        </nav>
    </header>

    <div class="container">
        <?php echo $pageContent ?? ''; ?>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeLogoutModal">&times;</span>
            <h3>Confirm Log Out</h3>
            <p>Are you sure you want to end your session?</p>
            <div class="modal-actions">
                <button id="confirmLogout" class="cta-button danger-button">Yes, Log Out</button>
                <button id="cancelLogout" class="tertiary-button">No, Stay Here</button>
            </div>
        </div>
    </div>

    <script>
        // NOTIFICATION SYSTEM
        let notificationsData = [];
        let notificationInterval;

        function loadNotifications() {
            fetch('getNotifications.php')
                .then(response => {
                    if (response.status === 401) {
                        if (notificationInterval) {
                            clearInterval(notificationInterval);
                        }
                        return null;
                    }
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        notificationsData = data.notifications;
                        updateNotificationUI(data.notifications, data.unreadCount);
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadNotifications();
            notificationInterval = setInterval(loadNotifications, 30000);

            const bellIcon = document.getElementById('notificationBell');
            const dropdown = document.getElementById('notificationDropdown');

            bellIcon.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            document.addEventListener('click', function (e) {
                if (!bellIcon.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            document.getElementById('markAllRead').addEventListener('click', function () {
                markAllAsRead();
            });

            // LOGOUT MODAL
            const logoutModal = document.getElementById('logoutModal');
            const logoutButton = document.getElementById('logoutButton');
            const confirmLogout = document.getElementById('confirmLogout');
            const cancelLogout = document.getElementById('cancelLogout');
            const closeModal = document.getElementById('closeLogoutModal');

            if (logoutButton) {
                logoutButton.addEventListener('click', function () {
                    logoutModal.style.display = 'flex';
                });
            }

            [closeModal, cancelLogout].forEach(element => {
                if (element) {
                    element.addEventListener('click', function () {
                        logoutModal.style.display = 'none';
                    });
                }
            });

            if (confirmLogout) {
                confirmLogout.addEventListener('click', function () {
                    window.location.href = 'logout.php';
                });
            }

            window.onclick = function (event) {
                if (event.target === logoutModal) {
                    logoutModal.style.display = "none";
                }
            }
        });

        function updateNotificationUI(notifications, unreadCount) {
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');

            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }

            if (notifications.length === 0) {
                list.innerHTML = '<div class="no-notifications">No notifications yet</div>';
                return;
            }

            list.innerHTML = notifications.map(notif => `
                <div class="notification-item ${notif.isRead == 0 ? 'unread' : ''}" 
                     onclick="markAsRead(${notif.notificationID})">
                    <span class="notification-icon">${getNotificationIcon(notif.type)}</span>
                    <div class="notification-content">
                        <div class="notification-message">${escapeHtml(notif.message)}</div>
                        <div class="notification-time">${formatTime(notif.createdAt)}</div>
                    </div>
                </div>
            `).join('');
        }

        function markAsRead(notificationID) {
            fetch('getNotifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notificationID=${notificationID}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                });
        }

        function markAllAsRead() {
            fetch('getNotifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                });
        }

        function getNotificationIcon(type) {
            const icons = {
                'request': '📩',
                'confirmation': '✅',
                'completion': '🎓',
                'cancellation': '❌',
                'message': '💬',
                'system': '🔔'
            };
            return icons[type] || '🔔';
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} min ago`;

            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;

            const diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

            return date.toLocaleDateString();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>