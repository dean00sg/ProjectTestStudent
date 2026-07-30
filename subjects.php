<?php
require 'db.php';

if (isset($_GET['delete'])) {
    $code = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM subject WHERE subject_code='$code'");
    header('Location: subjects.php?msg=deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code    = $conn->real_escape_string(trim($_POST['subject_code']));
    $name    = $conn->real_escape_string(trim($_POST['subject_name']));
    $credits = (int)$_POST['credits'];

    if ($_POST['action'] === 'add') {
        $conn->query("INSERT INTO subject (subject_code,subject_name,credits) VALUES ('$code','$name',$credits)");
    } else {
        $old = $conn->real_escape_string($_POST['old_code']);
        $conn->query("UPDATE subject SET subject_code='$code',subject_name='$name',credits=$credits WHERE subject_code='$old'");
    }
    header('Location: subjects.php?msg=saved');
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $code = $conn->real_escape_string($_GET['edit']);
    $edit = $conn->query("SELECT * FROM subject WHERE subject_code='$code'")->fetch_assoc();
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>จัดการวิชา</title>
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
    <div class="card-header bg-white fw-bold"><?= $edit ? 'แก้ไขวิชา' : 'เพิ่มวิชา' ?></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
        <?php if ($edit): ?><input type="hidden" name="old_code" value="<?= htmlspecialchars($edit['subject_code']) ?>"><?php endif; ?>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">รหัสวิชา</label>
            <input name="subject_code" class="form-control" required value="<?= htmlspecialchars($edit['subject_code'] ?? '') ?>">
          </div>
          <div class="col-md-5">
            <label class="form-label">ชื่อวิชา</label>
            <input name="subject_name" class="form-control" required value="<?= htmlspecialchars($edit['subject_name'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">หน่วยกิต</label>
            <input name="credits" type="number" min="1" max="9" class="form-control" required value="<?= htmlspecialchars($edit['credits'] ?? '3') ?>">
          </div>
          <div class="col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มวิชา' ?></button>
            <?php if ($edit): ?><a href="subjects.php" class="btn btn-secondary">ยกเลิก</a><?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white fw-bold">รายวิชาทั้งหมด</div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>รหัสวิชา</th><th>ชื่อวิชา</th><th>หน่วยกิต</th><th>จัดการ</th></tr>
        </thead>
        <tbody>
          <?php
          $rows = $conn->query('SELECT * FROM subject ORDER BY subject_code');
          while ($r = $rows->fetch_assoc()):
          ?>
          <tr>
            <td><?= htmlspecialchars($r['subject_code']) ?></td>
            <td><?= htmlspecialchars($r['subject_name']) ?></td>
            <td class="text-center"><?= $r['credits'] ?></td>
            <td>
              <a href="subjects.php?edit=<?= urlencode($r['subject_code']) ?>" class="btn btn-sm btn-outline-secondary">แก้ไข</a>
              <a href="subjects.php?delete=<?= urlencode($r['subject_code']) ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('ลบวิชานี้?')">ลบ</a>
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
