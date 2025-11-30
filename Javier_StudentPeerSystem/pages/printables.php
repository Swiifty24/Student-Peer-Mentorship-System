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

// FIXED: Get tutor information with correct column names
$stmt = $pdo->prepare("SELECT tp.*, u.firstName, u.lastName 
                       FROM tutorprofiles tp 
                       JOIN users u ON tp.userID = u.userID
                       WHERE tp.userID = ?");
$stmt->execute([$tutorUserId]);
$tutorProfile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutorProfile) {
    die("Tutor profile not found.");
}

// Get document type and student ID if specified
$docType = isset($_GET['doc']) ? $_GET['doc'] : null;
$studentId = isset($_GET['student']) ? $_GET['student'] : null;

// FIXED: Function to get student data
function getStudentData($pdo, $tutorUserId, $studentId = null) {
    if ($studentId) {
        $stmt = $pdo->prepare("SELECT u.*, c.courseName, e.enrollmentID, e.status,
                               COUNT(s.sessionID) as total_sessions,
                               SUM(s.duration) as total_hours
                               FROM users u
                               JOIN enrollments e ON u.userID = e.studentUserID
                               JOIN courses c ON e.courseID = c.courseID
                               LEFT JOIN sessions s ON e.enrollmentID = s.enrollmentID AND s.status = 'Completed'
                               WHERE e.tutorUserID = ? AND u.userID = ?
                               GROUP BY u.userID");
        $stmt->execute([$tutorUserId, $studentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

// FIXED: Get sessions for attendance/schedule
function getSessions($pdo, $tutorUserId, $limit = 20) {
    $stmt = $pdo->prepare("SELECT s.*, u.firstName, u.lastName, c.courseName, e.status
                           FROM sessions s
                           JOIN enrollments e ON s.enrollmentID = e.enrollmentID
                           JOIN users u ON e.studentUserID = u.userID
                           JOIN courses c ON e.courseID = c.courseID
                           WHERE e.tutorUserID = ?
                           ORDER BY s.sessionDate DESC, s.startTime DESC
                           LIMIT ?");
    $stmt->execute([$tutorUserId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$student = $studentId ? getStudentData($pdo, $tutorUserId, $studentId) : null;
$sessions = getSessions($pdo, $tutorUserId, 30);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printables - PeerMentor</title>
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

        .printables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .printable-card {
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

        .printable-card:nth-child(1) { animation-delay: 0.1s; }
        .printable-card:nth-child(2) { animation-delay: 0.2s; }
        .printable-card:nth-child(3) { animation-delay: 0.3s; }
        .printable-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .printable-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .printable-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .printable-icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .printable-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .printable-description {
            color: #666;
            font-size: 0.95em;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .printable-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95em;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-preview {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            flex: 1;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        /* Print document styles */
        .print-document {
            background: white;
            padding: 50px;
            margin: 20px 0;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .doc-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 4px solid #667eea;
            padding-bottom: 25px;
        }

        .doc-header h2 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .doc-header p {
            color: #666;
            font-size: 1.1em;
        }

        .doc-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-weight: 600;
            color: #667eea;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .doc-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9em;
        }

        .doc-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .doc-table tbody tr:hover {
            background: #f8f9ff;
        }

        .doc-footer {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 3px solid #e0e0e0;
            text-align: center;
            color: #666;
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
        <a href="tutorRequests.php" class="back-btn no-print">← Back to Dashboard</a>
        
        <div class="header no-print">
            <h1>🖨️ Printables & Documents</h1>
            <p>Generate professional documents for your tutoring sessions</p>
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
                    Print weekly or monthly schedules of all your tutoring sessions.
                </div>
                <div class="printable-actions">
                    <a href="?doc=schedule" class="btn btn-preview">View & Print</a>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">💳</div>
                <div class="printable-title">Invoice</div>
                <div class="printable-description">
                    Generate professional invoices for your tutoring services.
                </div>
                <div class="printable-actions">
                    <a href="?doc=invoice" class="btn btn-preview">View & Print</a>
                </div>
            </div>

            <div class="printable-card">
                <div class="printable-icon">📊</div>
                <div class="printable-title">Progress Report</div>
                <div class="printable-description">
                    Create detailed progress reports with performance metrics.
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
                <p>Tutor: <?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></p>
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
                        <td><?php echo date('M d, Y', strtotime($session['sessionDate'])); ?></td>
                        <td><?php echo htmlspecialchars($session['firstName'] . ' ' . $session['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($session['courseName']); ?></td>
                        <td><?php echo date('g:i A', strtotime($session['startTime'])); ?></td>
                        <td><?php echo $session['status'] === 'Completed' ? '✓ Present' : '✗ Absent'; ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p><strong>PeerMentor Connect</strong> - Student Peer Mentorship System</p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-preview">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview" style="margin-left: 10px;">← Back to Printables</a>
        </div>
        <?php endif; ?>

        <?php if ($docType === 'schedule'): ?>
        <div class="print-document">
            <div class="doc-header">
                <h2>📅 Session Schedule</h2>
                <p>Tutor: <?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></p>
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
                        <td><?php echo date('l', strtotime($session['sessionDate'])); ?></td>
                        <td><?php echo date('g:i A', strtotime($session['startTime'])); ?></td>
                        <td><?php echo htmlspecialchars($session['firstName'] . ' ' . $session['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($session['courseName']); ?></td>
                        <td><?php echo $session['duration']; ?> hrs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p><strong>PeerMentor Connect</strong> - Student Peer Mentorship System</p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-preview">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview" style="margin-left: 10px;">← Back to Printables</a>
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
                    <span class="info-value"><?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></span>
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
                    $hourlyRate = $tutorProfile['hourlyRate'] ?? 30;
                    $subtotal = $totalHours * $hourlyRate;
                    ?>
                    <tr>
                        <td>Tutoring Services - <?php echo date('F Y'); ?></td>
                        <td><?php echo number_format($totalHours, 1); ?></td>
                        <td>$<?php echo number_format($hourlyRate, 2); ?></td>
                        <td>$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <tr style="background: #f8f9ff;">
                        <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.2em;">Total:</td>
                        <td style="font-weight: bold; font-size: 1.2em; color: #667eea;">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                </tbody>
            </table>
            <div class="doc-footer">
                <p>Thank you for your business!</p>
                <p><strong>PeerMentor Connect</strong></p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-preview">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview" style="margin-left: 10px;">← Back to Printables</a>
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
                    <span class="info-value"><?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Period:</span>
                    <span class="info-value"><?php echo date('F Y'); ?></span>
                </div>
            </div>
            <h3 style="color: #667eea; margin: 30px 0 20px;">Student Performance Overview</h3>
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
                    $stmt = $pdo->prepare("SELECT u.firstName, u.lastName, c.courseName, e.status,
                                          COUNT(s.sessionID) as session_count,
                                          SUM(s.duration) as total_hours
                                          FROM enrollments e
                                          JOIN users u ON e.studentUserID = u.userID
                                          JOIN courses c ON e.courseID = c.courseID
                                          LEFT JOIN sessions s ON e.enrollmentID = s.enrollmentID AND s.status = 'Completed'
                                          WHERE e.tutorUserID = ?
                                          GROUP BY u.userID
                                          ORDER BY session_count DESC");
                    $stmt->execute([$tutorUserId]);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    require_once '../classes/enrollments.php';
                    $enrollmentMgr = new Enrollment();
                    
                    foreach ($students as $stud): 
                        $statusName = $enrollmentMgr->getStatusString($stud['status']);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stud['firstName'] . ' ' . $stud['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($stud['courseName']); ?></td>
                        <td><?php echo $stud['session_count']; ?></td>
                        <td><?php echo number_format($stud['total_hours'], 1); ?></td>
                        <td><?php echo htmlspecialchars($statusName); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="doc-footer">
                <p><strong>PeerMentor Connect</strong> | Generated on <?php echo date('F d, Y'); ?></p>
            </div>
        </div>
        <div class="no-print" style="text-align: center; margin: 20px;">
            <button onclick="window.print()" class="btn btn-preview">🖨️ Print Document</button>
            <a href="printables.php" class="btn btn-preview" style="margin-left: 10px;">← Back to Printables</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>