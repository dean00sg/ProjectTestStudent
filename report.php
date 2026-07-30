<?php
require 'db.php';

$students = $conn->query('SELECT student_code, CONCAT(student_fname_th," ",student_sname_th) AS name FROM student ORDER BY student_code');

$selected = $_GET['code'] ?? '';
$student  = null;
$grades   = [];
$gpa      = null;

if ($selected !== '') {
    $code    = $conn->real_escape_string($selected);
    $student = $conn->query("
        SELECT s.*, CONCAT(s.student_fname_th,' ',s.student_sname_th) AS name_th,
               CONCAT(s.student_fname_eng,' ',s.student_sname_eng) AS name_eng
        FROM student s WHERE student_code='$code'")->fetch_assoc();

    $result = $conn->query("
        SELECT g.*, sub.subject_name, sub.credits
        FROM grade g
        LEFT JOIN subject sub ON sub.subject_code = g.subject_code
        WHERE g.student_code='$code'
        ORDER BY g.academic_year, g.semester, g.subject_code
    ");
    while ($r = $result->fetch_assoc()) $grades[] = $r;

    // GPA calculation
    $total_gp = 0; $total_credits = 0;
    foreach ($grades as $g) {
        $total_gp      += $g['grade_point'] * $g['credits'];
        $total_credits += $g['credits'];
    }
    $gpa = $total_credits > 0 ? $total_gp / $total_credits : null;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>รายงาน GPA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">ระบบเก็บเกรด</a>
    <div class="navbar-nav flex-row gap-3">
      <a class="nav-link text-white" href="students.php">นักศึกษา</a>
      <a class="nav-link text-white" href="subjects.php">วิชา</a>
      <a class="nav-link text-white" href="grades.php">บันทึกเกรด</a>
      <a class="nav-link text-white" href="report.php">รายงาน GPA</a>
    </div>
  </div>
</nav>
<div class="container py-4">
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label fw-bold">เลือกนักศึกษา</label>
          <select name="code" class="form-select" onchange="this.form.submit()">
            <option value="">-- เลือกนักศึกษา --</option>
            <?php while ($s = $students->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($s['student_code']) ?>"
              <?= $selected === $s['student_code'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['student_code'] . ' - ' . $s['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <?php if ($student): ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          <h5 class="fw-bold"><?= htmlspecialchars($student['name_th']) ?></h5>
          <p class="text-muted mb-1"><?= htmlspecialchars($student['name_eng']) ?></p>
          <p class="mb-0">รหัส: <strong><?= htmlspecialchars($student['student_code']) ?></strong>
            &nbsp;|&nbsp; ห้อง: <strong><?= htmlspecialchars($student['class_room']) ?></strong></p>
        </div>
        <div class="col-md-4 text-end">
          <?php if ($gpa !== null): ?>
          <div class="display-5 fw-bold text-<?= $gpa >= 3.5 ? 'success' : ($gpa >= 2.0 ? 'primary' : 'danger') ?>">
            <?= number_format($gpa, 2) ?>
          </div>
          <div class="text-muted">GPA (<?= array_sum(array_column($grades,'credits')) ?> หน่วยกิต)</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php
  // Group by semester/year
  $grouped = [];
  foreach ($grades as $g) {
      $key = $g['academic_year'] . '_' . $g['semester'];
      $grouped[$key][] = $g;
  }
  foreach ($grouped as $key => $list):
      [$yr, $sem] = explode('_', $key);
      $sem_gp  = array_sum(array_map(fn($g) => $g['grade_point'] * $g['credits'], $list));
      $sem_cr  = array_sum(array_column($list, 'credits'));
      $sem_gpa = $sem_cr > 0 ? $sem_gp / $sem_cr : 0;
  ?>
  <div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <span class="fw-bold">ภาคเรียนที่ <?= $sem ?> / <?= $yr ?></span>
      <span class="text-muted">GPA ภาคนี้: <strong><?= number_format($sem_gpa, 2) ?></strong></span>
    </div>
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead class="table-light">
          <tr><th>รหัสวิชา</th><th>ชื่อวิชา</th><th class="text-center">หน่วยกิต</th><th class="text-center">คะแนน</th><th class="text-center">เกรด</th><th class="text-center">จุด</th></tr>
        </thead>
        <tbody>
          <?php foreach ($list as $g): ?>
          <tr>
            <td><?= htmlspecialchars($g['subject_code']) ?></td>
            <td><?= htmlspecialchars($g['subject_name']) ?></td>
            <td class="text-center"><?= $g['credits'] ?></td>
            <td class="text-center"><?= $g['score'] ?></td>
            <td class="text-center">
              <span class="badge bg-<?= $g['grade'] === 'F' ? 'danger' : ($g['grade_point'] >= 3.5 ? 'success' : 'secondary') ?>">
                <?= $g['grade'] ?>
              </span>
            </td>
            <td class="text-center"><?= $g['grade_point'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <?php elseif ($selected === ''): ?>
  <div class="text-center text-muted py-5">เลือกนักศึกษาเพื่อดูรายงานเกรด</div>
  <?php else: ?>
  <div class="alert alert-warning">ไม่พบนักศึกษา</div>
  <?php endif; ?>
</div>
</body>
</html>
