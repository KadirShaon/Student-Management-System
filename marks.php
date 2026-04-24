<?php
require_once 'config.php';
$title = 'Marks & Results';

/* ---- CREATE ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_act'] ?? '') === 'add') {
    $sid   = (int)($_POST['student_id']     ?? 0);
    $subid = (int)($_POST['subject_id']     ?? 0);
    $etype = clean($conn, $_POST['exam_type']      ?? '');
    $mo    = (float)($_POST['marks_obtained'] ?? 0);
    $tm    = (float)($_POST['total_marks']    ?? 100);
    $edate = clean($conn, $_POST['exam_date']      ?? '');

    if ($sid && $subid && $etype && $tm > 0 && $mo >= 0 && $mo <= $tm) {
        $edateVal = $edate ? "'$edate'" : 'NULL';
        $conn->query("INSERT INTO marks (student_id, subject_id, exam_type, marks_obtained, total_marks, exam_date)
                      VALUES ($sid,$subid,'$etype',$mo,$tm,$edateVal)");
        flash('success', 'Marks record added.');
    } else {
        flash('error', 'Please fill all fields correctly. Marks cannot exceed total marks.');
    }
    go('marks.php');
}

/* ---- DELETE ---- */
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->query("DELETE FROM marks WHERE id=" . (int)$_GET['del']);
    flash('success', 'Record deleted.');
    go('marks.php');
}

/* ---- READ ---- */
$marks = $conn->query(
    "SELECT m.*,
            s.full_name AS student_name, s.student_code,
            sub.name AS subject_name, sub.code AS subject_code,
            ROUND((m.marks_obtained / m.total_marks) * 100, 1) AS pct
     FROM marks m
     JOIN students s   ON s.id   = m.student_id
     JOIN subjects sub ON sub.id = m.subject_id
     ORDER BY m.created_at DESC"
);

$students = $conn->query("SELECT id, student_code, full_name FROM students WHERE status='Active' ORDER BY full_name");
$subjects = $conn->query("SELECT id, code, name FROM subjects ORDER BY name");
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Marks &amp; Results</div>
        <button onclick="openModal('modalAdd')" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Marks</button>
    </div>
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Student</th><th>Subject</th><th>Exam</th><th>Marks</th><th>Percentage</th><th>Grade</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if ($marks->num_rows): $i=1; while ($m = $marks->fetch_assoc()):
                [$grade, $gc] = gradeInfo((float)$m['pct']);
                $fillColor = $m['pct'] >= 60 ? 'var(--col-success)' : ($m['pct'] >= 40 ? 'var(--col-warning)' : 'var(--col-danger)');
            ?>
                <tr>
                    <td style="color:var(--col-text3)"><?= $i++ ?></td>
                    <td>
                        <div class="name-cell">
                            <div class="av"><?= initials($m['student_name']) ?></div>
                            <div>
                                <div class="nm"><?= htmlspecialchars($m['student_name']) ?></div>
                                <div class="sub mono"><?= htmlspecialchars($m['student_code']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?= htmlspecialchars($m['subject_name']) ?>
                        <span class="mono" style="color:var(--col-text3);font-size:11px"> (<?= htmlspecialchars($m['subject_code']) ?>)</span>
                    </td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($m['exam_type']) ?></span></td>
                    <td class="mono"><?= $m['marks_obtained'] ?> / <?= $m['total_marks'] ?></td>
                    <td>
                        <div class="pct-cell">
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= min(100, $m['pct']) ?>%;background:<?= $fillColor ?>"></div>
                            </div>
                            <span style="font-size:13px;font-weight:600"><?= $m['pct'] ?>%</span>
                        </div>
                    </td>
                    <td><span class="badge badge-<?= $gc ?>"><?= $grade ?></span></td>
                    <td style="color:var(--col-text3);font-size:12px">
                        <?= $m['exam_date'] ? date('d M Y', strtotime($m['exam_date'])) : '—' ?>
                    </td>
                    <td>
                        <a href="marks.php?del=<?= $m['id'] ?>"
                           onclick="return confirm('Delete this record?')"
                           class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9"><div class="empty"><i class="fas fa-chart-bar"></i><p>No marks records yet.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-plus" style="color:var(--col-primary)"></i> Add Marks</h3>
            <button class="modal-close" onclick="closeModal('modalAdd')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="add">
                <div class="form-row" style="margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Student *</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">— Select Student —</option>
                            <?php while ($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['student_code']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">— Select Subject —</option>
                            <?php while ($sub = $subjects->fetch_assoc()): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Type *</label>
                        <select name="exam_type" class="form-control" required>
                            <option value="Class Test">Class Test</option>
                            <option value="Mid Term">Mid Term</option>
                            <option value="Final Exam">Final Exam</option>
                            <option value="Assignment">Assignment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Marks Obtained *</label>
                        <input type="number" name="marks_obtained" id="marks_obtained"
                               class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Marks *</label>
                        <input type="number" name="total_marks" id="total_marks"
                               class="form-control" step="0.01" min="1" value="100" required>
                    </div>
                </div>
                <div style="margin-bottom:18px">
                    <span style="font-size:13px;color:var(--col-text2)">Percentage: </span>
                    <strong id="pct_preview" style="font-size:20px;font-weight:800">—</strong>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Marks</button>
                    <button type="button" onclick="closeModal('modalAdd')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
