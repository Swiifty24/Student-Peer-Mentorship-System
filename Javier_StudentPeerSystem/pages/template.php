<?php 
// template.php - FINAL REVISION (All Tutor Actions in Navbar)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? "Default Title";
// Use session variables for dynamic links
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <style>
        /* CSS FIX: Ensure the modal is hidden by default */
        #logoutModal {
            display: none; 
        }
        .modal { 
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); 
            align-items: center; 
            justify-content: center;
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="findTutor.php" style="text-decoration: none; color: inherit;">PeerMentor Connect</a></h1>
        <nav>
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
            <div class="modal-actions" style="margin-top: 20px; display: flex; justify-content: space-around;">
                <button id="confirmLogout" class="cta-button danger-button">Yes, Log Out</button>
                <button id="cancelLogout" class="tertiary-button">No, Stay Here</button>
            </div>
        </div>
    </div>
    
    <script>
        // JAVASCRIPT FOR LOGOUT MODAL LOGIC (retained)
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            const logoutButton = document.getElementById('logoutButton');
            const confirmLogout = document.getElementById('confirmLogout');
            const cancelLogout = document.getElementById('cancelLogout');
            const closeModal = document.getElementById('closeLogoutModal');
            
            if (logoutButton) {
                logoutButton.addEventListener('click', function() {
                    logoutModal.style.display = 'flex'; 
                });
            }

            const closeActions = [closeModal, cancelLogout];
            closeActions.forEach(element => {
                if (element) {
                    element.addEventListener('click', function() {
                        logoutModal.style.display = 'none'; 
                    });
                }
            });

            if (confirmLogout) {
                confirmLogout.addEventListener('click', function() {
                    window.location.href = 'logout.php';
                });
            }
            
            window.onclick = function(event) {
                if (event.target === logoutModal) {
                    logoutModal.style.display = "none";
                }
            }
        });
    </script>
</body>
</html>