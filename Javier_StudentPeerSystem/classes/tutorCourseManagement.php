<?php

require_once 'database.php';

class TutorCourse {
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Finds available tutors offering a specific course.
     * @param int $courseID
     * @return array List of tutor details.
     */
    public function findTutorsByCourse($courseID) {
        // Join users, tutorProfiles, and tutorCourses to get detailed tutor info
        $sql = "SELECT 
                    u.userID, 
                    u.firstName, 
                    u.lastName, 
                    tp.tutorBio, 
                    tp.hourlyRate, 
                    tp.availabilityDetails
                FROM 
                    users u
                JOIN 
                    tutorCourses tc ON u.userID = tc.userID
                JOIN 
                    tutorProfiles tp ON u.userID = tp.userID
                WHERE 
                    tc.courseID = :courseID 
                AND 
                    u.isTutorNow = 1
                ORDER BY 
                    u.lastName";

        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':courseID', $courseID, PDO::PARAM_INT);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Error handling or logging
            echo "Tutor Find Error: " . $e->getMessage();
            return [];
        }
    }
    
    /**
     * Fetches a simple array of course IDs taught by a specific tutor.
     * This is used to pre-select checkboxes in the profile form.
     * @param int $userID
     * @return array Simple array of course IDs (e.g., [1, 5, 12]).
     */
    public function getCoursesByTutor($userID) {
        $sql = "SELECT courseID FROM tutorCourses WHERE userID = :userID";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            // Fetch all course IDs and map them into a simple, 1D array
            return $query->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Removes all courses assigned to a specific tutor.
     * @param int $userID
     * @return bool True on success, False on failure.
     */
    public function removeCoursesByTutor($userID) {
        $sql = "DELETE FROM tutorCourses WHERE userID = :userID";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            return $query->execute();
        } catch (PDOException $e) {
            echo "Course Removal Error: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Links a specific course to a tutor.
     * @param int $userID
     * @param int $courseID
     * @return bool True on success, False on failure.
     */
    public function addCourseToTutor($userID, $courseID) {
        // Using IGNORE to prevent error if the link already exists (though it shouldn't after removal)
        $sql = "INSERT IGNORE INTO tutorCourses (userID, courseID) VALUES (:userID, :courseID)";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->bindParam(':courseID', $courseID, PDO::PARAM_INT);
            return $query->execute();
        } catch (PDOException $e) {
            echo "Course Add Error: " . $e->getMessage();
            return false;
        }
    }
}
?>