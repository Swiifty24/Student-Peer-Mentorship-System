<?php
session_start();
require_once '../classes/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isTutorNow']) || $_SESSION['isTutorNow'] != 1) {
    header("Location: login.php");
    exit();
}

$conn = new Database();
$pdo = $conn->connect();
$tutorUserId = $_SESSION['user_id'];

// Get tutor information - FIXED: Use correct column names (camelCase)
$stmt = $pdo->prepare("SELECT tp.*, u.firstName, u.lastName 
                       FROM tutorprofiles tp 
                       JOIN users u ON tp.userID = u.userID
                       WHERE tp.userID = ?");
$stmt->execute([$tutorUserId]);
$tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutorProfile) {
    die("Tutor profile not found. Please set up your profile first.");
}

// Get time period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$dateCondition = "";
switch($period) {
    case 'week':
        $dateCondition = "AND s.sessionDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'quarter':
        $dateCondition = "AND s.sessionDate >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
    case 'year':
        $dateCondition = "AND s.sessionDate >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    case 'all':
        $dateCondition = "";
        break;
    case 'month':
    default:
        $dateCondition = "AND s.sessionDate >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
}

// FIXED: Active Students
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.studentUserID) as count 
                       FROM enrollments e 
                       LEFT JOIN sessions s ON e.enrollmentID = s.enrollmentID
                       WHERE e.tutorUserID = ? AND e.status IN (0, 1) $dateCondition");
$stmt->execute([$tutorUserId]);
$activeStudents = $stmt->fetchColumn() ?: 0;

