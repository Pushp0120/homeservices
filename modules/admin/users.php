<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() !== 'admin') redirect(APP_URL . '/modules/admin/dashboard.php');
$db = getDB();
$pageTitle = 'Manage Users';

// Handle actions
if ($_GET['action'] ?? '' && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'suspend') $db->prepare("UPDATE users SET status='suspended' WHERE id=? AND role='customer'")->execute([$uid]);
    if ($act === 'activate') $db->prepare("UPDATE users SET status='active' WHERE id=? AND role='customer'")->execute([$uid]);
    if ($act === 'delete') {
        // Only soft-delete (suspend permanently)
        $db->prepare("UPDATE users SET status='suspended' WHERE id=? AND role='customer'")->execute([$uid]);
    }
    redirect(APP_URL . '/modules/admin/users.php?msg=done');
}

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $name  = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $pass  = hashPassword($_POST['password'] ?? 'password123');
    try {
        $db->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,'customer')")->execute([$name,$email,$phone,$pass]);
    } catch(Exception $e) {}
    redirect(APP_URL . '/modules/admin/users.php?msg=done');
}

$search = sanitize($_GET['q'] ?? '');
$where  = $search ? "AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')" : '';
$users  = $db->query("SELECT u.*,
    (SELECT COUNT(*) FROM bookings b WHERE b.customer_id=u.id) as booking_count,
    p.id as provider_id,
    p.approval_status,
    p.business_name
    FROM users u
    LEFT JOIN providers p ON p.user_id = u.id
    WHERE u.role='customer' $where ORDER BY u.created_at DESC")->fetchAll();
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="dashboard-wrapper">
<div class="overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
      <div class="topbar-title">Manage Users</div>
    </div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> Add User</button>
  </div>
  <div class="page-content">
    <?php if (isset($_GET['msg'])): ?><div class="alert alert-success py-2">Action completed.</div><?php endif; ?>
    <div class="card mb-3"><div class="card-body py-2">
      <form class="d-flex gap-2">
        <div class="search-bar flex-fill"><i class="bi bi-search"></i><input type="text" name="q" class="form-control" placeholder="Search users..." value="<?= $search ?>"></div>
        <button class="btn btn-outline-primary btn-sm">Search</button>
      </form>
    </div></div>

    <div class="card table-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="card-title">All Customers <span class="badge bg-primary"><?= count($users) ?></span></span>
      </div>
      <div class="table-responsive" data-searchable>
        <table class="table">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>Bookings</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="9" class="text-center py-4 text-muted">No users found.</td></tr>
          <?php else: foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td class="fw-600"><?= sanitize($u['name']) ?></td>
            <td><?= sanitize($u['email']) ?></td>
            <td><?= sanitize($u['phone'] ?? '—') ?></td>
            <td>
              <?php if ($u['provider_id']): ?>
                <?php if ($u['approval_status'] === 'approved'): ?>
                  <span class="badge bg-success"><i class="bi bi-person-badge"></i> Provider</span>
                <?php elseif ($u['approval_status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-hourglass"></i> Pending Provider</span>
                <?php else: ?>
                  <span class="badge bg-secondary"><i class="bi bi-person-badge"></i> Suspended Provider</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge bg-primary"><i class="bi bi-person"></i> Customer</span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-primary"><?= $u['booking_count'] ?></span></td>
            <td><?= statusBadge($u['status']) ?></td>
            <td class="text-muted small"><?= formatDate($u['created_at']) ?></td>
            <td>
              <?php if ($u['status']==='active'): ?>
              <a href="?action=suspend&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('Suspend this user?')">Suspend</a>
              <?php else: ?>
              <a href="?action=activate&id=<?= $u['id'] ?>" class="btn btn-sm btn-success">Activate</a>
              <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Customer</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
        <div class="col-sm-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
        <div class="col-sm-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Min 6 chars" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Create User</button>
      </div>
    </form>
  </div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
