<?php
session_start();
require_once "classes.php"; 

$db = new Database();
$conn = $db->getConnection();

// Fetch courses for the student dropdown
$courses = $conn->query("SELECT id, course_name FROM courses");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; // student or admin

    // Insert into users table
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id; 

        // If role is student, insert additional details into students table
        if ($role === "student") {
            $student_num = $_POST['student_num'];
            $course = $_POST['course']; // this will be course_id
            $year_level = $_POST['year_level'];

            $stmt2 = $conn->prepare("INSERT INTO students (user_id, student_num, course_id, year_level) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isii", $user_id, $student_num, $course, $year_level);
            $stmt2->execute();
        }

        header("Location: login.php");
        exit;
    } else {
        $error = "Registration failed. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function toggleStudentFields() {
      const role = document.getElementById("role").value;
      const studentFields = document.querySelectorAll(".student-fields");
      studentFields.forEach(field => {
        field.style.display = (role === "student") ? "block" : "none";
      });
    }
    // Run once on page load (so it hides fields if Admin is selected by default)
    window.onload = toggleStudentFields;
  </script>
</head>
<body class="bg-[#FDEBD0] flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-2xl shadow-lg w-96 border border-[#F7CAC9]">
    <h2 class="text-2xl font-bold mb-6 text-center text-[#DC143C]">Register</h2>

    <?php if (!empty($error)): ?>
      <p class="text-[#DC143C] text-sm mb-3 bg-[#F7CAC9] p-2 rounded text-center"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-[#DC143C]">Username</label>
        <input type="text" name="username" required 
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
      </div>

      <div>
        <label class="block text-sm font-medium text-[#DC143C]">Password</label>
        <input type="password" name="password" required 
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
      </div>

      <div>
        <label class="block text-sm font-medium text-[#DC143C]">Role</label>
        <select name="role" id="role" required onchange="toggleStudentFields()"
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
          <option value="student">Student</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <!-- Student-specific fields -->
      <div class="student-fields">
        <label class="block text-sm font-medium text-[#DC143C]">Student Number</label>
        <input type="text" name="student_num" 
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
      </div>

      <div class="student-fields">
        <label class="block text-sm font-medium text-[#DC143C]">Course</label>
        <select name="course" 
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
          <option value="">-- Select Course --</option>
          <?php while ($row = $courses->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['course_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="student-fields">
        <label class="block text-sm font-medium text-[#DC143C]">Year Level</label>
        <select name="year_level" 
          class="w-full p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
          <option value="1">1st Year</option>
          <option value="2">2nd Year</option>
          <option value="3">3rd Year</option>
          <option value="4">4th Year</option>
        </select>
      </div>

      <button type="submit" 
        class="w-full bg-[#DC143C] text-white py-2 rounded-lg hover:bg-[#F75270] transition-colors">
        Register
      </button>
    </form>

    <p class="mt-6 text-sm text-center text-gray-600">
      Already have an account? 
      <a href="login.php" class="text-[#F75270] font-medium hover:underline">Login</a>
    </p>
  </div>
</body>
</html>
