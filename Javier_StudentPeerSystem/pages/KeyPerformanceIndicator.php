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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-left h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .header-left p {
            color: #666;
            font-size: 1.1em;
        }

        .tutor-info {
            text-align: right;
        }

        .tutor-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .tutor-rate {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .time-period {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .time-period select {
            padding: 12px 24px;
            font-size: 1em;
            border: 2px solid #667eea;
            border-radius: 10px;
            cursor: pointer;
            background: white;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .time-period select:hover {
            background: #f8f9ff;
        }

        .refresh-btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: scaleIn 0.5s ease;
            animation-fill-mode: both;
        }

        .kpi-card:nth-child(1) { animation-delay: 0.1s; }
        .kpi-card:nth-child(2) { animation-delay: 0.2s; }
        .kpi-card:nth-child(3) { animation-delay: 0.3s; }
        .kpi-card:nth-child(4) { animation-delay: 0.4s; }
        .kpi-card:nth-child(5) { animation-delay: 0.5s; }
        .kpi-card:nth-child(6) { animation-delay: 0.6s; }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .kpi-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .kpi-icon {
            font-size: 3em;
            margin-bottom: 15px;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .kpi-title {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 3em;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .kpi-subtitle {
            font-size: 0.85em;
            color: #888;
            margin-top: 5px;
        }

        .students-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            animation: fadeIn 0.8s ease;
        }

        .students-section h2 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            font-size: 2em;
            font-weight: 700;
        }

        .student-list {
            display: grid;
            gap: 15px;
        }

        .student-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .student-card:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            border-left-width: 8px;
        }

        .student-avatar {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.6em;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .student-info h3 {
            color: #333;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .student-info p {
            color: #666;
            font-size: 0.9em;
        }

        .student-stats {
            text-align: right;
        }

        .stat-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .stat-badge:hover {
            transform: scale(1.05);
        }

        .badge-sessions {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .badge-status {
            background: linear-gradient(135deg, #cce5ff, #b8daff);
            color: #004085;
        }

        .no-students {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .no-students-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .tutor-info {
                text-align: center;
                margin-top: 15px;
            }

            .header-left h1 {
                font-size: 1.8em;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .student-card {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }

            .student-avatar {
                margin: 0 auto;
            }

            .student-stats {
                text-align: center;
            }

            .time-period {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
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