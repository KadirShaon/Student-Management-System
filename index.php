<?php
require_once 'config.php';
$title = 'Dashboard';

// ---- Quick stats ----
$totalStudents = (int)$conn->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetch_row()[0];
$totalClasses  = (int)$conn->query("SELECT COUNT(*) FROM classes")->fetch_row()[0];
$totalSubjects = (int)$conn->query("SELECT COUNT(*) FROM subjects")->fetch_row()[0];
$todayPresent  = (int)$conn->query("SELECT COUNT(*) FROM attendance WHERE att_date=CURDATE() AND status='Present'")->fetch_row()[0];

// ---- Recent 5 students ----
$recent = $conn->query("
    SELECT s.id, s.student_code, s.full_name, s.status,
           COALESCE(CONCAT(c.name,' ',c.section),'—') AS class_label
    FROM students s
    LEFT JOIN classes c ON c.id = s.class_id
    ORDER BY s.created_at DESC
    LIMIT 5
");

// ---- Latest 3 notices ----
$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 3");
?>
<?php include 'includes/header.php'; ?>

<!-- Stat cards -->
<div class="stats-row">
    <div class="stat-card">
        <div class="s-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div><div class="s-num"><?= $totalStudents ?></div><div class="s-lbl">Active Students</div></div>
    </div>
    <div class="stat-card">
        <div class="s-icon green"><i class="fas fa-school"></i></div>
        <div><div class="s-num"><?= $totalClasses ?></div><div class="s-lbl">Total Classes</div></div>
    </div>
    <div class="stat-card">
        <div class="s-icon amber"><i class="fas fa-book-open"></i></div>
        <div><div class="s-num"><?= $totalSubjects ?></div><div class="s-lbl">Total Subjects</div></div>
    </div>
    <div class="stat-card">
        <div class="s-icon cyan"><i class="fas fa-calendar-check"></i></div>
        <div><div class="s-num"><?= $todayPresent ?></div><div class="s-lbl">Present Today</div></div>
    </div>
</div>

<div class="two-col">

    <!-- Recent Students -->
    <div class="card">
        <div class="card-head">
            <div class="card-title"><i class="fas fa-user-graduate"></i> Recent Students</div>
            <a href="students.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr><th>Student</th><th>Class</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($recent->num_rows): while ($r = $recent->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="name-cell">
                                <div class="av"><?= initials($r['full_name']) ?></div>
                                <div>
                                    <div class="nm"><?= htmlspecialchars($r['full_name']) ?></div>
                                    <div class="sub mono"><?= htmlspecialchars($r['student_code']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['class_label']) ?></td>
                        <td>
                            <?php
                            $bc = ['Active'=>'success','Inactive'=>'neutral','Suspended'=>'danger'];
                            $cls = $bc[$r['status']] ?? 'neutral';
                            ?>
                            <span class="badge badge-<?= $cls ?>"><?= $r['status'] ?></span>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3"><div class="empty"><i class="fas fa-user-slash"></i><p>No students yet.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Notices -->
    <div class="card">
        <div class="card-head">
            <div class="card-title"><i class="fas fa-bullhorn"></i> Notice Board</div>
            <a href="notices.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="card-body">
            <?php if ($notices->num_rows): while ($n = $notices->fetch_assoc()): ?>
            <div class="notice-item">
                <div class="notice-title-row">
                    <div class="notice-dot"></div>
                    <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                </div>
                <div class="notice-meta">
                    <i class="fas fa-user-pen"></i> <?= htmlspecialchars($n['author']) ?>
                    &nbsp;·&nbsp;
                    <i class="fas fa-clock"></i> <?= date('d M Y', strtotime($n['created_at'])) ?>
                </div>
                <div class="notice-text"><?= htmlspecialchars(mb_substr($n['content'], 0, 130)) ?>…</div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty"><i class="fas fa-bell-slash"></i><p>No notices posted.</p></div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
