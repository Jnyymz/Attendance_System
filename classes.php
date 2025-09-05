<?php

class Database {
    private $host = "localhost";
    private $db_name = "secondact";   
    private $username = "root";      
    private $password = "";           
    protected $conn;

    public function __construct() {
        $this->connectDB();
    }

    private function connectDB() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
    public function getConnection() {
        return $this->conn;
    }
}


class User extends Database {
    public $id;
    public $username;
    public $role;

    public function login($username, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $this->id = $result['id'];
            $this->username = $result['username'];
            $this->role = $result['role'];
            return true;
        }
        return false;
    }
}


class Student extends User {
    private $student_id;

    public function __construct($student_id) {
        parent::__construct();
        $this->student_id = $student_id;
    }


    public function viewAttendance() {
        $stmt = $this->conn->prepare("
            SELECT date, time_in, status, is_late
            FROM attendance
            WHERE student_id=? ORDER BY date DESC
        ");
        $stmt->bind_param("i", $this->student_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}


class Admin extends User {
    public function addCourse($courseName) {
        $stmt = $this->conn->prepare("INSERT INTO courses (course_name) VALUES (?)");
        $stmt->bind_param("s", $courseName);
        return $stmt->execute();
    }

    public function viewAttendanceByCourse($course_id, $year_level) {
        $stmt = $this->conn->prepare("
            SELECT u.username, c.course_name, s.year_level,
                   a.date, a.time_in, a.is_late
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN courses c ON s.course_id = c.id
            JOIN attendance a ON s.id = a.student_id
            WHERE s.course_id=? AND s.year_level=?
            ORDER BY a.date DESC
        ");
        $stmt->bind_param("ii", $course_id, $year_level);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>

