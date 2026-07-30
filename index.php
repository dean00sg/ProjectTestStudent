<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ระบบเก็บเกรด</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">ระบบเก็บเกรด</a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link text-white" href="students.php">นักศึกษา</a>
      <a class="nav-link text-white" href="subjects.php">วิชา</a>
      <a class="nav-link text-white" href="grades.php">บันทึกเกรด</a>
      <a class="nav-link text-white" href="report.php">รายงาน GPA</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h4 class="mb-4">ภาพรวมระบบ</h4>
  <div class="row g-3">
    <?php
    $stats = [
      ['label' => 'นักศึกษาทั้งหมด', 'color' => 'primary',   'sql' => 'SELECT COUNT(*) FROM student'],
      ['label' => 'วิชาทั้งหมด',     'color' => 'success',   'sql' => 'SELECT COUNT(*) FROM subject'],
      ['label' => 'เกรดที่บันทึก',   'color' => 'warning',   'sql' => 'SELECT COUNT(*) FROM grade'],
    ];
    foreach ($stats as $s):
      $count = $conn->query($s['sql'])->fetch_row()[0];
    ?>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-4">
          <h2 class="fw-bold text-<?= $s['color'] ?>"><?= $count ?></h2>
          <p class="text-muted mb-0"><?= $s['label'] ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <h5 class="mt-5 mb-3">นักศึกษาล่าสุด</h5>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>รหัสนักศึกษา</th>
            <th>ชื่อ-สกุล (ไทย)</th>
            <th>ห้อง</th>
            <th>อีเมล</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = $conn->query('SELECT * FROM student ORDER BY student_id DESC LIMIT 10');
          while ($r = $rows->fetch_assoc()):
          ?>
          <tr>
            <td><?= htmlspecialchars($r['student_code']) ?></td>
            <td><?= htmlspecialchars($r['student_fname_th'] . ' ' . $r['student_sname_th']) ?></td>
            <td><?= htmlspecialchars($r['class_room']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><a href="report.php?code=<?= urlencode($r['student_code']) ?>" class="btn btn-sm btn-outline-primary">ดูเกรด</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
