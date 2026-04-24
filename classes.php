<?php
require_once 'config.php';
$title = 'Classes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act  = $_POST['_act'] ?? '';
    $name = clean($conn, $_POST['name']    ?? '');
    $sec  = clean($conn, $_POST['section'] ?? '');

    if ($act === 'add') {
        if ($name && $sec) {
            $conn->query("INSERT INTO classes (name, section) VALUES ('$name','$sec')");
            flash('success', "Class \"$name $sec\" added.");
        } else {
            flash('error', 'Name and Section are required.');
        }
    }
    if ($act === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("UPDATE classes SET name='$name', section='$sec' WHERE id=$id");
        flash('success', 'Class updated.');
    }
    go('classes.php');
}

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->query("DELETE FROM classes WHERE id=" . (int)$_GET['del']);
    flash('success', 'Class deleted.');
    go('classes.php');
}

$classes = $conn->query(
    "SELECT c.*, COUNT(s.id) AS cnt
     FROM classes c
     LEFT JOIN students s ON s.class_id = c.id
     GROUP BY c.id
     ORDER BY c.name, c.section"
);
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-school"></i> Classes</div>
        <button onclick="openModal('modalAdd')" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Class</button>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead><tr><th>#</th><th>Class Name</th><th>Section</th><th>Students</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($classes->num_rows): $i=1; while ($c = $classes->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--col-text3)"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($c['section']) ?></span></td>
                    <td><span class="badge badge-success"><?= $c['cnt'] ?> student<?= $c['cnt'] != 1 ? 's' : '' ?></span></td>
                    <td>
                        <button onclick='fillEdit(<?= json_encode(["id"=>$c["id"],"name"=>$c["name"],"section"=>$c["section"]]) ?>)'
                                class="btn btn-warning btn-sm"><i class="fas fa-pencil"></i></button>
                        <a href="classes.php?del=<?= $c['id'] ?>"
                           onclick="return confirm('Delete this class? Students assigned to it will be unassigned.')"
                           class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5"><div class="empty"><i class="fas fa-school"></i><p>No classes yet.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add -->
<div class="overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-plus" style="color:var(--col-primary)"></i> Add Class</h3>
            <button class="modal-close" onclick="closeModal('modalAdd')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="add">
                <div class="form-row" style="margin-bottom:18px">
                    <div class="form-group">
                        <label class="form-label">Class Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Class Ten">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section *</label>
                        <input type="text" name="section" class="form-control" required placeholder="e.g. A">
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
            <h3><i class="fas fa-pencil" style="color:var(--col-warning)"></i> Edit Class</h3>
            <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="edit">
                <input type="hidden" name="id" id="e_id">
                <div class="form-row" style="margin-bottom:18px">
                    <div class="form-group">
                        <label class="form-label">Class Name *</label>
                        <input type="text" name="name" id="e_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Section *</label>
                        <input type="text" name="section" id="e_sec" class="form-control" required>
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
function fillEdit(c) {
    document.getElementById('e_id').value   = c.id;
    document.getElementById('e_name').value = c.name;
    document.getElementById('e_sec').value  = c.section;
    openModal('modalEdit');
}
</script>
<?php include 'includes/footer.php'; ?>
