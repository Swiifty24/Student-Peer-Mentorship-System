<?php

require_once 'database.php';

class Enrollment {
    // Properties matching your database table columns for a session request
    public $studentUserID;
    public $tutorUserID;
    public $courseID;
    public $sessionDetails;
    // status 0: Requested (default), 1: Confirmed, 2: Cancelled/Declined, 3: Completed

    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Inserts a new session request into the database.
     * @return bool True on success, False on failure.
     */
    public function requestSession() {
        $status = 0; // Default status: Requested
        $requestDate = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO enrollments (
                    studentUserID, tutorUserID, courseID, 
                    sessionDetails, status, requestDate
                ) VALUES (
                    :studentUserID, :tutorUserID, :courseID, 
                    :sessionDetails, :status, :requestDate
                )";

        try {
            $query = $this->db->connect()->prepare($sql);
            
            $query->bindParam(':studentUserID', $this->studentUserID, PDO::PARAM_INT);
            $query->bindParam(':tutorUserID', $this->tutorUserID, PDO::PARAM_INT);
            $query->bindParam(':courseID', $this->courseID, PDO::PARAM_INT);
            $query->bindParam(':sessionDetails', $this->sessionDetails, PDO::PARAM_STR);
            $query->bindParam(':status', $status, PDO::PARAM_INT);
            $query->bindParam(':requestDate', $requestDate, PDO::PARAM_STR);

            return $query->execute();
        } catch (PDOException $e) {
            // Error handling or logging
            error_log("Database Error in requestSession: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all session requests for a specific tutor.
     * @param int $tutorID The ID of the tutor.
     * @return array List of enrollment requests.
     */
    public function getRequestsByTutor($tutorID) {
        // Only fetch requests that are 'Requested' (0) or 'Confirmed' (1) and order by request date.
        $sql = "SELECT * FROM enrollments WHERE tutorUserID = :tutorID AND status IN (0, 1) ORDER BY requestDate DESC";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':tutorID', $tutorID, PDO::PARAM_INT);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getRequestsByTutor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates the status of a session request/enrollment.
     * @param int $enrollmentID The ID of the enrollment record.
     * @param string $statusString 'Confirmed' or 'Cancelled'.
     * @return bool True on success, False on failure.
     */
    public function updateStatus($enrollmentID, $statusString) {
        // Map string status from tutorRequests.php to integer status for the database
        $statusCode = 0; // Default to Requested (0)
        switch ($statusString) {
            case 'Confirmed':
                $statusCode = 1;
                break;
            case 'Cancelled':
                $statusCode = 2; // Cancelled/Declined
                break;
            case 'Completed':
                $statusCode = 3; // Completed
                break;
        }

        $sql = "UPDATE enrollments SET status = :status WHERE enrollmentID = :enrollmentID";

        try {
            $query = $this->db->connect()->prepare($sql);
            
            $query->bindParam(':status', $statusCode, PDO::PARAM_INT);
            $query->bindParam(':enrollmentID', $enrollmentID, PDO::PARAM_INT);

            return $query->execute();
        } catch (PDOException $e) {
            // Error handling or logging:
            error_log("Database Error in updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Maps the integer status code to a readable string.
     * @param int $statusCode The status code (0=Requested, 1=Confirmed, 2=Cancelled/Declined, 3=Completed).
     * @return string The human-readable status.
     */
    public function getStatusString($statusCode) {
        switch ($statusCode) {
            case 0:
                return 'Requested';
            case 1:
                return 'Confirmed';
            case 2:
                return 'Cancelled/Declined';
            case 3:
                return 'Completed';
            default:
                return 'Unknown Status';
        }
    }
}
?>