<?php
require 'db.php';

$msg = '';

// Auto-generate student code: YY + 5-digit running (e.g. 6900001)
function nextStudentCode($conn): string {
    $yearPrefix = substr((string)((int)date('Y') + 543), -2); // last 2 digits of Thai year
    $like = $conn->real_escape_string($yearPrefix);
    $row = $conn->query("SELECT student_code FROM student WHERE student_code LIKE '{$like}%' ORDER BY student_code DESC LIMIT 1")->fetch_row();
    if ($row && preg_match('/^\d{7}$/', $row[0])) {
        $next = (int)$row[0] + 1;
    } else {
        $next = (int)($yearPrefix . '00001');
    }
    return str_pad($next, 7, '0', STR_PAD_LEFT);
}

// Delete
if (isset($_GET['delete'])) {
    $code = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM student WHERE student_code='$code'");
    header('Location: students.php?msg=deleted');
    exit;
}

// Save (add / edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['student_code','student_fname_th','student_sname_th','student_fname_eng','student_sname_eng','class_room','email'];
    $data = [];
    foreach ($fields as $f) $data[$f] = $conn->real_escape_string(trim($_POST[$f] ?? ''));

    if ($_POST['action'] === 'add') {
        $sql = "INSERT INTO student (student_code,student_fname_th,student_sname_th,student_fname_eng,student_sname_eng,class_room,email)
                VALUES ('{$data['student_code']}','{$data['student_fname_th']}','{$data['student_sname_th']}',
                        '{$data['student_fname_eng']}','{$data['student_sname_eng']}','{$data['class_room']}','{$data['email']}')";
        $conn->query($sql);
    } else {
        $old = $conn->real_escape_string($_POST['old_code']);
        $sql = "UPDATE student SET student_code='{$data['student_code']}',student_fname_th='{$data['student_fname_th']}',
                student_sname_th='{$data['student_sname_th']}',student_fname_eng='{$data['student_fname_eng']}',
                student_sname_eng='{$data['student_sname_eng']}',class_room='{$data['class_room']}',email='{$data['email']}'
                WHERE student_code='$old'";
        $conn->query($sql);
    }
    header('Location: students.php?msg=saved');
    exit;
}

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
    $code = $conn->real_escape_string($_GET['edit']);
    $edit = $conn->query("SELECT * FROM student WHERE student_code='$code'")->fetch_assoc();
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการนักศึกษา</title>
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
  <?php if ($msg === 'saved'): ?><div class="alert alert-success">บันทึกข้อมูลสำเร็จ</div><?php endif; ?>
  <?php if ($msg === 'deleted'): ?><div class="alert alert-warning">ลบข้อมูลสำเร็จ</div><?php endif; ?>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-bold"><?= $edit ? 'แก้ไขนักศึกษา' : 'เพิ่มนักศึกษา' ?></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="old_code" value="<?= htmlspecialchars($edit['student_code']) ?>"><?php endif; ?>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">รหัสนักศึกษา</label>
            <?php $autoCode = $edit ? $edit['student_code'] : nextStudentCode($conn); ?>
            <input name="student_code" class="form-control <?= $edit ? '' : 'bg-light' ?>"
                   <?= $edit ? '' : 'readonly' ?> required
                   value="<?= htmlspecialchars($autoCode) ?>">
            <?php if (!$edit): ?>
            <div class="form-text">สร้างอัตโนมัติตามปีการศึกษา</div>
            <?php endif; ?>
          </div>
          <div class="col-md-3">
            <label class="form-label">ชื่อ (ไทย)</label>
            <input name="student_fname_th" class="form-control" required value="<?= htmlspecialchars($edit['student_fname_th'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">สกุล (ไทย)</label>
            <input name="student_sname_th" class="form-control" required value="<?= htmlspecialchars($edit['student_sname_th'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">ชื่อ (อังกฤษ)</label>
            <input name="student_fname_eng" class="form-control" value="<?= htmlspecialchars($edit['student_fname_eng'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">สกุล (อังกฤษ)</label>
            <input name="student_sname_eng" class="form-control" value="<?= htmlspecialchars($edit['student_sname_eng'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">ห้องเรียน</label>
            <input name="class_room" class="form-control" value="<?= htmlspecialchars($edit['class_room'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">อีเมล</label>
            <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มนักศึกษา' ?></button>
            <?php if ($edit): ?><a href="students.php" class="btn btn-secondary">ยกเลิก</a><?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">รายชื่อนักศึกษาทั้งหมด</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>รหัส</th>
            <th>ชื่อ-สกุล (ไทย)</th>
            <th>ชื่อ-สกุล (Eng)</th>
            <th>ห้อง</th>
            <th>อีเมล</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rows = $conn->query('SELECT * FROM student ORDER BY student_code');
          while ($r = $rows->fetch_assoc()):
          ?>
          <tr>
            <td><?= htmlspecialchars($r['student_code']) ?></td>
            <td><?= htmlspecialchars($r['student_fname_th'] . ' ' . $r['student_sname_th']) ?></td>
            <td><?= htmlspecialchars($r['student_fname_eng'] . ' ' . $r['student_sname_eng']) ?></td>
            <td><?= htmlspecialchars($r['class_room']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td>
              <a href="students.php?edit=<?= urlencode($r['student_code']) ?>" class="btn btn-sm btn-outline-secondary">แก้ไข</a>
              <a href="students.php?delete=<?= urlencode($r['student_code']) ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('ลบนักศึกษานี้?')">ลบ</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
