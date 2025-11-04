<?php

require_once "database.php";

class TutorProfile {
    // Properties matching your database table columns
    public $userID;
    public $tutorBio;
    public $hourlyRate;
    public $availabilityDetails;
    
    protected $db;

    public function __construct(){
        $this->db = new Database();
    }

    public function saveProfile(){
        $sql = "INSERT INTO tutorprofiles (userID, tutorBio, hourlyRate, availabilityDetails) 
                VALUES (:userID, :tutorBio, :hourlyRate, :availabilityDetails)
                ON DUPLICATE KEY UPDATE 
                tutorBio = VALUES(tutorBio), 
                hourlyRate = VALUES(hourlyRate),
                availabilityDetails = VALUES(availabilityDetails)"; 

        try {
            $query = $this->db->connect()->prepare($sql);
            
            $query->bindParam(':userID', $this->userID, PDO::PARAM_INT);
            $query->bindParam(':tutorBio', $this->tutorBio, PDO::PARAM_STR);
            $query->bindParam(':hourlyRate', $this->hourlyRate); 
            $query->bindParam(':availabilityDetails', $this->availabilityDetails, PDO::PARAM_STR);

            return $query->execute();
        } catch (PDOException $e) {
            error_log("Database Error in saveProfile: " . $e->getMessage());
            return false;
        }
    }

    public function getProfile($userID){
        try {
            $sql = "SELECT * FROM tutorprofiles WHERE userID = :userID LIMIT 1";
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getProfile: " . $e->getMessage());
            return false;
        }
    }

    public function getAllActiveTutors(){
        $sql = "SELECT 
                    u.userID, 
                    u.firstName, 
                    u.lastName,
                    tp.tutorBio, 
                    tp.hourlyRate, 
                    tp.availabilityDetails
                FROM users u
                JOIN tutorProfiles tp ON u.userID = tp.userID
                WHERE u.isTutorNow = 1
                ORDER BY u.lastName";
        
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->execute();
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Get Active Tutors Error: " . $e->getMessage();
            return [];
        }
    }
}