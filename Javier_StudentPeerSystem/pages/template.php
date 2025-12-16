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

    <!-- Force logout button styling + Modal styles -->
    <style>
        /* Logout Button */
        .nav-button.danger-button,
        #logoutButton {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.4) !important;
            font-weight: 600 !important;
            padding: 10px 24px !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            font-size: 0.95rem !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
        }

        .nav-button.danger-button:hover,
        #logoutButton:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.6) !important;
        }

        /* Modal Styles - CRITICAL FOR LOGOUT TO WORK */
        .modal {
            display: none;
            /* JavaScript will change this to 'flex' */
            position: fixed !important;
            z-index: 10000 !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.7) !important;
            justify-content: center !important;
            align-items: center !important;
        }

        .modal-content {
            background-color: white !important;
            padding: 30px !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
            max-width: 400px !important;
            width: 90% !important;
            position: relative !important;
            animation: slideIn 0.3s ease !important;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal .close {
            position: absolute !important;
            top: 15px !important;
            right: 20px !important;
            font-size: 28px !important;
            font-weight: bold !important;
            color: #666 !important;
            cursor: pointer !important;
            line-height: 1 !important;
        }

        .modal .close:hover {
            color: #e74c3c !important;
        }

        .modal-content h3 {
            margin-top: 0 !important;
            color: #333 !important;
            margin-bottom: 15px !important;
            font-size: 1.5rem !important;
        }

        .modal-content p {
            color: #666 !important;
            margin-bottom: 25px !important;
            line-height: 1.6 !important;
        }

        .modal-actions {
            display: flex !important;
            gap: 15px !important;
            justify-content: flex-end !important;
        }

        .modal-actions button {
            padding: 10px 20px !important;
            border: none !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            transition: all 0.3s !important;
        }
    </style>
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
                <a href="printables.php" class="nav-button tertiary-button">📄 Printables</a>
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

            console.log('Logout elements:', { logoutButton, logoutModal, confirmLogout });

            if (logoutButton) {
                logoutButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Logout button clicked!');

                    // Try multiple methods to display the modal
                    logoutModal.style.setProperty('display', 'flex', 'important');
                    logoutModal.style.visibility = 'visible';
                    logoutModal.style.opacity = '1';
                    logoutModal.style.zIndex = '99999';

                    console.log('Modal display set to:', logoutModal.style.display);
                    console.log('Computed display:', window.getComputedStyle(logoutModal).display);
                });
            } else {
                console.error('Logout button not found!');
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