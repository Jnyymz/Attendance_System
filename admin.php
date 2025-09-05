<?php
session_start();
require_once "classes.php";


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_name = trim($_POST['course_name']);
    $course_time = $_POST['course_time'];

    if (!empty($course_name) && !empty($course_time)) {
        $stmt = $conn->prepare("INSERT INTO courses (course_name, course_time) VALUES (?, ?)");
        $stmt->bind_param("ss", $course_name, $course_time);
        $stmt->execute();
    }
}


if (isset($_GET['delete_course'])) {
    $course_id = intval($_GET['delete_course']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}


$courses = $conn->query("SELECT * FROM courses");


$attendance = $conn->query("
    SELECT a.id, u.username, c.course_name AS course, c.course_time, 
           s.year_level, a.date, a.status, a.is_late
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON s.course_id = c.id
    ORDER BY c.course_name, s.year_level, a.date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#FDEBD0] min-h-screen flex flex-col font-sans">
  
  <!-- Header -->
  <header class="bg-[#DC143C] text-white p-4 flex justify-between items-center shadow-md">
    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
    <a href="login.php" class="bg-white text-[#DC143C] font-semibold px-4 py-2 rounded-lg hover:bg-[#F7CAC9] transition">
      Logout
    </a>
  </header>

  <main class="flex-1 p-6 space-y-8">
    
    <!-- Add Course -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#F75270]">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">➕ Add New Course</h2>
      <form method="POST" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="course_name" placeholder="Course Name" required 
          class="flex-1 p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
        <input type="time" name="course_time" required 
          class="p-2 border border-[#F7CAC9] rounded focus:outline-none focus:ring-2 focus:ring-[#F75270]">
        <button type="submit" name="add_course" 
          class="bg-[#DC143C] text-white px-4 py-2 rounded-lg hover:bg-[#F75270] transition">
          Add
        </button>
      </form>
    </div>

    <!-- Course List -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#F7CAC9]">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">📚 All Courses</h2>
      <ul class="divide-y divide-[#F7CAC9]">
        <?php while ($row = $courses->fetch_assoc()): ?>
          <li class="py-2 flex justify-between items-center">
            <span>
              <span class="font-semibold text-gray-700"><?= htmlspecialchars($row['course_name']) ?></span>
              <span class="text-sm text-gray-500">
                (<?= $row['course_time'] ? date("h:i A", strtotime($row['course_time'])) : "No time set" ?>)
              </span>
            </span>
            <a href="admin.php?delete_course=<?= $row['id'] ?>" 
               class="text-[#DC143C] font-medium hover:text-[#F75270]">Delete</a>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#DC143C]">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">📝 Attendance Records</h2>
      <div class="overflow-x-auto">
        <table class="w-full border border-[#F7CAC9] rounded-lg overflow-hidden text-sm">
          <thead>
            <tr class="bg-[#F7CAC9] text-left text-[#DC143C] font-semibold">
              <th class="px-4 py-2">Student</th>
              <th class="px-4 py-2">Course</th>
              <th class="px-4 py-2">Schedule</th>
              <th class="px-4 py-2">Year Level</th>
              <th class="px-4 py-2">Date</th>
              <th class="px-4 py-2">Status</th>
              <th class="px-4 py-2">Late</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#F7CAC9]">
            <?php while ($row = $attendance->fetch_assoc()): ?>
              <tr class="hover:bg-[#FDEBD0] transition">
                <td class="px-4 py-2"><?= htmlspecialchars($row['username']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['course']) ?></td>
                <td class="px-4 py-2"><?= $row['course_time'] ? date("h:i A", strtotime($row['course_time'])) : "-" ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['year_level']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['date']) ?></td>
                <td class="px-4 py-2">
                  <span class="px-2 py-1 rounded text-white 
                    <?= $row['status'] === 'Present' ? 'bg-[#DC143C]' : 'bg-gray-500' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td class="px-4 py-2">
                  <?= $row['is_late'] ? '<span class="text-[#F75270] font-medium">Yes</span>' : 'No' ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</body>
</html>

