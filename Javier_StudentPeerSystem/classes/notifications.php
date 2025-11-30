<?php

require_once 'database.php';

class Notification {
    private $conn;
    private $table = 'notifications';
    
    public $notificationID;
    public $userID;
    public $type;
    public $message;
    public $relatedID;
    public $isRead;
    public $createdAt;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    public function create($userID, $type, $message, $relatedID = null) {
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (userID, type, message, relatedID, isRead, createdAt) 
                      VALUES (:userID, :type, :message, :relatedID, 0, NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':userID', $userID);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':relatedID', $relatedID);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserNotifications($userID, $unreadOnly = false) {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      WHERE userID = :userID";
            
            if ($unreadOnly) {
                $query .= " AND isRead = 0";
            }
            
            $query .= " ORDER BY createdAt DESC LIMIT 50";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUnreadCount($userID) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                      WHERE userID = :userID AND isRead = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function markAsRead($notificationID) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET isRead = 1 
                      WHERE notificationID = :notificationID";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':notificationID', $notificationID);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    
    public function markAllAsRead($userID) {
        try {
            $query = "UPDATE " . $this->table . " 
                      SET isRead = 1 
                      WHERE userID = :userID AND isRead = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteOldNotifications() {
        try {
            $query = "DELETE FROM " . $this->table . " 
                      WHERE createdAt < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete old notifications error: " . $e->getMessage());
            return false;
        }
    }
}
?>