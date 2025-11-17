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
$stmt = $pdo->prepare("SELECT tp.*, u.first_name, u.last_name 
                       FROM tutorprofiles tp 
                       JOIN users u ON tp.user_id = u.user_id
                       WHERE tp.user_id = ?");
$stmt->execute([$tutorUserId]);
$tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get document type and student ID if specified
$docType = isset($_GET['doc']) ? $_GET['doc'] : null;
$studentId = isset($_GET['student']) ? $_GET['student'] : null;

// Function to get student data
function getStudentData($pdo, $tutorId, $studentId = null) {
    if ($studentId) {
        $stmt = $pdo->prepare("SELECT u.*, c.course_name, e.enrollment_id, e.status,
                               COUNT(s.session_id) as total_sessions,
                               SUM(s.duration) as total_hours
                               FROM users u
                               JOIN enrollments e ON u.user_id = e.student_id
                               JOIN courses c ON e.course_id = c.course_id
                               LEFT JOIN sessions s ON e.enrollment_id = s.enrollment_id AND s.status = 'completed'
                               WHERE e.tutor_id = ? AND u.user_id = ?
                               GROUP BY u.user_id");
        $stmt->execute([$tutorId, $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// Get sessions for attendance/schedule
function getSessions($pdo, $tutorId, $limit = 10) {
    $stmt = $pdo->prepare("SELECT s.*, u.first_name, u.last_name, c.course_name, e.status
                           FROM sessions s
                           JOIN enrollments e ON s.enrollment_id = e.enrollment_id
                           JOIN users u ON e.student_id = u.user_id
                           JOIN courses c ON e.course_id = c.course_id
                           WHERE e.tutor_id = ?
                           ORDER BY s.session_date DESC, s.start_time DESC
                           LIMIT ?");
    $stmt->execute([$tutorId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$student = $studentId ? getStudentData($pdo, $tutorProfile['tutor_id'], $studentId) : null;
$sessions = getSessions($pdo, $tutorProfile['tutor_id'], 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printables - Student Peer Mentorship</title>
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

        .printables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .printable-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }

        .printable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        }

        .printable-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .printable-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .printable-description {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .printable-actions {
            display: flex;
            gap: 10px;
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
            text-align: center;
        }

        .btn-preview {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-preview:hover {
            transform: scale(1.05);
        }

        .btn-print {
            background: #28a745;
            color: white;
        }

        .btn-print:hover {
            background: #218838;
        }

        /* Print document styles */
        .print-document {
            background: white;
            padding: 40px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .doc-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }

        .doc-header h2 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .doc-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .doc-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .doc-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .doc-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
        }

        .signature-line {
            margin-top: 50px;
            border-top: 2px solid #333;
            width: 300px;
            padding-top: 10px;
            margin-left: auto;
            margin-right: auto;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-document {
                box-shadow: none;
                page-break-after: always;
            }
            .print-document:last-child {
                page-break-after: auto;
            }
        }

        @media (max-width: 768px) {
            .printables-grid {
                grid-template-columns: 1fr;
            }
            .doc-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="tutor_dashboard.php" class="back-btn no-print">← Back to Dashboard</a>
        
        <div class="header no-print">
            <h1>🖨️ Printables & Documents</h1>
            <p>Generate and print professional documents for your tutoring sessions</p>
        </div>

        <?php if (!$docType): ?>
        <div class="printables-grid no-print">
            <div class="printable-card">
                <div class="printable-icon">📋</div>
                <div class="printable-title">Attendance Sheet</div>
                <div class="printable-description">
                    Print attendance tracking sheets for your tutoring sessions and courses.
                </div>
                <div class="printable-actions">
                    <a href="?doc=attendance" class="btn btn-preview">View & Print</a>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📅</div>
                <div class="printable-title">Session Schedule</div>
                <div class="printable-description">
                    Print weekly or monthly schedules of all your tutoring sessions and appointments.
                </div>
                <div class="printable-actions">
                    <a href="?doc=schedule" class="btn btn-preview">View & Print</a>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">💳</div>
                <div class="printable-title">Invoice</div>
                <div class="printable-description">
                    Generate professional invoices for your tutoring services and session payments.
                </div>
                <div class="printable-actions">
                    <a href="?doc=invoice" class="btn btn-preview">View & Print</a>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📊</div>
                <div class="printable-title">Progress Report</div>
                <div class="printable-description">
                    Create detailed progress reports for individual students with performance metrics.
                </div>
                <div class="printable-actions">
                    <a href="?doc=progress" class="btn btn-preview">View & Print</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($docType === 'attendance'): ?>
        <div class="print-document">
            <div class="doc-header">
                <h2>📋 Attendance Sheet</h2>
                <p>Tutor: <?php echo htmlspecialchars($tutorProfile['first_name'] . ' ' . $tutorProfile['last_name']); ?></p>
            </div>
            <div class="doc-info">
                <div class="info-item">
                    <span class="info-label">Period:</span>
                    <span class="info-value"><?php echo date('F Y'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Generated:</span>
                    <span class="info-value"><?php echo date('M d, Y'); ?></span>
                </div>
            </div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Signature</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($session['session_date'])); ?></td>
                        <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($session['course_name']); ?></td>
                        <td><?php echo date('g:i A', strtotime($session['start_time'])); ?></td>
                        <td><?php echo $session['status'] === 'completed' ? '✓ Present' : '✗ Absent'; ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p>Student Peer Mentorship System</p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview">← Back to Printables</a>
        </div>
        <?php endif; ?>

        <?php if ($docType === 'schedule'): ?>
        <div class="print-document">
            <div class="doc-header">
                <h2>📅 Session Schedule</h2>
                <p>Tutor: <?php echo htmlspecialchars($tutorProfile['first_name'] . ' ' . $tutorProfile['last_name']); ?></p>
            </div>
            <div class="doc-info">
                <div class="info-item">
                    <span class="info-label">Week of:</span>
                    <span class="info-value"><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo date('l', strtotime($session['session_date'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($session['start_time'])); ?></td>
                        <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($session['course_name']); ?></td>
                        <td><?php echo $session['duration']; ?> hrs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p>Student Peer Mentorship System</p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview">← Back to Printables</a>
        </div>
        <?php endif; ?>

        <?php if ($docType === 'invoice'): ?>
        <div class="print-document">
            <div class="doc-header">
                <h2>💳 Invoice</h2>
                <p>Professional Tutoring Services</p>
            </div>
            <div class="doc-info">
                <div class="info-item">
                    <span class="info-label">Invoice #:</span>
                    <span class="info-value">INV-<?php echo date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span class="info-value"><?php echo date('M d, Y'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tutor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($tutorProfile['first_name'] . ' ' . $tutorProfile['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Due Date:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime('+7 days')); ?></span>
                </div>
            </div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Hours</th>
                        <th>Rate</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalHours = array_sum(array_column($sessions, 'duration'));
                    $hourlyRate = $tutorProfile['hourly_rate'] ?? 30;
                    $subtotal = $totalHours * $hourlyRate;
                    ?>
                    <tr>
                        <td>Tutoring Services - <?php echo date('F Y'); ?></td>
                        <td><?php echo number_format($totalHours, 1); ?></td>
                        <td>$<?php echo number_format($hourlyRate, 2); ?></td>
                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr style="background: #f8f9fa;">
                        <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.2em;">Total:</td>
                        <td style="font-weight: bold; font-size: 1.2em;">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                </tbody>
            </table>
            <div class="doc-footer">
                <p>Thank you for your business!</p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview">← Back to Printables</a>
        </div>
        <?php endif; ?>

        <?php if ($docType === 'progress'): ?>
        <div class="print-document">
            <div class="doc-header">
                <h2>📊 Student Progress Report</h2>
                <p>Detailed Performance Analysis</p>
            </div>
            <div class="doc-info">
                <div class="info-item">
                    <span class="info-label">Tutor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($tutorProfile['first_name'] . ' ' . $tutorProfile['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Period:</span>
                    <span class="info-value"><?php echo date('F Y'); ?></span>
                </div>
            </div>
            <h3 style="color: #667eea; margin: 20px 0;">Student Performance Overview</h3>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Sessions Completed</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, c.course_name, e.status,
                                          COUNT(s.session_id) as session_count,
                                          SUM(s.duration) as total_hours
                                          FROM enrollments e
                                          JOIN users u ON e.student_id = u.user_id
                                          JOIN courses c ON e.course_id = c.course_id
                                          LEFT JOIN sessions s ON e.enrollment_id = s.enrollment_id AND s.status = 'completed'
                                          WHERE e.tutor_id = ?
                                          GROUP BY u.user_id
                                          ORDER BY session_count DESC");
                    $stmt->execute([$tutorProfile['tutor_id']]);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($students as $stud): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stud['first_name'] . ' ' . $stud['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($stud['course_name']); ?></td>
                        <td><?php echo $stud['session_count']; ?></td>
                        <td><?php echo number_format($stud['total_hours'], 1); ?></td>
                        <td><?php echo ucfirst($stud['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p>Student Peer Mentorship System | Generated on <?php echo date('F d, Y'); ?></p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-print">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview">← Back to Printables</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>