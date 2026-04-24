<?php
require_once 'config.php';
$title = 'Students';

/* ============================================================
   CREATE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_act'] ?? '') === 'add') {
    $code   = clean($conn, $_POST['student_code'] ?? '');
    $name   = clean($conn, $_POST['full_name']     ?? '');
    $email  = clean($conn, $_POST['email']         ?? '');
    $phone  = clean($conn, $_POST['phone']         ?? '');
    $gender = clean($conn, $_POST['gender']        ?? '');
    $dob    = clean($conn, $_POST['dob']           ?? '');
    $cls    = (int)($_POST['class_id'] ?? 0);
    $addr   = clean($conn, $_POST['address']       ?? '');
    $status = clean($conn, $_POST['status']        ?? 'Active');

    if ($code === '' || $name === '' || $email === '' || $gender === '') {
        flash('error', 'Please fill all required fields.');
    } else {
        $ok = $conn->query(
            "INSERT INTO students
                (student_code, full_name, email, phone, gender, dob, class_id, address, status)
             VALUES
                ('$code','$name','$email','$phone','$gender',
                 " . ($dob ? "'$dob'" : "NULL") . ",
                 " . ($cls  ? $cls    : "NULL") . ",
                 '$addr','$status')"
        );
        if ($ok) {
            flash('success', "Student \"$name\" added successfully.");
        } else {
            // Duplicate key check
            if (strpos($conn->error, 'Duplicate') !== false) {
                flash('error', 'Student Code or Email already exists.');
            } else {
                flash('error', 'Database error: ' . $conn->error);
            }
        }
    }
    go('students.php');
}

/* ============================================================
   UPDATE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_act'] ?? '') === 'edit') {
    $id     = (int)($_POST['id'] ?? 0);
    $name   = clean($conn, $_POST['full_name'] ?? '');
    $email  = clean($conn, $_POST['email']     ?? '');
    $phone  = clean($conn, $_POST['phone']     ?? '');
    $gender = clean($conn, $_POST['gender']    ?? '');
    $cls    = (int)($_POST['class_id'] ?? 0);
    $addr   = clean($conn, $_POST['address']   ?? '');
    $status = clean($conn, $_POST['status']    ?? 'Active');

    $conn->query(
        "UPDATE students SET
            full_name='$name', email='$email', phone='$phone',
            gender='$gender',
            class_id=" . ($cls ? $cls : "NULL") . ",
            address='$addr', status='$status'
         WHERE id=$id"
    );
    flash('success', 'Student updated successfully.');
    go('students.php');
}

/* ============================================================
   DELETE
   ============================================================ */
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM students WHERE id=$id");
    flash('success', 'Student deleted.');
    go('students.php');
}

/* ============================================================
   READ  (with optional search)
   ============================================================ */
$q = clean($conn, $_GET['q'] ?? '');
$where = $q
    ? "WHERE s.full_name LIKE '%$q%'
          OR s.student_code LIKE '%$q%'
          OR s.email LIKE '%$q%'"
    : '';

$students = $conn->query(
    "SELECT s.*,
            COALESCE(CONCAT(c.name,' ',c.section),'—') AS class_label
     FROM students s
     LEFT JOIN classes c ON c.id = s.class_id
     $where
     ORDER BY s.created_at DESC"
);

$classes = $conn->query("SELECT * FROM classes ORDER BY name, section");
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-user-graduate"></i> All Students</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <!-- Live search -->
            <form method="GET" style="display:flex;gap:8px">
                <div class="search-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="q" placeholder="Search name / ID / email…"
                           value="<?= htmlspecialchars($q) ?>" id="liveSearch">
                </div>
                <?php if ($q): ?>
                    <a href="students.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </form>
            <button onclick="openModal('modalAdd')" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Student
            </button>
        </div>
    </div>

    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Code</th>
                    <th>Gender</th>
                    <th>Class</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $badgeMap = ['Active'=>'success','Inactive'=>'neutral','Suspended'=>'danger'];
            if ($students->num_rows):
                $i = 1;
                while ($s = $students->fetch_assoc()):
            ?>
                <tr>
                    <td style="color:var(--col-text3)"><?= $i++ ?></td>
                    <td>
                        <div class="name-cell">
                            <div class="av"><?= initials($s['full_name']) ?></div>
                            <div>
                                <div class="nm"><?= htmlspecialchars($s['full_name']) ?></div>
                                <div class="sub"><?= htmlspecialchars($s['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="mono" style="color:var(--col-primary)"><?= htmlspecialchars($s['student_code']) ?></span></td>
                    <td><?= $s['gender'] ?></td>
                    <td><?= htmlspecialchars($s['class_label']) ?></td>
                    <td><?= $s['phone'] ? htmlspecialchars($s['phone']) : '<span style="color:var(--col-text3)">—</span>' ?></td>
                    <td><span class="badge badge-<?= $badgeMap[$s['status']] ?? 'neutral' ?>"><?= $s['status'] ?></span></td>
                    <td>
                        <button
                            onclick='fillEdit(<?= json_encode([
                                "id"         => $s["id"],
                                "full_name"  => $s["full_name"],
                                "email"      => $s["email"],
                                "phone"      => $s["phone"] ?? "",
                                "gender"     => $s["gender"],
                                "class_id"   => $s["class_id"] ?? 0,
                                "address"    => $s["address"] ?? "",
                                "status"     => $s["status"],
                            ]) ?>)'
                            class="btn btn-warning btn-sm">
                            <i class="fas fa-pencil"></i>
                        </button>
                        <a href="students.php?del=<?= $s['id'] ?>"
                           onclick="return confirm('Delete this student permanently?')"
                           class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="8">
                        <div class="empty">
                            <i class="fas fa-user-slash"></i>
                            <p><?= $q ? "No results for \"" . htmlspecialchars($q) . "\"" : "No students yet. Click Add Student." ?></p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== ADD MODAL ===== -->
<div class="overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-user-plus" style="color:var(--col-primary)"></i> Add New Student</h3>
            <button class="modal-close" onclick="closeModal('modalAdd')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="_act" value="add">
                <div class="form-row" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Student Code *</label>
                        <input type="text" name="student_code" id="auto_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="">— Select —</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-control">
                            <option value="0">— Select Class —</option>
                            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ' ' . $c['section']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:18px">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Student</button>
                    <button type="button" onclick="closeModal('modalAdd')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT MODAL ===== -->
<div class="overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-user-pen" style="color:var(--col-warning)"></i> Edit Student</h3>
            <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="_act" value="edit">
                <input type="hidden" name="id" id="e_id">
                <div class="form-row" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" id="e_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="e_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="e_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="gender" id="e_gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" id="e_class" class="form-control">
                            <option value="0">— Select Class —</option>
                            <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'] . ' ' . $c['section']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="e_status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:18px">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="e_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Update Student</button>
                    <button type="button" onclick="closeModal('modalEdit')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillEdit(s) {
    document.getElementById('e_id').value      = s.id;
    document.getElementById('e_name').value    = s.full_name;
    document.getElementById('e_email').value   = s.email;
    document.getElementById('e_phone').value   = s.phone;
    document.getElementById('e_gender').value  = s.gender;
    document.getElementById('e_class').value   = s.class_id || 0;
    document.getElementById('e_status').value  = s.status;
    document.getElementById('e_address').value = s.address;
    openModal('modalEdit');
}
</script>

<?php include 'includes/footer.php'; ?>
