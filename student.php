<?php
session_start();
require_once "classes.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];


$stmt = $conn->prepare("
    SELECT s.id AS student_id, s.student_num, s.year_level, u.username, c.course_name, c.course_time
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON s.course_id = c.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found.");
}

$student_id = $student['student_id'];
$course_time = $student['course_time'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['mark_attendance'])) {
    $date = $_POST['date'];
    $status = $_POST['status'];

    if ($status === "Absent") {
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, time_in, is_late) VALUES (?, ?, ?, NULL, NULL)");
        $stmt->bind_param("iss", $student_id, $date, $status);
    } else {
        $time_in = $_POST['time_in'];

        $is_late = 0;
        if ($course_time && strtotime($time_in) > strtotime($course_time) + (15 * 60)) {
            $is_late = 1;
        }

        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, time_in, is_late) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $student_id, $date, $status, $time_in, $is_late);
    }

    $stmt->execute();
    header("Location: student.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT date, time_in, status, is_late 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#FDEBD0] min-h-screen font-sans">
  <div class="max-w-5xl mx-auto p-6">
    
    <!-- Header -->
    <header class="bg-[#DC143C] text-white px-6 py-4 rounded-2xl shadow-lg flex justify-between items-center mb-8">
      <h1 class="text-2xl font-bold">🎓 My Attendance Dashboard</h1>
      <a href="login.php" class="bg-white text-[#DC143C] px-4 py-2 rounded-lg font-semibold hover:bg-[#F7CAC9] transition">
        Logout
      </a>
    </header>

    <!-- Student Details -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#F75270] mb-8">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">👤 Student Details</h2>
      <div class="grid sm:grid-cols-2 gap-3 text-gray-700">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['username']) ?></p>
        <p><strong>Student Number:</strong> <?= htmlspecialchars($student['student_num']) ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($student['course_name']) ?></p>
        <p><strong>Year Level:</strong> <?= htmlspecialchars($student['year_level']) ?></p>
        <p><strong>Course Time:</strong> <?= $course_time ? date("h:i A", strtotime($course_time)) : "Not Set" ?></p>
      </div>
    </div>

    <!-- Attendance Button -->
    <div class="mb-6">
      <button onclick="document.getElementById('attendanceForm').classList.toggle('hidden')" 
              class="bg-[#DC143C] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#F75270] transition">
        Mark Attendance
      </button>
    </div>

    <!-- Attendance Form -->
    <div id="attendanceForm" class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#F7CAC9] mb-8 hidden">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">📝 Mark Attendance</h2>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block font-medium text-[#DC143C]">Date</label>
          <input type="date" name="date" required 
            class="p-2 border border-[#F7CAC9] rounded w-full focus:outline-none focus:ring-2 focus:ring-[#F75270]">
        </div>
        <div>
          <label class="block font-medium text-[#DC143C]">Time In</label>
          <input type="time" name="time_in" 
            class="p-2 border border-[#F7CAC9] rounded w-full focus:outline-none focus:ring-2 focus:ring-[#F75270]">
        </div>
        <div>
          <label class="block font-medium text-[#DC143C]">Status</label>
          <select name="status" required onchange="toggleTimeIn(this.value)"
            class="p-2 border border-[#F7CAC9] rounded w-full focus:outline-none focus:ring-2 focus:ring-[#F75270]">
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
          </select>
        </div>
        <button type="submit" name="mark_attendance" 
          class="bg-[#DC143C] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#F75270] transition">
          Submit
        </button>
      </form>
    </div>

    <!-- Attendance History -->
    <div class="bg-white p-6 rounded-2xl shadow-lg border-l-4 border-[#DC143C]">
      <h2 class="text-lg font-bold mb-4 text-[#DC143C]">📊 My Attendance History</h2>
      <div class="overflow-x-auto">
        <table class="w-full border border-[#F7CAC9] rounded-lg overflow-hidden text-sm">
          <thead>
            <tr class="bg-[#F7CAC9] text-left text-[#DC143C] font-semibold">
              <th class="p-3">Date</th>
              <th class="p-3">Time In</th>
              <th class="p-3">Status</th>
              <th class="p-3">Late</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#F7CAC9]">
            <?php while ($row = $attendance->fetch_assoc()): ?>
              <tr class="hover:bg-[#FDEBD0] transition">
                <td class="p-3"><?= htmlspecialchars($row['date']) ?></td>
                <td class="p-3"><?= $row['time_in'] ? htmlspecialchars($row['time_in']) : "-" ?></td>
                <td class="p-3">
                  <span class="px-2 py-1 rounded text-white 
                    <?= $row['status'] === 'Present' ? 'bg-[#DC143C]' : 'bg-gray-500' ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td class="p-3">
                  <?php 
                    if ($row['status'] === "Absent") {
                        echo "-";
                    } else {
                        echo $row['is_late'] 
                          ? '<span class="text-[#F75270] font-bold">Yes</span>' 
                          : '<span class="text-green-600">No</span>';
                    }
                  ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script>
    function toggleTimeIn(status) {
      const timeInput = document.querySelector("input[name='time_in']");
      if (status === "Absent") {
        timeInput.disabled = true;
        timeInput.value = "";
      } else {
        timeInput.disabled = false;
      }
    }
  </script>
</body>
</html>


