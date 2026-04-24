<?php
require_once 'config.php';
$title = 'Attendance';

/* Save attendance */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_att'])) {
    $date = clean($conn, $_POST['att_date'] ?? date('Y-m-d'));
    if (!empty($_POST['att'])) {
        foreach ($_POST['att'] as $sid => $status) {
            $sid    = (int)$sid;
            $status = clean($conn, $status);
            $conn->query(
                "INSERT INTO attendance (student_id, att_date, status)
                 VALUES ($sid, '$date', '$status')
                 ON DUPLICATE KEY UPDATE status='$status'"
            );
        }
    }
    flash('success', 'Attendance saved for ' . date('d F Y', strtotime($date)) . '.');
    go('attendance.php?date=' . urlencode($date) . '&cls=' . (int)($_POST['cls'] ?? 0));
}

/* Filters */
$selDate = clean($conn, $_GET['date'] ?? date('Y-m-d'));
$selCls  = (int)($_GET['cls'] ?? 0);

$where = $selCls ? "AND s.class_id = $selCls" : '';

$students = $conn->query(
    "SELECT s.id, s.full_name, s.student_code,
            COALESCE(CONCAT(c.name,' ',c.section),'—') AS class_label,
            COALESCE(a.status,'Present') AS att_status
     FROM students s
     LEFT JOIN classes c ON c.id = s.class_id
     LEFT JOIN attendance a ON a.student_id = s.id AND a.att_date = '$selDate'
     WHERE s.status='Active' $where
     ORDER BY s.full_name"
);

$classes = $conn->query("SELECT * FROM classes ORDER BY name, section");

/* Summary */
$summary = [];
$res = $conn->query("SELECT status, COUNT(*) AS n FROM attendance WHERE att_date='$selDate' GROUP BY status");
while ($r = $res->fetch_assoc()) $summary[$r['status']] = $r['n'];
?>
<?php include 'includes/header.php'; ?>

<!-- Filter row -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selDate) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Class</label>
                <select name="cls" class="form-control">
                    <option value="0">All Classes</option>
                    <?php while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $selCls === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name'].' '.$c['section']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="justify-content:flex-end">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary chips -->
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
    <span class="badge badge-success" style="padding:7px 14px;font-size:13px">✓ Present: <?= $summary['Present'] ?? 0 ?></span>
    <span class="badge badge-danger"  style="padding:7px 14px;font-size:13px">✗ Absent: <?= $summary['Absent']  ?? 0 ?></span>
    <span class="badge badge-warning" style="padding:7px 14px;font-size:13px">⏱ Late: <?= $summary['Late']    ?? 0 ?></span>
    <span class="badge badge-info"    style="padding:7px 14px;font-size:13px">ℹ Excused: <?= $summary['Excused'] ?? 0 ?></span>
</div>

<!-- Attendance form -->
<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-calendar-check"></i>
            Attendance — <?= date('d F Y', strtotime($selDate)) ?>
        </div>
        <?php if ($students->num_rows): ?>
        <button type="submit" form="attForm" class="btn btn-success btn-sm"><i class="fas fa-floppy-disk"></i> Save</button>
        <?php endif; ?>
    </div>

    <form id="attForm" method="POST">
        <input type="hidden" name="_att" value="1">
        <input type="hidden" name="att_date" value="<?= htmlspecialchars($selDate) ?>">
        <input type="hidden" name="cls" value="<?= $selCls ?>">

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Code</th>
                        <th>Class</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students->num_rows): $i=1; while ($s = $students->fetch_assoc()): $cur = $s['att_status']; ?>
                <tr>
                    <td style="color:var(--col-text3)"><?= $i++ ?></td>
                    <td>
                        <div class="name-cell">
                            <div class="av"><?= initials($s['full_name']) ?></div>
                            <div class="nm"><?= htmlspecialchars($s['full_name']) ?></div>
                        </div>
                    </td>
                    <td><span class="mono" style="color:var(--col-primary)"><?= htmlspecialchars($s['student_code']) ?></span></td>
                    <td><?= htmlspecialchars($s['class_label']) ?></td>
                    <td>
                        <div class="att-radio">
                            <?php foreach ([
                                'Present' => ['att-lbl-p','P'],
                                'Absent'  => ['att-lbl-a','A'],
                                'Late'    => ['att-lbl-l','L'],
                                'Excused' => ['att-lbl-e','E'],
                            ] as $val => [$cls2, $lbl]): ?>
                            <input type="radio"
                                   name="att[<?= $s['id'] ?>]"
                                   id="att_<?= $s['id'].'_'.$val ?>"
                                   value="<?= $val ?>"
                                   <?= $cur === $val ? 'checked' : '' ?>>
                            <label for="att_<?= $s['id'].'_'.$val ?>" class="<?= $cls2 ?>"><?= $lbl ?></label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5"><div class="empty"><i class="fas fa-users-slash"></i><p>No active students found.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($students->num_rows): ?>
        <div style="padding:14px 20px;border-top:1px solid var(--col-border)">
            <button type="submit" class="btn btn-success"><i class="fas fa-floppy-disk"></i> Save Attendance</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
