<?php
session_start();
require_once '../classes/database.class.php';

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

// Get filter parameters
$reportType = isset($_GET['type']) ? $_GET['type'] : null;
$dateRange = isset($_GET['range']) ? $_GET['range'] : 'month';
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$courseId = isset($_GET['course']) ? $_GET['course'] : 'all';

// Get course list for filter
$stmt = $pdo->prepare("SELECT DISTINCT c.course_id, c.course_name 
                       FROM courses c
                       JOIN enrollments e ON c.course_id = e.course_id
                       WHERE e.tutor_id = ?
                       ORDER BY c.course_name");
$stmt->execute([$tutorProfile['tutor_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to generate report data based on type
function generateReportData($pdo, $tutorId, $reportType, $startDate, $endDate, $courseId) {
    $reportData = ['summary' => [], 'tableHeaders' => [], 'tableData' => []];
    
    $courseCondition = $courseId !== 'all' ? "AND c.course_id = ?" : "";
    $params = [$tutorId, $startDate, $endDate];
    if ($courseId !== 'all') $params[] = $courseId;
    
    switch($reportType) {
        case 'sessions':
            $reportData['title'] = 'Session Report';
            
            // Summary stats
            $stmt = $pdo->prepare("SELECT 
                                  COUNT(*) as total,
                                  SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed,
                                  SUM(CASE WHEN s.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                                  AVG(s.duration) as avg_duration
                                  FROM sessions s
                                  JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                                  JOIN courses c ON e.course_id = c.course_id
                                  WHERE e.tutor_id = ? 
                                  AND s.session_date BETWEEN ? AND ? $courseCondition");
            $stmt->execute($params);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reportData['summary'] = [
                'Total Sessions' => $summary['total'],
                'Completed' => $summary['completed'],
                'Cancelled' => $summary['cancelled'],
                'Avg Duration' => round($summary['avg_duration'], 1) . ' hrs'
            ];
            
            // Session details
            $stmt = $pdo->prepare("SELECT 
                                  s.session_date, s.start_time,
                                  u.first_name, u.last_name,
                                  c.course_name,
                                  s.duration,
                                  s.status
                                  FROM sessions s
                                  JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                                  JOIN users u ON e.student_id = u.user_id
                                  JOIN courses c ON e.course_id = c.course_id
                                  WHERE e.tutor_id = ? 
                                  AND s.session_date BETWEEN ? AND ? $courseCondition
                                  ORDER BY s.session_date DESC
                                  LIMIT 50");
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reportData['tableHeaders'] = ['Date', 'Time', 'Student', 'Course', 'Duration', 'Status'];
            $reportData['tableData'] = array_map(function($s) {
                return [
                    date('M d, Y', strtotime($s['session_date'])),
                    $s['start_time'],
                    $s['first_name'] . ' ' . $s['last_name'],
                    $s['course_name'],
                    $s['duration'] . ' hrs',
                    ucfirst($s['status'])
                ];
            }, $sessions);
            break;
            
        case 'students':
            $reportData['title'] = 'Student Progress Report';
            
            $stmt = $pdo->prepare("SELECT 
                                  COUNT(DISTINCT e.student_id) as total_students,
                                  SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active,
                                  AVG(r.rating) as avg_rating
                                  FROM enrollments e
                                  JOIN courses c ON e.course_id = c.course_id
                                  LEFT JOIN ratings r ON e.enrollment_id = r.enrollment_id
                                  WHERE e.tutor_id = ? $courseCondition");
            $stmt->execute([$tutorId] + ($courseId !== 'all' ? [$courseId] : []));
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $reportData['summary'] = [
                'Total Students' => $summary['total_students'],
                'Active' => $summary['active'],
                'Avg Rating' => round($summary['avg_rating'] ?? 0, 1),
                'Completion Rate' => '92%' // Calculate from actual data
            ];
            
            $stmt = $pdo->prepare("SELECT 
                                  u.first_name, u.last_name,
                                  COUNT(s.session_id) as session_count,
                                  AVG(r.rating) as avg_rating,
                                  e.status
                                  FROM enrollments e
                                  JOIN users u ON e.student_id = u.user_id
                                  JOIN courses c ON e.course_id = c.course_id
                                  LEFT JOIN sessions s ON e.enrollment_id = s.enrollment_id AND s.status = 'completed'
                                  LEFT JOIN ratings r ON e.enrollment_id = r.enrollment_id
                                  WHERE e.tutor_id = ? $courseCondition
                                  GROUP BY u.user_id
                                  ORDER BY session_count DESC");
            $stmt->execute([$tutorId] + ($courseId !== 'all' ? [$courseId] : []));
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reportData['tableHeaders'] = ['Student', 'Sessions', 'Progress', 'Rating', 'Status'];
            $reportData['tableData'] = array_map(function($s) {
                $rating = round($s['avg_rating'] ?? 0);
                return [
                    $s['first_name'] . ' ' . $s['last_name'],
                    $s['session_count'],
                    '85%', // Calculate actual progress
                    str_repeat('⭐', $rating),
                    ucfirst($s['status'])
                ];
            }, $students);
            break;
            
        case 'earnings':
            $reportData['title'] = 'Earnings Report';
            
            $stmt = $pdo->prepare("SELECT 
                                  SUM(s.duration) as total_hours,
                                  COUNT(s.session_id) as total_sessions
                                  FROM sessions s
                                  JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                                  JOIN courses c ON e.course_id = c.course_id
                                  WHERE e.tutor_id = ? 
                                  AND s.status = 'completed'
                                  AND s.session_date BETWEEN ? AND ? $courseCondition");
            $stmt->execute($params);
            $earnings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $hourlyRate = 30; // Get from tutor profile
            $totalEarnings = ($earnings['total_hours'] ?? 0) * $hourlyRate;
            
            $reportData['summary'] = [
                'Total Earnings' => '$' . number_format($totalEarnings, 2),
                'Total Hours' => round($earnings['total_hours'] ?? 0, 1),
                'Avg Rate' => '$' . $hourlyRate . '/hr',
                'Sessions' => $earnings['total_sessions']
            ];
            
            $stmt = $pdo->prepare("SELECT 
                                  s.session_date,
                                  u.first_name, u.last_name,
                                  s.duration,
                                  c.course_name
                                  FROM sessions s
                                  JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                                  JOIN users u ON e.student_id = u.user_id
                                  JOIN courses c ON e.course_id = c.course_id
                                  WHERE e.tutor_id = ? 
                                  AND s.status = 'completed'
                                  AND s.session_date BETWEEN ? AND ? $courseCondition
                                  ORDER BY s.session_date DESC
                                  LIMIT 50");
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $reportData['tableHeaders'] = ['Date', 'Student', 'Hours', 'Rate', 'Amount'];
            $reportData['tableData'] = array_map(function($s) use ($hourlyRate) {
                $amount = $s['duration'] * $hourlyRate;
                return [
                    date('M d, Y', strtotime($s['session_date'])),
                    $s['first_name'] . ' ' . $s['last_name'],
                    $s['duration'],
                    '$' . $hourlyRate,
                    '$' . number_format($amount, 2)
                ];
            }, $sessions);
            break;
            
        default:
            $reportData['title'] = 'Select a Report Type';
            $reportData['summary'] = [];
            $reportData['tableHeaders'] = [];
            $reportData['tableData'] = [];
    }
    
    return $reportData;
}

$reportData = null;
if ($reportType) {
    $reportData = generateReportData($pdo, $tutorProfile['tutor_id'], $reportType, $startDate, $endDate, $courseId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Student Peer Mentorship</title>
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
        }

        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
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

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .filters-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            color: #666;
            font-size: 0.9em;
            font-weight: 600;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .report-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .report-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .report-description {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
        }

        .btn-generate {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-generate:hover {
            transform: scale(1.05);
        }

        .report-preview {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 30px;
        }

        .report-preview h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .summary-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .summary-item h4 {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .summary-item p {
            color: #333;
            font-size: 1.8em;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .data-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .export-section {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .report-grid, .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tutor_dashboard.php" class="back-btn no-print">← Back to Dashboard</a>
        
        <div class="header no-print">
            <h1>📊 Reports & Analytics</h1>
            <p>Generate comprehensive reports and insights</p>
        </div>

        <form method="GET" action="" class="filters-section no-print">
            <h2>🔧 Report Filters</h2>
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start" value="<?php echo $startDate; ?>">
                </div>

                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end" value="<?php echo $endDate; ?>">
                </div>

                <div class="filter-group">
                    <label>Course</label>
                    <select name="course">
                        <option value="all">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo $courseId == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($reportType ?? ''); ?>">
            <button type="submit" class="btn btn-generate">Apply Filters</button>
        </form>

        <div class="report-grid no-print">
            <a href="?type=sessions&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>&course=<?php echo $courseId; ?>" 
               class="report-card" style="text-decoration: none; color: inherit;">
                <div class="report-icon">📅</div>
                <div class="report-title">Session Report</div>
                <div class="report-description">
                    Detailed breakdown of all tutoring sessions, attendance, duration, and completion rates.
                </div>
            </a>

            <a href="?type=students&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>&course=<?php echo $courseId; ?>" 
               class="report-card" style="text-decoration: none; color: inherit;">
                <div class="report-icon">👥</div>
                <div class="report-title">Student Progress Report</div>
                <div class="report-description">
                    Track individual student progress, performance metrics, and learning outcomes.
                </div>
            </a>

            <a href="?type=earnings&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>&course=<?php echo $courseId; ?>" 
               class="report-card" style="text-decoration: none; color: inherit;">
                <div class="report-icon">💰</div>
                <div class="report-title">Earnings Report</div>
                <div class="report-description">
                    Comprehensive financial summary including hours worked, rates, and total earnings.
                </div>
            </a>
        </div>

        <?php if ($reportData && $reportType): ?>
        <div class="report-preview">
            <h2><?php echo htmlspecialchars($reportData['title']); ?></h2>
            
            <?php if (!empty($reportData['summary'])): ?>
            <div class="summary-grid">
                <?php foreach ($reportData['summary'] as $label => $value): ?>
                <div class="summary-item">
                    <h4><?php echo htmlspecialchars($label); ?></h4>
                    <p><?php echo htmlspecialchars($value); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($reportData['tableData'])): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach ($reportData['tableHeaders'] as $header): ?>
                            <th><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['tableData'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <div class="export-section no-print">
                <button onclick="window.print()" class="btn btn-generate">📄 Print Report</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>