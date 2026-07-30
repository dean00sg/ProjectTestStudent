<?php
require 'db.php';

function calcGrade(float $score): array {
    if ($score >= 80) return ['A',  4.0];
    if ($score >= 75) return ['B+', 3.5];
    if ($score >= 70) return ['B',  3.0];
    if ($score >= 65) return ['C+', 2.5];
    if ($score >= 60) return ['C',  2.0];
    if ($score >= 55) return ['D+', 1.5];
    if ($score >= 50) return ['D',  1.0];
    return ['F', 0.0];
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM grade WHERE grade_id=$id");
    header('Location: grades.php?msg=deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_code = $conn->real_escape_string(trim($_POST['student_code']));
    $subject_code = $conn->real_escape_string(trim($_POST['subject_code']));
    $score        = (float)$_POST['score'];
    $semester     = (int)$_POST['semester'];
    $year         = (int)$_POST['academic_year'];

    [$grade, $gp] = calcGrade($score);

    if ($_POST['action'] === 'add') {
        $conn->query("INSERT INTO grade (student_code,subject_code,score,grade,grade_point,semester,academic_year)
                      VALUES ('$student_code','$subject_code',$score,'$grade',$gp,$semester,$year)
                      ON DUPLICATE KEY UPDATE score=$score,grade='$grade',grade_point=$gp");
    } else {
        $id = (int)$_POST['grade_id'];
        $conn->query("UPDATE grade SET student_code='$student_code',subject_code='$subject_code',
                      score=$score,grade='$grade',grade_point=$gp,semester=$semester,academic_year=$year
                      WHERE grade_id=$id");
    }
    header('Location: grades.php?msg=saved');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM grade WHERE grade_id=$id")->fetch_assoc();
}

$students = $conn->query('SELECT student_code, CONCAT(student_fname_th," ",student_sname_th) AS name FROM student ORDER BY student_code');
$subjects = $conn->query('SELECT subject_code, subject_name FROM subject ORDER BY subject_code');

$msg = $_GET['msg'] ?? '';
$year_now = (int)date('Y') + 543;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>บันทึกเกรด</title>
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
  <?php if ($msg === 'saved'): ?><div class="alert alert-success">บันทึกเกรดสำเร็จ</div><?php endif; ?>
  <?php if ($msg === 'deleted'): ?><div class="alert alert-warning">ลบเกรดสำเร็จ</div><?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold"><?= $edit ? 'แก้ไขเกรด' : 'บันทึกเกรด' ?></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="grade_id" value="<?= $edit['grade_id'] ?>"><?php endif; ?>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">นักศึกษา</label>
            <select name="student_code" class="form-select" required>
              <option value="">-- เลือกนักศึกษา --</option>
              <?php $students->data_seek(0); while ($s = $students->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($s['student_code']) ?>"
                <?= ($edit['student_code'] ?? '') === $s['student_code'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['student_code'] . ' - ' . $s['name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">วิชา</label>
            <select name="subject_code" class="form-select" required>
              <option value="">-- เลือกวิชา --</option>
              <?php $subjects->data_seek(0); while ($sub = $subjects->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($sub['subject_code']) ?>"
                <?= ($edit['subject_code'] ?? '') === $sub['subject_code'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($sub['subject_code'] . ' - ' . $sub['subject_name']) ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">คะแนน (0-100)</label>
            <input name="score" type="number" step="0.01" min="0" max="100" class="form-control" required
                   value="<?= $edit['score'] ?? '' ?>"
                   oninput="previewGrade(this.value)">
          </div>
          <div class="col-md-1">
            <label class="form-label">เกรด</label>
            <input id="preview_grade" class="form-control bg-light" readonly value="<?= $edit['grade'] ?? '' ?>">
          </div>
          <div class="col-md-1">
            <label class="form-label">ภาค</label>
            <select name="semester" class="form-select">
              <option value="1" <?= ($edit['semester'] ?? 1) == 1 ? 'selected' : '' ?>>1</option>
              <option value="2" <?= ($edit['semester'] ?? '') == 2 ? 'selected' : '' ?>>2</option>
              <option value="3" <?= ($edit['semester'] ?? '') == 3 ? 'selected' : '' ?>>3</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">ปีการศึกษา</label>
            <input name="academic_year" type="number" class="form-control" required
                   value="<?= $edit['academic_year'] ?? $year_now ?>">
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary"><?= $edit ? 'บันทึกการแก้ไข' : 'บันทึกเกรด' ?></button>
          <?php if ($edit): ?><a href="grades.php" class="btn btn-secondary">ยกเลิก</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">เกรดทั้งหมด</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>รหัสนักศึกษา</th>
            <th>ชื่อนักศึกษา</th>
            <th>วิชา</th>
            <th>คะแนน</th>
            <th>เกรด</th>
            <th>จุด</th>
            <th>ภาค/ปี</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = $conn->query("
            SELECT g.*, CONCAT(s.student_fname_th,' ',s.student_sname_th) AS sname, sub.subject_name
            FROM grade g
            LEFT JOIN student s ON s.student_code = g.student_code
            LEFT JOIN subject sub ON sub.subject_code = g.subject_code
            ORDER BY g.academic_year DESC, g.semester DESC, g.student_code
          ");
          while ($r = $rows->fetch_assoc()):
          ?>
          <tr>
            <td><?= htmlspecialchars($r['student_code']) ?></td>
            <td><?= htmlspecialchars($r['sname']) ?></td>
            <td><?= htmlspecialchars($r['subject_code'] . ' ' . $r['subject_name']) ?></td>
            <td><?= $r['score'] ?></td>
            <td><span class="badge bg-<?= $r['grade'] === 'F' ? 'danger' : ($r['grade_point'] >= 3.5 ? 'success' : 'secondary') ?>">
              <?= $r['grade'] ?></span></td>
            <td><?= $r['grade_point'] ?></td>
            <td><?= $r['semester'] ?>/<?= $r['academic_year'] ?></td>
            <td>
              <a href="grades.php?edit=<?= $r['grade_id'] ?>" class="btn btn-sm btn-outline-secondary">แก้ไข</a>
              <a href="grades.php?delete=<?= $r['grade_id'] ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('ลบเกรดนี้?')">ลบ</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function previewGrade(score) {
  score = parseFloat(score);
  let g = 'F';
  if (score >= 80) g = 'A';
  else if (score >= 75) g = 'B+';
  else if (score >= 70) g = 'B';
  else if (score >= 65) g = 'C+';
  else if (score >= 60) g = 'C';
  else if (score >= 55) g = 'D+';
  else if (score >= 50) g = 'D';
  document.getElementById('preview_grade').value = isNaN(score) ? '' : g;
}
</script>
</body>
</html>
