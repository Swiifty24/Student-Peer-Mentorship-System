<?php
// classes/tutorCourses.php

require_once 'database.php';

/**
 * Manages the linking between Tutors and Courses (tutorCourses table).
 */
class TutorCourse {
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Finds available tutors offering a specific course (used when a filter is applied).
     * @param int $courseID The ID of the course to search for.
     * @return array List of tutor details (userID, firstName, lastName, tutorBio, hourlyRate, availabilityDetails, nextAvailableDate, availableTime).
     */
    public function findTutorsByCourse($courseID) {
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
        echo "Tutor Find Error: " . $e->getMessage();
        return [];
    }
}
    
    /**
     * Retrieves all course names or IDs that a tutor teaches.
     * Used for display on tutor cards.
     * @param int $userID The ID of the tutor.
     * @param bool $returnIDs If true, returns an array of course IDs. If false, returns an array of course names.
     * @return array List of course names or IDs.
     */
    public function getAllCoursesTaughtByTutor($userID, $returnIDs = false) {
        $select = $returnIDs ? 'c.courseID' : 'c.courseName';
        $sql = "SELECT 
                    {$select}
                FROM 
                    tutorCourses tc
                JOIN 
                    courses c ON tc.courseID = c.courseID
                WHERE 
                    tc.userID = :userID
                ORDER BY 
                    c.courseName";

        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            
            $results = $query->fetchAll(PDO::FETCH_COLUMN, 0);
            
            return $results; // Returns array of names or IDs
        } catch (PDOException $e) {
            error_log("Database Error in getAllCoursesTaughtByTutor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * FIX FOR ERROR: Retrieves all courses a tutor teaches, including course name and subject area.
     * Used to populate the course dropdown in the session request modal.
     * @param int $userID The ID of the tutor.
     * @return array List of courses (courseID, courseName, subjectArea).
     */
    public function getTutorCoursesWithSubjectArea($userID) {
        $sql = "SELECT 
                    c.courseID, 
                    c.courseName, 
                    c.subjectArea 
                FROM 
                    tutorCourses tc
                JOIN 
                    courses c ON tc.courseID = c.courseID
                WHERE 
                    tc.userID = :userID
                ORDER BY 
                    c.subjectArea, c.courseName";

        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getTutorCoursesWithSubjectArea: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Saves the courses taught by a tutor using a transaction:
     * 1. Removes all existing course links.
     * 2. Inserts the new links.
     * @param int $userID
     * @param array $courseIDs
     * @return bool True on success, False on failure.
     */
    public function saveCourses($userID, array $courseIDs) {
        $dbConnection = $this->db->connect();
        
        try {
            // Start transaction
            $dbConnection->beginTransaction();

            // 1. Delete all existing courses for this tutor
            $deleteSql = "DELETE FROM tutorCourses WHERE userID = :userID";
            $deleteQuery = $dbConnection->prepare($deleteSql);
            $deleteQuery->execute([':userID' => $userID]); 

            // 2. Insert new courses (only if there are courses to insert)
            if (!empty($courseIDs)) {
                $insertSql = "INSERT INTO tutorCourses (userID, courseID) VALUES (:userID, :courseID)";
                $insertQuery = $dbConnection->prepare($insertSql);
                
                foreach ($courseIDs as $courseID) {
                    $insertQuery->execute([
                        ':userID' => $userID,
                        ':courseID' => intval($courseID) 
                    ]);
                }
            }

            // Commit transaction
            $dbConnection->commit();
            return true;

        } catch (PDOException $e) {
            // Rollback on error
            $dbConnection->rollBack();
            error_log("Course Save Error: " . $e->getMessage());
            return false;
        }
    }
}
?>