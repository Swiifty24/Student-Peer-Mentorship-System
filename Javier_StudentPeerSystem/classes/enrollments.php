<?php

require_once 'database.php';
require_once 'notifications.php';
require_once 'emailService.php';
require_once 'users.php';
require_once 'courses.php';

class Enrollment
{
    private $conn;
    private $table = 'enrollments';
    private $notificationManager;
    private $emailService;
    private $userManager;
    private $courseManager;

    public $enrollmentID;
    public $studentUserID;
    public $tutorUserID;
    public $courseID;
    public $sessionDetails;
    public $status;
    public $requestDate;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
        $this->notificationManager = new Notification();
        $this->userManager = new User();
        $this->courseManager = new Course();
        $this->emailService = new EmailService();
    }

    public function requestSession()
    {
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (studentUserID, tutorUserID, courseID, sessionDetails, status, requestDate) 
                      VALUES (:studentUserID, :tutorUserID, :courseID, :sessionDetails, 0, NOW())";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':studentUserID', $this->studentUserID);
            $stmt->bindParam(':tutorUserID', $this->tutorUserID);
            $stmt->bindParam(':courseID', $this->courseID);
            $stmt->bindParam(':sessionDetails', $this->sessionDetails);

            if ($stmt->execute()) {
                $enrollmentID = $this->conn->lastInsertId();

                // Get user and course names for notifications
                $studentName = $this->userManager->getUserFullNameByID($this->studentUserID);
                $courseName = $this->courseManager->getCourseNameByID($this->courseID);

                // Create system notification for tutor
                $notificationMessage = "New tutoring request from $studentName for $courseName";
                $this->notificationManager->create(
                    $this->tutorUserID,
                    'request',
                    $notificationMessage,
                    $enrollmentID
                );

                // FIXED LINE 64: Get tutor info before using it
                $tutorInfo = $this->userManager->getUserByID($this->tutorUserID);

                // Send email notification to tutor (if they have email notifications enabled)
                if ($tutorInfo && isset($tutorInfo['email'])) {
                    $tutorFullName = $tutorInfo['firstName'] . ' ' . $tutorInfo['lastName'];
                    $this->emailService->sendNewSessionRequestEmail(
                        $tutorInfo['email'],
                        $tutorFullName,
                        $studentName,
                        $courseName,
                        $this->sessionDetails
                    );
                }

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Request session error: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($enrollmentID, $newStatus)
    {
        try {
            // First, get enrollment details for notifications
            $enrollmentQuery = "SELECT studentUserID, tutorUserID, courseID 
                               FROM " . $this->table . " 
                               WHERE enrollmentID = :enrollmentID";
            $enrollmentStmt = $this->conn->prepare($enrollmentQuery);
            $enrollmentStmt->bindParam(':enrollmentID', $enrollmentID);
            $enrollmentStmt->execute();
            $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$enrollment) {
                return false;
            }

            // Map string status to integer
            $statusMap = [
                'Confirmed' => 1,
                'Cancelled' => 2,
                'Completed' => 3
            ];

            $statusCode = $statusMap[$newStatus] ?? 0;

            $query = "UPDATE " . $this->table . " 
                      SET status = :status 
                      WHERE enrollmentID = :enrollmentID";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $statusCode);
            $stmt->bindParam(':enrollmentID', $enrollmentID);

            if ($stmt->execute()) {
                // Get names for notifications
                $studentInfo = $this->userManager->getUserByID($enrollment['studentUserID']);
                $tutorName = $this->userManager->getUserFullNameByID($enrollment['tutorUserID']);
                $courseName = $this->courseManager->getCourseNameByID($enrollment['courseID']);

                // Send appropriate notifications based on status
                if ($newStatus === 'Confirmed') {
                    // Notify student
                    $message = "Your tutoring request for $courseName with $tutorName has been confirmed!";
                    $this->notificationManager->create(
                        $enrollment['studentUserID'],
                        'confirmation',
                        $message,
                        $enrollmentID
                    );

                    // Send confirmation email
                    if ($studentInfo) {
                        $studentFullName = $studentInfo['firstName'] . ' ' . $studentInfo['lastName'];
                        $this->emailService->sendSessionConfirmedEmail(
                            $studentInfo['email'],
                            $studentFullName,
                            $tutorName,
                            $courseName
                        );
                    }

                } elseif ($newStatus === 'Cancelled') {
                    // Notify student
                    $message = "Your tutoring request for $courseName has been declined by $tutorName.";
                    $this->notificationManager->create(
                        $enrollment['studentUserID'],
                        'cancellation',
                        $message,
                        $enrollmentID
                    );

                    // Send decline email to student
                    if ($studentInfo) {
                        $studentFullName = $studentInfo['firstName'] . ' ' . $studentInfo['lastName'];
                        $this->emailService->sendSessionDeclinedEmail(
                            $studentInfo['email'],
                            $studentFullName,
                            $tutorName,
                            $courseName
                        );
                    }

                } elseif ($newStatus === 'Completed') {
                    // Notify student
                    $message = "Your tutoring session for $courseName with $tutorName has been completed!";
                    $this->notificationManager->create(
                        $enrollment['studentUserID'],
                        'completion',
                        $message,
                        $enrollmentID
                    );

                    // Send completion email
                    if ($studentInfo) {
                        $studentFullName = $studentInfo['firstName'] . ' ' . $studentInfo['lastName'];
                        $this->emailService->sendSessionCompletedEmail(
                            $studentInfo['email'],
                            $studentFullName,
                            $tutorName,
                            $courseName
                        );
                    }
                }

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
    }

    public function getRequestsByTutor($tutorUserID, $limit = 20, $offset = 0)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE tutorUserID = :tutorUserID 
                      ORDER BY requestDate DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tutorUserID', $tutorUserID, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get tutor requests error: " . $e->getMessage());
            return [];
        }
    }

    public function getRequestsByStudent($studentUserID)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE studentUserID = :studentUserID 
                      ORDER BY requestDate DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':studentUserID', $studentUserID);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get student requests error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatusString($statusCode)
    {
        $statusMap = [
            0 => 'Requested',
            1 => 'Confirmed',
            2 => 'Cancelled',
            3 => 'Completed'
        ];

        return $statusMap[$statusCode] ?? 'Unknown';
    }
}
?>