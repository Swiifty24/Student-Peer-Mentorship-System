<?php
require_once "database.php";

class User
{

    public $email;
    public $password;
    public $firstName;
    public $lastName;

    protected $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Registers a new user with default roles (student active, tutor inactive).
     * @return bool True on success, False on failure.
     */
    public function registerUser()
    {
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
            $query->bindParam(':password', $hashedPassword);
            $query->bindParam(':firstName', $this->firstName);
            $query->bindParam(':lastName', $this->lastName);
            $query->bindParam(':creationDate', $creationDate);
            $query->bindParam(':isActive', $isActive, PDO::PARAM_INT);
            $query->bindParam(':isTutorNow', $isTutorNow, PDO::PARAM_INT);
            $query->bindParam(':isStudentNow', $isStudentNow, PDO::PARAM_INT);

            return $query->execute();
        } catch (PDOException $e) {
            // Check for duplicate entry error
            if ($e->getCode() == '23000') {
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
    public function emailExists()
    {
        $sql = "SELECT userID FROM users WHERE email = :email LIMIT 1";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':email', $this->email);
            $query->execute();
            return $query->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("Database Error in emailExists: " . $e->getMessage());
            return true; // Assume true on error for safety
        }
    }

    /**
     * Attempts to log in a user.
     * @param string $email
     * @param string $password
     * @return array|false The user's row data on success, or false on failure.
     */
    public function login($email, $password)
    {
        $sql = "SELECT userID, email, password, firstName, lastName, isTutorNow, isActive, isVerified 
                FROM users 
                WHERE email = :email AND isActive = 1";

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
     * Gets a user by their ID.
     * ADDED METHOD - Required by enrollments.php
     * @param int $userID
     * @return array|false User data or false
     */
    public function getUserByID($userID)
    {
        $sql = "SELECT userID, email, firstName, lastName, isTutorNow, isStudentNow, isActive 
                FROM users 
                WHERE userID = :userID LIMIT 1";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error in getUserByID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets the full name of a user based on their ID.
     * @param int $userID
     * @return string Full name or 'Unknown User'
     */
    public function getUserFullNameByID($userID)
    {
        $sql = "SELECT firstName, lastName FROM users WHERE userID = :userID";
        try {
            $query = $this->db->connect()->prepare($sql);
            $query->bindParam(':userID', $userID, PDO::PARAM_INT);
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result ? htmlspecialchars($result['firstName']) . ' ' . htmlspecialchars($result['lastName']) : 'Unknown User';
        } catch (PDOException $e) {
            error_log("Database Error in getUserFullNameByID: " . $e->getMessage());
            return 'Unknown User';
        }
    }

    /**
     * Toggles a user's role status (isTutorNow or isStudentNow flag).
     * @param int $userID
     * @param string $role 'tutor' or 'student'
     * @param int $status 0 or 1
     * @return bool True on success, False on failure
     */
    public function toggleRole($userID, $role, $status)
    {
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