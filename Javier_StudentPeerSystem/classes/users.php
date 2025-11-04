<?php
require_once "database.php"; 

class User {
   
    public $email;
    public $password; 
    public $firstName;
    public $lastName;
    
    protected $db; 

    public function __construct(){
        $this->db = new Database();
    }
    /**
     * Registers a new user with default roles (student active, tutor inactive).
     * @return bool True on success, False on failure.
     */
    public function registerUser(){
        // Set necessary default values
        $creationDate = date('Y-m-d H:i:s'); 
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $isActive = 1; 
        $isTutorNow = 0;   // Default: Not an active tutor
        $isStudentNow = 1; // Default: Always a student

        $sql = "INSERT INTO users (email, password, firstName, lastName, creationDate, isActive, isTutorNow, isStudentNow) 
                VALUES (:email, :password, :firstName, :lastName, :creationDate, :isActive, :isTutorNow, :isStudentNow)";
        
        try {
            $query = $this->db->connect()->prepare($sql);
            
            // Bind parameters
            $query->bindParam(':email', $this->email);
            $query->bindParam(':password', $hashedPassword); // Store the HASH
            $query->bindParam(':firstName', $this->firstName);
            $query->bindParam(':lastName', $this->lastName);
            $query->bindParam(':creationDate', $creationDate);
            $query->bindParam(':isActive', $isActive, PDO::PARAM_INT);
            $query->bindParam(':isTutorNow', $isTutorNow, PDO::PARAM_INT);
            $query->bindParam(':isStudentNow', $isStudentNow, PDO::PARAM_INT);
            
            return $query->execute();
        } catch (PDOException $e) {
            // Check for duplicate entry error (e.g., duplicate email)
            if ($e->getCode() == '23000') {
                // You might want to log this or handle it more gracefully
                error_log("Registration Error (Duplicate Email): " . $e->getMessage());
            } else {
                error_log("Registration Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Checks if a user's email already exists in the database.
     * @return bool True if exists, False otherwise.
     */
    public function emailExists() {
        $sql = "SELECT userID FROM users WHERE email = :email LIMIT 1";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':email', $this->email);
            $query->execute();
            return $query->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("Database Error in emailExists: " . $e->getMessage());
            return true; // Assume true on error for safety (prevent registration)
        }
    }

/**
     * Attempts to log in a user.
     * @param string $email
     * @param string $password
     * @return array|false The user's row data on success, or false on failure.
     */
    public function login($email, $password) {
        $sql = "SELECT userID, email, password, firstName, lastName, isTutorNow, isActive FROM users WHERE email = :email AND isActive = 1";
        
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':email', $email);
            $query->execute();
            $user = $query->fetch(PDO::FETCH_ASSOC);

            // Verify if user exists and check password against the hash
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']); 
                return $user; 
            } else {
                return false; 
            }
        } catch (PDOException $e) {
            error_log("Database Error in login: " . $e->getMessage()); 
            return false;
        }
    }
    
    /**
     * Gets the full name of a user based on their ID.
     */
    public function getUserFullNameByID($userID) {
        $sql = "SELECT firstName, lastName FROM users WHERE userID = :userID";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ? htmlspecialchars($result['firstName']) . ' ' . htmlspecialchars($result['lastName']) : 'Unknown User';
        } catch (PDOException $e) {
            return 'Unknown User';
        }
    }

    /**
     * Toggles a user's role status (isTutorNow or isStudentNow flag).
     */
    public function toggleRole($userID, $role, $status){
        $field = '';
        if (strtolower($role) === 'tutor') {
            $field = 'isTutorNow';
        } elseif (strtolower($role) === 'student') {
            $field = 'isStudentNow';
        } else {
            return false;
        }

        $sql = "UPDATE users SET {$field} = :status WHERE userID = :userID";
        
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':status', $status, PDO::PARAM_INT);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            return $query->execute();
        } catch (PDOException $e) {
            error_log("Database Error in toggleRole: " . $e->getMessage());
            return false;
        }
    }
}
?>