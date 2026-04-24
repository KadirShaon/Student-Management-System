<?php
require_once 'config.php';
$title = 'Notice Board';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act     = $_POST['_act']   ?? '';
    $ptitle  = clean($conn, $_POST['title']   ?? '');
    $content = clean($conn, $_POST['content'] ?? '');
    $author  = clean($conn, $_POST['author']  ?? 'Admin');

    if ($act === 'add') {
        if ($ptitle && $content) {
            $conn->query("INSERT INTO notices (title, content, author) VALUES ('$ptitle','$content','$author')");
            flash('success', 'Notice posted.');
        } else {
            flash('error', 'Title and Content are required.');
        }
    }
    if ($act === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("UPDATE notices SET title='$ptitle', content='$content' WHERE id=$id");
        flash('success', 'Notice updated.');
    }
    go('notices.php');
}

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->query("DELETE FROM notices WHERE id=" . (int)$_GET['del']);
    flash('success', 'Notice deleted.');
    go('notices.php');
}

$notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC");
?>
<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-head">
        <div class="card-title"><i class="fas fa-bullhorn"></i> Notice Board</div>
        <button onclick="openModal('modalAdd')" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Post Notice</button>
    </div>
    <div class="card-body">
        <?php if ($notices->num_rows): while ($n = $notices->fetch_assoc()): ?>
        <div class="notice-item">
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start">
                <div style="flex:1">
                    <div class="notice-title-row">
                        <div class="notice-dot"></div>
                        <div class="notice-title"><?= htmlspecialchars($n['title']) ?></div>
                    </div>
                    <div class="notice-meta">
                        <i class="fas fa-user-pen"></i> <?= htmlspecialchars($n['author']) ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-clock"></i> <?= date('d F Y, h:i A', strtotime($n['created_at'])) ?>
                    </div>
                    <div class="notice-text"><?= nl2br(htmlspecialchars($n['content'])) ?></div>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0">
                    <button onclick='fillEdit(<?= json_encode(["id"=>(int)$n["id"],"title"=>$n["title"],"content"=>$n["content"]]) ?>)'
                            class="btn btn-warning btn-sm"><i class="fas fa-pencil"></i></button>
                    <a href="notices.php?del=<?= $n['id'] ?>"
                       onclick="return confirm('Delete this notice?')"
                       class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div class="empty"><i class="fas fa-bell-slash"></i><p>No notices posted yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="overlay" id="modalAdd">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-bullhorn" style="color:var(--col-primary)"></i> Post Notice</h3>
            <button class="modal-close" onclick="closeModal('modalAdd')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="add">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Notice title">
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="5" required placeholder="Write the notice here…"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:18px">
                    <label class="form-label">Posted By</label>
                    <input type="text" name="author" class="form-control" value="Admin">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post</button>
                    <button type="button" onclick="closeModal('modalAdd')" class="btn btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-head">
            <h3><i class="fas fa-pencil" style="color:var(--col-warning)"></i> Edit Notice</h3>
            <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="_act" value="edit">
                <input type="hidden" name="id" id="e_id">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" id="e_title" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:18px">
                    <label class="form-label">Content *</label>
                    <textarea name="content" id="e_content" class="form-control" rows="5" required></textarea>
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
function fillEdit(n) {
    document.getElementById('e_id').value      = n.id;
    document.getElementById('e_title').value   = n.title;
    document.getElementById('e_content').value = n.content;
    openModal('modalEdit');
}
</script>

<?php include 'includes/footer.php'; ?>
