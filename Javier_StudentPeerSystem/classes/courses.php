<?php

require_once 'database.php';

class Course {
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Fetches all courses for the filter dropdown.
     * @return array List of courses.
     */
    public function getAllCourses() {
        $sql = "SELECT courseID, courseName, subjectArea FROM courses ORDER BY subjectArea, courseName";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Error handling or logging
            error_log("Database Error in getAllCourses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserts a new course into the database.
     * Use this to add MathMod, PathFit, and Web Development.
     * @param string $courseName
     * @param string $subjectArea
     * @return bool True on success, False on failure.
     */
    public function addCourse($courseName, $subjectArea) {
        $sql = "INSERT INTO courses (courseName, subjectArea) VALUES (:courseName, :subjectArea)";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':courseName', $courseName);
            $query->bindParam(':subjectArea', $subjectArea);
            return $query->execute();
        } catch (PDOException $e) {
            error_log("Database Error in addCourse: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the name of a course based on its ID.
     * @param int $courseID
     * @return string The course name or a default message.
     */
    public function getCourseNameByID($courseID) {
        $sql = "SELECT courseName FROM courses WHERE courseID = :courseID";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':courseID', $courseID, PDO::PARAM_INT);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['courseName'] : 'Unknown Course';
        } catch (PDOException $e) {
            error_log("Database Error in getCourseNameByID: " . $e->getMessage());
            return 'Unknown Course';
        }
    }
}
?>
