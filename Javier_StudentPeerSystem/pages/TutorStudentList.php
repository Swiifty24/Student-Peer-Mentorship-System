<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/enrollments.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isTutorNow']) || $_SESSION['isTutorNow'] != 1) {
    header("Location: login.php");
    exit();
}

$conn = new Database();
$pdo = $conn->connect();
$tutorUserId = $_SESSION['user_id'];
$enrollmentMgr = new Enrollment();

// FIXED: Get tutor information with correct column names
$stmt = $pdo->prepare("SELECT tp.*, u.firstName, u.lastName 
                       FROM tutorprofiles tp 
                       JOIN users u ON tp.userID = u.userID
                       WHERE tp.userID = ?");
$stmt->execute([$tutorUserId]);
$tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutorProfile) {
    die("Tutor profile not found. Please set up your profile first.");
}

// Get filter parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$courseFilter = isset($_GET['course']) ? $_GET['course'] : 'all';

// FIXED: Build the query with correct column names
$query = "SELECT 
          u.userID, u.firstName, u.lastName, u.email,
          c.courseName,
          e.status,
          e.enrollmentID,
          COUNT(s.sessionID) as session_count,
          MAX(s.sessionDate) as last_session,
          MIN(CASE WHEN s.sessionDate > CURDATE() THEN s.sessionDate END) as next_session,
          AVG(r.rating) as avg_rating
          FROM enrollments e
          JOIN users u ON e.studentUserID = u.userID
          JOIN courses c ON e.courseID = c.courseID
          LEFT JOIN sessions s ON e.enrollmentID = s.enrollmentID AND s.status = 'Completed'
          LEFT JOIN reviews r ON e.enrollmentID = r.enrollmentID
          WHERE e.tutorUserID = ?";

$params = [$tutorUserId];

if (!empty($searchTerm)) {
    $query .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== 'all') {
    $statusCode = null;
    switch($statusFilter) {
        case 'requested': $statusCode = 0; break;
        case 'confirmed': $statusCode = 1; break;
        case 'cancelled': $statusCode = 2; break;
        case 'completed': $statusCode = 3; break;
    }
    if ($statusCode !== null) {
        $query .= " AND e.status = ?";
        $params[] = $statusCode;
    }
}

if ($courseFilter !== 'all') {
    $query .= " AND c.courseID = ?";
    $params[] = $courseFilter;
}

$query .= " GROUP BY u.userID, u.firstName, u.lastName, u.email, c.courseName, e.status, e.enrollmentID
            ORDER BY session_count DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalStudents = count($students);
$activeStudents = count(array_filter($students, function($s) { return $s['status'] == 1; }));
$pendingStudents = count(array_filter($students, function($s) { return $s['status'] == 0; }));
$avgSessions = $totalStudents > 0 ? round(array_sum(array_column($students, 'session_count')) / $totalStudents) : 0;

// FIXED: Get course list for filter
$stmt = $pdo->prepare("SELECT DISTINCT c.courseID, c.courseName 
                       FROM courses c
                       JOIN enrollments e ON c.courseID = e.courseID
                       WHERE e.tutorUserID = ?
                       ORDER BY c.courseName");
$stmt->execute([$tutorUserId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Course', 'Status', 'Sessions', 'Next Session', 'Rating']);
    
    foreach ($students as $student) {
        $statusName = $enrollmentMgr->getStatusString($student['status']);
        fputcsv($output, [
            $student['firstName'] . ' ' . $student['lastName'],
            $student['email'],
            $student['courseName'],
            $statusName,
            $student['session_count'],
            $student['next_session'] ?? 'N/A',
            round($student['avg_rating'] ?? 0, 1)
        ]);
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - PeerMentor</title>
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
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.8em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #666;
            font-size: 1.15em;
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

        .controls {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .search-box {
            flex: 1;
            min-width: 280px;
        }

        .search-box input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-select {
            padding: 14px 24px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1em;
            cursor: pointer;
            background: white;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            transition: all 0.3s;
        }

        .filter-select:hover {
            border-color: #667eea;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .stats-bar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            border-radius: 12px;
            transition: all 0.3s;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 0.95em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .table-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            overflow-x: auto;
            animation: fadeIn 0.8s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        th {
            padding: 18px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            transform: scale(1.01);
        }

        td {
            padding: 18px;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 1.1em;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .name-info h3 {
            color: #333;
            margin-bottom: 4px;
            font-size: 1.05em;
            font-weight: 600;
        }

        .name-info p {
            color: #888;
            font-size: 0.9em;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-requested {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }

        .badge-confirmed {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .badge-completed {
            background: linear-gradient(135deg, #cce5ff, #b8daff);
            color: #004085;
        }

        .badge-cancelled {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
            font-weight: 600;
        }

        .btn-view {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1em;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .no-data-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 0.85em;
            }

            th, td {
                padding: 12px 8px;
            }

            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tutorRequests.php" class="back-btn">← Back to Dashboard</a>
        
        <div class="header">
            <h1>👥 My Students</h1>
            <p>Manage and track your enrolled students</p>
        </div>

        <form method="GET" action="" class="controls">
            <div class="search-box">
                <input type="text" name="search" placeholder="🔍 Search students by name or email..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            
            <select class="filter-select" name="status" onchange="this.form.submit()">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="requested" <?php echo $statusFilter === 'requested' ? 'selected' : ''; ?>>Requested</option>
                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <select class="filter-select" name="course" onchange="this.form.submit()">
                <option value="all">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['courseID']; ?>" 
                            <?php echo $courseFilter == $course['courseID'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['courseName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </form>

        <a href="?export=csv<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?><?php echo $courseFilter !== 'all' ? '&course=' . $courseFilter : ''; ?>" 
           class="btn btn-primary" style="margin-bottom: 25px;">📥 Export CSV</a>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $activeStudents; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $pendingStudents; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $avgSessions; ?></div>
                <div class="stat-label">Avg Sessions</div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Sessions</th>
                        <th>Next Session</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="no-data">
                                <div class="no-data-icon">📚</div>
                                <p>No students found matching your criteria</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php 
                            $initials = strtoupper(substr($student['firstName'], 0, 1) . substr($student['lastName'], 0, 1));
                            $rating = round($student['avg_rating'] ?? 0);
                            $stars = str_repeat('⭐', $rating);
                            $statusName = $enrollmentMgr->getStatusString($student['status']);
                            $statusClass = '';
                            switch($student['status']) {
                                case 0: $statusClass = 'requested'; break;
                                case 1: $statusClass = 'confirmed'; break;
                                case 2: $statusClass = 'cancelled'; break;
                                case 3: $statusClass = 'completed'; break;
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="student-name">
                                        <div class="avatar"><?php echo $initials; ?></div>
                                        <div class="name-info">
                                            <h3><?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?></h3>
                                            <p><?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['courseName']); ?></td>
                                <td><span class="badge badge-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusName); ?></span></td>
                                <td><?php echo $student['session_count']; ?></td>
                                <td><?php echo $student['next_session'] ? date('M d, Y', strtotime($student['next_session'])) : 'N/A'; ?></td>
                                <td><span class="rating-stars"><?php echo $stars ?: 'N/A'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>