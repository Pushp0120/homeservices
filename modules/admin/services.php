<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/admin/dashboard.php');
$db = getDB();
$pageTitle = 'Manage Services';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $catId = (int)$_POST['category_id'];
        $name  = sanitize($_POST['name'] ?? '');
        $desc  = sanitize($_POST['description'] ?? '');
        $unit  = sanitize($_POST['unit'] ?? 'per visit');
        $price = (float)$_POST['price'];
        $db->prepare("INSERT INTO services (category_id,name,description,unit,price) VALUES (?,?,?,?,?)")->execute([$catId,$name,$desc,$unit,$price]);
        $success = 'Service added.';
    } elseif ($action === 'edit') {
        $id    = (int)$_POST['id'];
        $name  = sanitize($_POST['name'] ?? '');
        $desc  = sanitize($_POST['description'] ?? '');
        $unit  = sanitize($_POST['unit'] ?? 'per visit');
        $price = (float)$_POST['price'];
        $stat  = $_POST['status'] ?? 'active';
        $db->prepare("UPDATE services SET name=?,description=?,unit=?,price=?,status=? WHERE id=?")->execute([$name,$desc,$unit,$price,$stat,$id]);
        $success = 'Service updated.';
    } elseif ($action === 'delete') {
        $db->prepare("UPDATE services SET status='inactive' WHERE id=?")->execute([(int)$_POST['id']]);
        $success = 'Service deactivated.';
    }
}

$categories = $db->query("SELECT * FROM categories WHERE status='active' ORDER BY name")->fetchAll();
$filterCat  = (int)($_GET['cat'] ?? 0);
$where      = $filterCat ? "WHERE s.category_id=$filterCat" : '';
$services   = $db->query("SELECT s.*, c.name as cat_name FROM services s JOIN categories c ON s.category_id=c.id $where ORDER BY c.name, s.name")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Services & Pricing</div>
    </div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addSvcModal"><i class="bi bi-plus-circle"></i> Add Service</button>
  </div>
  <div class="page-content">
    <?php if ($success): ?><div class="alert alert-success py-2"><?= $success ?></div><?php endif; ?>

    <!-- Filter -->
    <div class="card mb-3"><div class="card-body py-2">
      <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="text-muted small">Filter:</span>
        <a href="?" class="btn btn-sm <?= !$filterCat ? 'btn-danger' : 'btn-outline-secondary' ?>">All</a>
        <?php foreach ($categories as $c): ?>
        <a href="?cat=<?= $c['id'] ?>" class="btn btn-sm <?= $filterCat==$c['id'] ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= sanitize($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div></div>

    <div class="card table-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">All Services <span class="badge bg-danger"><?= count($services) ?></span></span>
        <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Search..." style="width:200px;padding-left:2.2rem"></div>
      </div>
      <div class="table-responsive" data-searchable>
        <table class="table">
          <thead><tr><th>#</th><th>Service Name</th><th>Category</th><th>Unit</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if (empty($services)): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No services.</td></tr>
          <?php else: $srNo = 1; foreach ($services as $s): ?>
          <tr>
            <td><?= $srNo++ ?></td>
            <td class="fw-600"><?= sanitize($s['name']) ?></td>
            <td><?= sanitize($s['cat_name']) ?></td>
            <td class="text-muted"><?= sanitize($s['unit']) ?></td>
            <td class="fw-700 text-primary"><?= formatCurrency($s['price']) ?></td>
            <td><?= statusBadge($s['status']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" onclick='editSvc(<?= json_encode($s) ?>)'><i class="bi bi-pencil"></i></button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Deactivate?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addSvcModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Service</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Category *</label>
          <select name="category_id" class="form-select" required>
            <?php foreach ($categories as $c): ?><option value="<?=$c['id']?>"><?= sanitize($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-12"><label class="form-label">Service Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="col-sm-6"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" value="per visit"></div>
        <div class="col-sm-6"><label class="form-label">Price (₹) *</label><input type="number" name="price" class="form-control" min="0" step="0.01" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Add Service</button></div>
    </form>
  </div></div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editSvcModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Edit Service</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="esId">
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Service Name *</label><input type="text" name="name" id="esName" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="esDesc" class="form-control" rows="2"></textarea></div>
        <div class="col-sm-6"><label class="form-label">Unit</label><input type="text" name="unit" id="esUnit" class="form-control"></div>
        <div class="col-sm-6"><label class="form-label">Price (₹) *</label><input type="number" name="price" id="esPrice" class="form-control" min="0" step="0.01" required></div>
        <div class="col-sm-6"><label class="form-label">Status</label>
          <select name="status" id="esStatus" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function editSvc(s) {
  document.getElementById('esId').value = s.id;
  document.getElementById('esName').value = s.name;
  document.getElementById('esDesc').value = s.description || '';
  document.getElementById('esUnit').value = s.unit;
  document.getElementById('esPrice').value = s.price;
  document.getElementById('esStatus').value = s.status;
  new bootstrap.Modal(document.getElementById('editSvcModal')).show();
}
</script>