<?php
require_once 'config.php';
$title = 'Subjects';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act     = $_POST['_act']    ?? '';
    $name    = clean($conn, $_POST['name']    ?? '');
    $code    = clean($conn, $_POST['code']    ?? '');
    $cls     = (int)($_POST['class_id']       ?? 0);
    $teacher = clean($conn, $_POST['teacher'] ?? '');

    if ($act === 'add') {
        if ($name && $code) {
            $clsVal = $cls ? $cls : 'NULL';
            $ok = $conn->query("INSERT INTO subjects (name, code, class_id, teacher)
                                VALUES ('$name','$code',$clsVal,'$teacher')");
            $ok ? flash('success', "Subject \"$name\" added.")
                : flash('error', strpos($conn->error,'Duplicate')!==false
                    ? 'Subject code already exists.' : $conn->error);
        } else {
            flash('error', 'Name and Code are required.');
        }
    }
    if ($act === 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $clsVal = $cls ? $cls : 'NULL';
        $conn->query("UPDATE subjects SET name='$name', code='$code',
                      class_id=$clsVal, teacher='$teacher' WHERE id=$id");
        flash('success', 'Subject updated.');
    }
    go('subjects.php');
}

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->query("DELETE FROM subjects WHERE id=" . (int)$_GET['del']);
    flash('success', 'Subject deleted.');
    go('subjects.php');
}

$subjects = $conn->query(
    "SELECT s.*, COALESCE(CONCAT(c.name,' ',c.section),'—') AS class_label
     FROM subjects s
     LEFT JOIN classes c ON c.id = s.class_id
     ORDER BY s.name"
);
$classes = $conn->query("SELECT * FROM classes ORDER BY name, section");
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-book-open"></i> Subjects</div>
        <button onclick="openModal('modalAdd')" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Subject</button>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>#</th><th>Subject Name</th><th>Code</th><th>Class</th><th>Teacher</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($subjects->num_rows): $i=1; while ($s = $subjects->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--col-text3)"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><span class="mono" style="color:var(--col-primary)"><?= htmlspecialchars($s['code']) ?></span></td>
                    <td><?= htmlspecialchars($s['class_label']) ?></td>
                    <td><?= $s['teacher'] ? htmlspecialchars($s['teacher']) : '<span style="color:var(--col-text3)">—</span>' ?></td>
                    <td>
                        <button onclick='fillEdit(<?= json_encode([
                            "id"=>(int)$s["id"],"name"=>$s["name"],"code"=>$s["code"],
                            "class_id"=>(int)($s["class_id"]??0),"teacher"=>$s["teacher"]??""
                        ]) ?>)' class="btn btn-warning btn-sm"><i class="fas fa-pencil"></i></button>
                        <a href="subjects.php?del=<?= $s['id'] ?>"
                           onclick="return confirm('Delete this subject?')"
                           class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6"><div class="empty"><i class="fas fa-book"></i><p>No subjects yet.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add -->
<div class="overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-book-open" style="color:var(--col-primary)"></i> Add Subject</h3>
            <button class="modal-close" onclick="closeModal('modalAdd')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="add">
                <div class="form-row" style="margin-bottom:18px">
                    <div class="form-group">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject Code *</label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. MATH-10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-control">
                            <option value="0">— Select Class —</option>
                            <?php while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'].' '.$c['section']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <input type="text" name="teacher" class="form-control" placeholder="Teacher name">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
                    <button type="button" onclick="closeModal('modalAdd')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit -->
<div class="overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-pencil" style="color:var(--col-warning)"></i> Edit Subject</h3>
            <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="edit">
                <input type="hidden" name="id" id="e_id">
                <div class="form-row" style="margin-bottom:18px">
                    <div class="form-group">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="name" id="e_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject Code *</label>
                        <input type="text" name="code" id="e_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" id="e_class" class="form-control">
                            <option value="0">— Select Class —</option>
                            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'].' '.$c['section']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teacher</label>
                        <input type="text" name="teacher" id="e_teacher" class="form-control">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Update</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillEdit(s) {
    document.getElementById('e_id').value      = s.id;
    document.getElementById('e_name').value    = s.name;
    document.getElementById('e_code').value    = s.code;
    document.getElementById('e_class').value   = s.class_id;
    document.getElementById('e_teacher').value = s.teacher;
    openModal('modalEdit');
}
</script>
<?php include 'includes/footer.php'; ?>
