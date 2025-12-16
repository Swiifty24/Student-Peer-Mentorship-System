<?php
// classes/tutorCourses.php

require_once 'database.php';

/**
 * Manages the linking between Tutors and Courses (tutorCourses table).
 */
class TutorCourse
{
    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Finds available tutors offering a specific course.
     * @param int $courseID The ID of the course to search for.
     * @return array List of tutor details.
     */
    public function findTutorsByCourse($courseID)
    {
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
                    tutorcourses tc ON u.userID = tc.userID
                JOIN 
                    tutorprofiles tp ON u.userID = tp.userID
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
            error_log("Tutor Find Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all course names or IDs that a tutor teaches.
     * @param int $userID The ID of the tutor.
     * @param bool $returnIDs If true, returns an array of course IDs. If false, returns course names.
     * @return array List of course names or IDs.
     */
    public function getAllCoursesTaughtByTutor($userID, $returnIDs = false)
    {
        $select = $returnIDs ? 'c.courseID' : 'c.courseName';
        $sql = "SELECT 
                    {$select}
                FROM 
                    tutorcourses tc
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

            return $results;
        } catch (PDOException $e) {
            error_log("Database Error in getAllCoursesTaughtByTutor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all courses a tutor teaches with subject area.
     * Used for the session request modal.
     * @param int $userID The ID of the tutor.
     * @return array List of courses with courseID, courseName, subjectArea.
     */
    public function getTutorCoursesWithSubjectArea($userID)
    {
        $sql = "SELECT 
                    c.courseID, 
                    c.courseName, 
                    c.subjectArea 
                FROM 
                    tutorcourses tc
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
     * Saves courses for a tutor using a transaction.
     * @param int $userID
     * @param array $courseIDs
     * @return bool True on success, False on failure.
     */
    public function saveCourses($userID, array $courseIDs)
    {
        $dbConnection = $this->db->connect();

        try {
            $dbConnection->beginTransaction();

            // Delete existing courses
            $deleteSql = "DELETE FROM tutorcourses WHERE userID = :userID";
            $deleteQuery = $dbConnection->prepare($deleteSql);
            $deleteQuery->execute([':userID' => $userID]);

            // Insert new courses
            if (!empty($courseIDs)) {
                $insertSql = "INSERT INTO tutorcourses (userID, courseID) VALUES (:userID, :courseID)";
                $insertQuery = $dbConnection->prepare($insertSql);

                foreach ($courseIDs as $courseID) {
                    $insertQuery->execute([
                        ':userID' => $userID,
                        ':courseID' => intval($courseID)
                    ]);
                }
            }

            $dbConnection->commit();
            return true;

        } catch (PDOException $e) {
            $dbConnection->rollBack();
            error_log("Course Save Error: " . $e->getMessage());
            return false;
        }
    }
}
?>