// FIXED: Sessions Completed
$stmt = $pdo->prepare("SELECT COUNT(s.sessionID) as count 
                       FROM sessions s
                       JOIN enrollments e ON s.enrollmentID = e.enrollmentID
                       WHERE e.tutorUserID = ? AND s.status = 'Completed' $dateCondition");
$stmt->execute([$tutorUserId]);
$sessionsCompleted = $stmt->fetchColumn() ?: 0;

// FIXED: Total Hours
$stmt = $pdo->prepare("SELECT SUM(s.duration) as total 
                       FROM sessions s
                       JOIN enrollments e ON s.enrollmentID = e.enrollmentID
                       WHERE e.tutorUserID = ? AND s.status = 'Completed' $dateCondition");
$stmt->execute([$tutorUserId]);
$totalHours = $stmt->fetchColumn() ?: 0;

// FIXED: Average Rating (using reviews table correctly)
$reviewDateCondition = str_replace('s.sessionDate', 'r.reviewDate', $dateCondition);
$stmt = $pdo->prepare("SELECT AVG(r.rating) as avg_rating 
                       FROM reviews r
                       WHERE r.tutorUserID = ? $reviewDateCondition");
$stmt->execute([$tutorUserId]);
$averageRating = round($stmt->fetchColumn() ?: 0, 1);

// FIXED: Completion Rate
$stmt = $pdo->prepare("SELECT 
                       SUM(CASE WHEN s.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                       COUNT(*) as total
                       FROM sessions s 
                       JOIN enrollments e ON s.enrollmentID = e.enrollmentID 
                       WHERE e.tutorUserID = ? $dateCondition");
$stmt->execute([$tutorUserId]);
$sessionStats = $stmt->fetch(PDO::FETCH_ASSOC);
$completionRate = $sessionStats['total'] > 0 ? round(($sessionStats['completed'] / $sessionStats['total']) * 100) : 0;

// FIXED: Total Earnings
$totalEarnings = $totalHours * ($tutorProfile['hourlyRate'] ?? 0);

// FIXED: Get list of students with correct column names
$stmt = $pdo->prepare("SELECT u.firstName, u.lastName, u.email, 
                       COUNT(s.sessionID) as session_count, 
                       MAX(e.status) as status_code
                       FROM users u
                       JOIN enrollments e ON u.userID = e.studentUserID
                       LEFT JOIN sessions s ON e.enrollmentID = s.enrollmentID AND s.status = 'Completed'
                       WHERE e.tutorUserID = ?
                       GROUP BY u.userID, u.firstName, u.lastName, u.email
                       ORDER BY session_count DESC");
$stmt->execute([$tutorUserId]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get weekly session data for potential chart
$stmt = $pdo->prepare("SELECT 
                       WEEK(s.sessionDate) as week_num,
                       COUNT(*) as session_count
                       FROM sessions s
                       JOIN enrollments e ON s.enrollmentID = e.enrollmentID
                       WHERE e.tutorUserID = ? AND s.status = 'Completed'
                       AND s.sessionDate >= DATE_SUB(CURDATE(), INTERVAL 5 WEEK)
                       GROUP BY WEEK(s.sessionDate)
                       ORDER BY week_num");
$stmt->execute([$tutorUserId]);
$weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - PeerMentor</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../styles/reports.css" rel="stylesheet">
</head>
<body class="report-page">
    <div class="report-container">
        <a href="tutorRequests.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <div class="header-left">
                <h1>📊 Performance Dashboard</h1>
                <p>Track your mentorship impact and student progress</p>
            </div>
            <div class="tutor-info">
                <div class="tutor-name"><?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></div>
                <div class="tutor-rate">$<?php echo number_format($tutorProfile['hourlyRate'] ?? 0, 2); ?>/hr</div>
            </div>
        </div>

        <div class="time-period">
            <form method="GET" action="" style="display: flex; align-items: center; gap: 15px;">
                <label for="period" style="font-weight: 600; color: #667eea;">Time Period:</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                    <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                </select>
            </form>
            <button class="refresh-btn" onclick="window.location.reload()">🔄 Refresh Data</button>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon">👥</div>
                <div class="kpi-title">Active Students</div>
                <div class="kpi-value"><?php echo $activeStudents; ?></div>
                <div class="kpi-subtitle">Currently enrolled</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">📅</div>
                <div class="kpi-title">Total Sessions</div>
                <div class="kpi-value"><?php echo $sessionsCompleted; ?></div>
                <div class="kpi-subtitle">Completed this period</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-title">Total Hours</div>
                <div class="kpi-value"><?php echo number_format($totalHours, 1); ?></div>
                <div class="kpi-subtitle">Teaching time</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">⭐</div>
                <div class="kpi-title">Average Rating</div>
                <div class="kpi-value"><?php echo $averageRating; ?></div>
                <div class="kpi-subtitle">Student feedback</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">✅</div>
                <div class="kpi-title">Completion Rate</div>
                <div class="kpi-value"><?php echo $completionRate; ?>%</div>
                <div class="kpi-subtitle">Sessions completed</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">💰</div>
                <div class="kpi-title">Total Earnings</div>
                <div class="kpi-value">$<?php echo number_format($totalEarnings, 2); ?></div>
                <div class="kpi-subtitle">This period</div>
            </div>
        </div>

        <div class="students-section">
            <h2>👥 My Students</h2>
            <div class="student-list">
                <?php if (empty($students)): ?>
                    <div class="no-students">
                        <div class="no-students-icon">📚</div>
                        <p>No students enrolled yet. Start accepting requests to build your mentorship network!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    require_once '../classes/enrollments.php';
                    $enrollmentManager = new Enrollment();

                    foreach ($students as $student): 
                        $initials = strtoupper(substr($student['firstName'], 0, 1) . substr($student['lastName'], 0, 1));
                        $statusString = $enrollmentManager->getStatusString($student['status_code']);
                    ?>
                        <div class="student-card">
                            <div class="student-avatar"><?php echo $initials; ?></div>
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?></h3>
                                <p><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                            <div class="student-stats">
                                <span class="stat-badge badge-sessions"><?php echo $student['session_count']; ?> sessions</span>
                                <span class="stat-badge badge-status"><?php echo htmlspecialchars($statusString); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>