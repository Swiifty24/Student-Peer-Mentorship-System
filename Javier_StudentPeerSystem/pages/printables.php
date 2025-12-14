<?php
require_once 'init.php';
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
function getStudentData($pdo, $tutorUserId, $studentId = null)
{
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
function getSessions($pdo, $tutorUserId, $limit = 20)
{
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
    <link href="../styles/reports.css" rel="stylesheet">
</head>

<body class="report-page">
    <div class="report-container">
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
                    <p>Tutor: <?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?>
                    </p>
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
                    <p>Tutor: <?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?>
                    </p>
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
                        <span
                            class="info-value">INV-<?php echo date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span>
                        <span class="info-value"><?php echo date('M d, Y'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tutor:</span>
                        <span
                            class="info-value"><?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></span>
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
                            <td style="font-weight: bold; font-size: 1.2em; color: #667eea;">
                                $<?php echo number_format($subtotal, 2); ?></td>
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
                        <span
                            class="info-value"><?php echo htmlspecialchars($tutorProfile['firstName'] . ' ' . $tutorProfile['lastName']); ?></span>
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