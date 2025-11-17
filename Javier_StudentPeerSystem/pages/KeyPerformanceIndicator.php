<?php
session_start();
require_once '../classes/database.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../login.php");
    exit();
}

$conn = new Database();
$pdo = $conn->connect();
$tutorUserId = $_SESSION['user_id'];

// Get tutor information
$stmt = $pdo->prepare("SELECT * FROM tutorprofiles WHERE user_id = ?");
$stmt->execute([$tutorUserId]);
$tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get time period filter
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$dateCondition = "";
switch($period) {
    case 'week':
        $dateCondition = "AND s.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'quarter':
        $dateCondition = "AND s.session_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
    case 'month':
    default:
        $dateCondition = "AND s.session_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
}

// Get KPI data
// Active Students
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.student_id) as count 
                       FROM enrollments e 
                       WHERE e.tutor_id = ? AND e.status = 'active'");
$stmt->execute([$tutorProfile['tutor_id']]);
$activeStudents = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total Sessions
$stmt = $pdo->prepare("SELECT COUNT(*) as count 
                       FROM sessions s 
                       JOIN enrollments e ON s.enrollment_id = e.enrollment_id 
                       WHERE e.tutor_id = ? AND s.status = 'completed' $dateCondition");
$stmt->execute([$tutorProfile['tutor_id']]);
$totalSessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total Hours
$stmt = $pdo->prepare("SELECT SUM(s.duration) as total 
                       FROM sessions s 
                       JOIN enrollments e ON s.enrollment_id = e.enrollment_id 
                       WHERE e.tutor_id = ? AND s.status = 'completed' $dateCondition");
$stmt->execute([$tutorProfile['tutor_id']]);
$totalHours = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Average Rating
$stmt = $pdo->prepare("SELECT AVG(r.rating) as avg_rating 
                       FROM ratings r 
                       JOIN enrollments e ON r.enrollment_id = e.enrollment_id 
                       WHERE e.tutor_id = ?");
$stmt->execute([$tutorProfile['tutor_id']]);
$avgRating = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0, 1);

// Completion Rate
$stmt = $pdo->prepare("SELECT 
                       SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed,
                       COUNT(*) as total
                       FROM sessions s 
                       JOIN enrollments e ON s.enrollment_id = e.enrollment_id 
                       WHERE e.tutor_id = ? $dateCondition");
$stmt->execute([$tutorProfile['tutor_id']]);
$sessionStats = $stmt->fetch(PDO::FETCH_ASSOC);
$completionRate = $sessionStats['total'] > 0 ? round(($sessionStats['completed'] / $sessionStats['total']) * 100) : 0;

// Total Earnings
$totalEarnings = $totalHours * ($tutorProfile['hourly_rate'] ?? 0);

// Get student list with session counts
$stmt = $pdo->prepare("SELECT 
                       u.user_id, u.first_name, u.last_name, u.email,
                       e.status,
                       COUNT(s.session_id) as session_count
                       FROM enrollments e
                       JOIN users u ON e.student_id = u.user_id
                       LEFT JOIN sessions s ON e.enrollment_id = s.enrollment_id AND s.status = 'completed'
                       WHERE e.tutor_id = ?
                       GROUP BY u.user_id, u.first_name, u.last_name, u.email, e.status
                       ORDER BY session_count DESC");
$stmt->execute([$tutorProfile['tutor_id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get weekly session data for chart
$stmt = $pdo->prepare("SELECT 
                       WEEK(s.session_date) as week_num,
                       COUNT(*) as session_count
                       FROM sessions s
                       JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                       WHERE e.tutor_id = ? AND s.status = 'completed'
                       AND s.session_date >= DATE_SUB(CURDATE(), INTERVAL 5 WEEK)
                       GROUP BY WEEK(s.session_date)
                       ORDER BY week_num");
$stmt->execute([$tutorProfile['tutor_id']]);
$weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Dashboard - Student Peer Mentorship</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 1.1em;
        }

        .tutor-info {
            text-align: right;
        }

        .tutor-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .tutor-rate {
            color: #667eea;
            font-weight: 600;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .time-period {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .time-period select {
            padding: 10px 20px;
            font-size: 1em;
            border: 2px solid #667eea;
            border-radius: 8px;
            cursor: pointer;
            background: white;
            color: #667eea;
            font-weight: 600;
        }

        .refresh-btn {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }

        .refresh-btn:hover {
            transform: scale(1.05);
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .kpi-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .kpi-title {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .kpi-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
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
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .students-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .student-list {
            display: grid;
            gap: 15px;
        }

        .student-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: transform 0.2s;
        }

        .student-card:hover {
            transform: translateX(5px);
            background: #e9ecef;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
            font-weight: bold;
        }

        .student-info h3 {
            color: #333;
            margin-bottom: 5px;
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
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 2px;
        }

        .badge-sessions {
            background: #d4edda;
            color: #155724;
        }

        .badge-status {
            background: #cce5ff;
            color: #004085;
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
            }

            .student-avatar {
                margin: 0 auto;
            }

            .student-stats {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tutor_dashboard.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <div class="header-left">
                <h1>📊 Tutor Performance Dashboard</h1>
                <p>Track your mentorship impact and student progress</p>
            </div>
            <div class="tutor-info">
                <div class="tutor-name"><?php echo htmlspecialchars($tutorProfile['first_name'] . ' ' . $tutorProfile['last_name']); ?></div>
                <div class="tutor-rate">$<?php echo number_format($tutorProfile['hourly_rate'] ?? 0, 2); ?>/hr</div>
            </div>
        </div>

        <div class="time-period">
            <form method="GET" action="" style="display: flex; align-items: center; gap: 15px;">
                <label for="period" style="font-weight: 600; color: #667eea;">Time Period:</label>
                <select id="period" name="period" onchange="this.form.submit()">
                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                    <option value="quarter" <?php echo $period === 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
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
                <div class="kpi-value"><?php echo $totalSessions; ?></div>
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
                <div class="kpi-value"><?php echo $avgRating; ?></div>
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
                    <p style="text-align: center; color: #666;">No students enrolled yet.</p>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <?php 
                        $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
                        ?>
                        <div class="student-card">
                            <div class="student-avatar"><?php echo $initials; ?></div>
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                                <p><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                            <div class="student-stats">
                                <span class="stat-badge badge-sessions"><?php echo $student['session_count']; ?> sessions</span>
                                <span class="stat-badge badge-status"><?php echo htmlspecialchars($student['status']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>