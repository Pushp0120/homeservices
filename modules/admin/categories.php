<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/admin/dashboard.php');
$db = getDB();
$pageTitle = 'Manage Categories';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name  = sanitize($_POST['name'] ?? '');
        $desc  = sanitize($_POST['description'] ?? '');
        $icon  = sanitize($_POST['icon'] ?? 'bi-tools');
        $color = sanitize($_POST['color'] ?? '#0d6efd');
        if ($name) {
            $db->prepare("INSERT INTO categories (name,description,icon,color) VALUES (?,?,?,?)")->execute([$name,$desc,$icon,$color]);
            $success = 'Category added.';
        }
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $name  = sanitize($_POST['name'] ?? '');
        $desc  = sanitize($_POST['description'] ?? '');
        $icon  = sanitize($_POST['icon'] ?? 'bi-tools');
        $color = sanitize($_POST['color'] ?? '#0d6efd');
        $status= $_POST['status'] ?? 'active';
        $db->prepare("UPDATE categories SET name=?,description=?,icon=?,color=?,status=? WHERE id=?")->execute([$name,$desc,$icon,$color,$status,$id]);
        $success = 'Category updated.';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE categories SET status='inactive' WHERE id=?")->execute([$id]);
        $success = 'Category deactivated.';
    }
}

$categories = $db->query("SELECT c.*, COUNT(s.id) as service_count, COUNT(DISTINCT p.id) as provider_count
    FROM categories c
    LEFT JOIN services s ON s.category_id=c.id AND s.status='active'
    LEFT JOIN providers p ON p.category_id=c.id AND p.approval_status='approved'
    GROUP BY c.id ORDER BY c.name")->fetchAll();

$icons = ['bi-tools','bi-droplet-fill','bi-lightning-charge-fill','bi-stars','bi-hammer','bi-brush-fill','bi-flower1','bi-wind','bi-shield-lock-fill','bi-house','bi-truck','bi-camera-video'];
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Categories</div>
    </div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal"><i class="bi bi-plus-circle"></i> Add Category</button>
  </div>
  <div class="page-content">
    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>

    <div class="row g-3">
      <?php foreach ($categories as $c): ?>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100">
          <div class="card-body text-center">
            <div style="font-size:2.5rem;color:<?= $c['color'] ?>"><i class="bi <?= $c['icon'] ?>"></i></div>
            <h6 class="fw-700 mt-2 mb-1"><?= sanitize($c['name']) ?></h6>
            <p class="text-muted small mb-2"><?= sanitize(substr($c['description'] ?? '', 0, 60)) ?></p>
            <div class="d-flex justify-content-center gap-3 text-muted small mb-3">
              <span><i class="bi bi-tools"></i> <?= $c['service_count'] ?> services</span>
              <span><i class="bi bi-people"></i> <?= $c['provider_count'] ?> providers</span>
            </div>
            <?= statusBadge($c['status']) ?>
            <div class="d-flex gap-2 justify-content-center mt-2">
              <button class="btn btn-sm btn-outline-primary" onclick='editCat(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Deactivate category?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-toggle-off"></i></button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="col-sm-6"><label class="form-label">Icon (Bootstrap)</label>
          <select name="icon" class="form-select"><?php foreach ($icons as $ic): ?><option value="<?=$ic?>"><?=$ic?></option><?php endforeach; ?></select>
        </div>
        <div class="col-sm-6"><label class="form-label">Color</label><input type="color" name="color" class="form-control" value="#0d6efd"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Add</button></div>
    </form>
  </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Name *</label><input type="text" name="name" id="editName" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="editDesc" class="form-control" rows="2"></textarea></div>
        <div class="col-sm-6"><label class="form-label">Icon</label>
          <select name="icon" id="editIcon" class="form-select"><?php foreach ($icons as $ic): ?><option value="<?=$ic?>"><?=$ic?></option><?php endforeach; ?></select>
        </div>
        <div class="col-sm-6"><label class="form-label">Color</label><input type="color" name="color" id="editColor" class="form-control"></div>
        <div class="col-sm-6"><label class="form-label">Status</label>
          <select name="status" id="editStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function editCat(c) {
  document.getElementById('editId').value = c.id;
  document.getElementById('editName').value = c.name;
  document.getElementById('editDesc').value = c.description || '';
  document.getElementById('editIcon').value = c.icon || 'bi-tools';
  document.getElementById('editColor').value = c.color || '#0d6efd';
  document.getElementById('editStatus').value = c.status;
  new bootstrap.Modal(document.getElementById('editCatModal')).show();
}
</script>
