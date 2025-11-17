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

// Get filter parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$courseFilter = isset($_GET['course']) ? $_GET['course'] : 'all';

// Build the query with filters
$query = "SELECT 
          u.user_id, u.first_name, u.last_name, u.email,
          c.course_name,
          e.status,
          e.enrollment_id,
          COUNT(s.session_id) as session_count,
          MAX(s.session_date) as last_session,
          MIN(CASE WHEN s.session_date > CURDATE() THEN s.session_date END) as next_session,
          AVG(r.rating) as avg_rating
          FROM enrollments e
          JOIN users u ON e.student_id = u.user_id
          JOIN courses c ON e.course_id = c.course_id
          LEFT JOIN sessions s ON e.enrollment_id = s.enrollment_id AND s.status = 'completed'
          LEFT JOIN ratings r ON e.enrollment_id = r.enrollment_id
          WHERE e.tutor_id = ?";

$params = [$tutorProfile['tutor_id']];

if (!empty($searchTerm)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter !== 'all') {
    $query .= " AND e.status = ?";
    $params[] = $statusFilter;
}

if ($courseFilter !== 'all') {
    $query .= " AND c.course_id = ?";
    $params[] = $courseFilter;
}

$query .= " GROUP BY u.user_id, u.first_name, u.last_name, u.email, c.course_name, e.status, e.enrollment_id
            ORDER BY session_count DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalStudents = count($students);
$activeStudents = count(array_filter($students, function($s) { return $s['status'] === 'active'; }));
$pendingStudents = count(array_filter($students, function($s) { return $s['status'] === 'pending'; }));
$avgSessions = $totalStudents > 0 ? round(array_sum(array_column($students, 'session_count')) / $totalStudents) : 0;

// Get course list for filter
$stmt = $pdo->prepare("SELECT DISTINCT c.course_id, c.course_name 
                       FROM courses c
                       JOIN enrollments e ON c.course_id = e.course_id
                       WHERE e.tutor_id = ?
                       ORDER BY c.course_name");
$stmt->execute([$tutorProfile['tutor_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Course', 'Status', 'Sessions', 'Next Session', 'Rating']);
    
    foreach ($students as $student) {
        fputcsv($output, [
            $student['first_name'] . ' ' . $student['last_name'],
            $student['email'],
            $student['course_name'],
            $student['status'],
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
    <title>My Students - Student Peer Mentorship</title>
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

        .header p {
            color: #666;
            font-size: 1.1em;
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

        .controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            background: white;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .stats-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow-x: auto;
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
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }

        tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        td {
            padding: 15px;
        }

        .student-name {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }

        .name-info h3 {
            color: #333;
            margin-bottom: 3px;
            font-size: 1em;
        }

        .name-info p {
            color: #888;
            font-size: 0.85em;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-completed {
            background: #cce5ff;
            color: #004085;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: white;
        }

        .btn-view {
            background: #667eea;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-message {
            background: #28a745;
        }

        .btn-message:hover {
            background: #218838;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1em;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
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
                font-size: 0.9em;
            }

            th, td {
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tutor_dashboard.php" class="back-btn">← Back to Dashboard</a>
        
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
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>

            <select class="filter-select" name="course" onchange="this.form.submit()">
                <option value="all">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>" 
                            <?php echo $courseFilter == $course['course_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">🔍 Search</button>
        </form>

        <a href="?export=csv<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $statusFilter !== 'all' ? '&status=' . $statusFilter : ''; ?><?php echo $courseFilter !== 'all' ? '&course=' . $courseFilter : ''; ?>" 
           class="btn btn-primary" style="margin-bottom: 20px;">📥 Export CSV</a>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $activeStudents; ?></div>
                <div class="stat-label">Active</div>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="no-data">No students found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php 
                            $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
                            $rating = round($student['avg_rating'] ?? 0);
                            $stars = str_repeat('⭐', $rating);
                            ?>
                            <tr>
                                <td>
                                    <div class="student-name">
                                        <div class="avatar"><?php echo $initials; ?></div>
                                        <div class="name-info">
                                            <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                                            <p><?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                                <td><span class="badge badge-<?php echo $student['status']; ?>"><?php echo ucfirst($student['status']); ?></span></td>
                                <td><?php echo $student['session_count']; ?></td>
                                <td><?php echo $student['next_session'] ? date('M d, Y', strtotime($student['next_session'])) : 'N/A'; ?></td>
                                <td><span class="rating-stars"><?php echo $stars ?: 'N/A'; ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="student_details.php?id=<?php echo $student['user_id']; ?>" class="btn-sm btn-view">View</a>
                                        <a href="messages.php?user=<?php echo $student['user_id']; ?>" class="btn-sm btn-message">Message</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>