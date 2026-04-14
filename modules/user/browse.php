<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
if (currentRole() === 'admin')    redirect(APP_URL . '/modules/admin/dashboard.php');
if (currentRole() === 'provider') redirect(APP_URL . '/modules/provider/dashboard.php');

$db = getDB();
$pageTitle = 'Browse Services';
$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = sanitize($_GET['q'] ?? '');

// Pagination
$perPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Only show categories that have at least 1 approved provider
$categories = $db->query("
  SELECT c.*, COUNT(p.id) as provider_count
  FROM categories c
  JOIN providers p ON p.category_id = c.id AND p.approval_status = 'approved'
  WHERE c.status = 'active'
  GROUP BY c.id
  ORDER BY provider_count DESC, c.name
")->fetchAll();

$whereParts = ["p.approval_status='approved'"];
if ($selectedCat) $whereParts[] = "p.category_id=$selectedCat";
if ($search)      $whereParts[] = "(p.business_name LIKE '%" . addslashes($search) . "%' OR p.bio LIKE '%" . addslashes($search) . "%' OR c.name LIKE '%" . addslashes($search) . "%')";
$whereClause = 'WHERE ' . implode(' AND ', $whereParts);

// Sort
$sort = $_GET['sort'] ?? 'rating';
$orderBy = match($sort) {
    'price_asc'  => 'p.base_price ASC',
    'price_desc' => 'p.base_price DESC',
    'jobs'       => 'completed_jobs DESC',
    default      => 'avg_rating DESC, completed_jobs DESC',
};

// Count total
$totalProviders = $db->query("
  SELECT COUNT(DISTINCT p.id)
  FROM providers p
  JOIN users u ON p.user_id=u.id
  JOIN categories c ON p.category_id=c.id
  $whereClause
")->fetchColumn();
$totalPages = ceil($totalProviders / $perPage);

$providers = $db->query("
  SELECT p.*, u.name as provider_name, u.email, c.name as category_name, c.color, c.icon,
    ROUND(COALESCE(AVG(r.rating),0),1) as avg_rating,
    COUNT(DISTINCT r.id) as review_count,
    SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) as completed_jobs
  FROM providers p
  JOIN users u ON p.user_id=u.id
  JOIN categories c ON p.category_id=c.id
  LEFT JOIN reviews r ON r.provider_id=p.id
  LEFT JOIN bookings b ON b.provider_id=p.id
  $whereClause
  GROUP BY p.id
  ORDER BY $orderBy
  LIMIT $perPage OFFSET $offset
")->fetchAll();

function starHtml($rating) {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);
    $html  = str_repeat('<i class="bi bi-star-fill text-warning"></i>', $full);
    if ($half) $html .= '<i class="bi bi-star-half text-warning"></i>';
    $html .= str_repeat('<i class="bi bi-star text-secondary opacity-25"></i>', $empty);
    return $html;
}

// Build base URL for pagination links
function browseUrl($params = []) {
    $defaults = ['cat' => $_GET['cat'] ?? 0, 'q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? 'rating'];
    $merged   = array_merge($defaults, $params);
    $query    = http_build_query(array_filter($merged, fn($v) => $v !== '' && $v !== '0' && $v !== 0));
    return '?' . $query;
}
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>
<div class="user-main-content">
  <div class="page-content">

    <!-- Page Hero -->
    <div class="page-hero mb-4" style="background:linear-gradient(135deg,#0f172a,#2563eb)">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="mb-1 fw-700">🔍 Find a Service Provider</h4>
          <p class="mb-0 opacity-75">Browse <?= $totalProviders ?> verified professionals ready to help you.</p>
        </div>
        <!-- Search bar in hero -->
        <form method="GET" class="d-flex gap-2" style="min-width:280px">
          <?php if ($selectedCat): ?><input type="hidden" name="cat" value="<?= $selectedCat ?>"><?php endif; ?>
          <input type="hidden" name="sort" value="<?= sanitize($sort) ?>">
          <div class="input-group">
            <span class="input-group-text bg-white border-0"><i class="bi bi-search text-primary"></i></span>
            <input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Search providers, services..." value="<?= $search ?>" style="min-width:200px">
            <button type="submit" class="btn btn-light fw-600 px-3">Search</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Search result notice -->
    <?php if ($search): ?>
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-search"></i>
      Showing results for "<strong><?= $search ?></strong>" — <?= $totalProviders ?> provider(s) found.
      <a href="?cat=<?= $selectedCat ?>" class="ms-auto btn btn-sm btn-outline-secondary">Clear</a>
    </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- LEFT: Filters -->
      <div class="col-lg-3">
        <div class="card sticky-top" style="top:80px">
          <div class="card-header"><span class="card-title"><i class="bi bi-funnel me-1"></i>Filter by Category</span></div>
          <div class="card-body p-2">
            <a href="?q=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="cat-filter-btn d-flex mb-1 <?= !$selectedCat ? 'active' : '' ?>" style="border-radius:10px;width:100%">
              <i class="bi bi-grid-3x3-gap me-2"></i> All Categories
              <small class="ms-auto"><?= array_sum(array_column($categories,'provider_count')) ?></small>
            </a>
            <?php foreach ($categories as $c): ?>
            <a href="?cat=<?= $c['id'] ?>&q=<?= urlencode($search) ?>&sort=<?= $sort ?>" class="cat-filter-btn d-flex mb-1 <?= $selectedCat==$c['id'] ? 'active' : '' ?>" style="--cat-color:<?= $c['color'] ?>;border-radius:10px;width:100%">
              <i class="bi <?= $c['icon'] ?> me-2"></i> <?= sanitize($c['name']) ?>
              <small class="ms-auto"><?= $c['provider_count'] ?></small>
            </a>
            <?php endforeach; ?>
          </div>

          <!-- Sort -->
          <div class="card-header border-top"><span class="card-title"><i class="bi bi-sort-down me-1"></i>Sort By</span></div>
          <div class="card-body p-2">
            <?php $sorts = ['rating'=>'⭐ Top Rated','price_asc'=>'💰 Price: Low to High','price_desc'=>'💸 Price: High to Low','jobs'=>'🏆 Most Jobs Done']; ?>
            <?php foreach ($sorts as $sv => $sl): ?>
            <a href="?cat=<?= $selectedCat ?>&q=<?= urlencode($search) ?>&sort=<?= $sv ?>" class="cat-filter-btn d-flex mb-1 <?= $sort===$sv?'active':'' ?>" style="border-radius:10px;width:100%;font-size:.82rem">
              <?= $sl ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- RIGHT: Provider cards -->
      <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="section-title mb-0">
            <?= $search ? 'Search Results' : ($selectedCat ? sanitize($categories[array_search($selectedCat,array_column($categories,'id'))]['name'] ?? 'Providers') : 'All Providers') ?>
            <span class="badge bg-primary ms-1"><?= $totalProviders ?></span>
          </h5>
          <span class="text-muted small">Page <?= $page ?> of <?= max(1,$totalPages) ?></span>
        </div>

        <?php if (empty($providers)): ?>
        <div class="empty-state card py-5">
          <i class="bi bi-people" style="font-size:3rem;opacity:.2"></i>
          <p class="mt-2">No providers found<?= $search ? " for \"$search\"" : '' ?>.</p>
          <a href="browse.php" class="btn btn-outline-primary btn-sm mt-2">View All Providers</a>
        </div>
        <?php else: ?>
        <div class="row g-3">
          <?php foreach ($providers as $p): ?>
          <div class="col-sm-6 col-xl-4">
            <div class="provider-card h-100" style="border-radius:16px">
              <!-- Colored top accent bar -->
              <div style="height:5px;background:<?= $p['color'] ?? '#2563eb' ?>;border-radius:16px 16px 0 0"></div>
              <div class="card-body p-3">
                <div class="d-flex align-items-start gap-3 mb-3">
                <div class="provider-avatar flex-shrink-0" style="background:<?= $p['color'] ?? 'linear-gradient(135deg,#2563eb,#7c3aed)' ?>;overflow:hidden">
                    <?php if (!empty($p['profile_photo'])): ?>
                    <img src="<?= APP_URL ?>/uploads/providers/<?= htmlspecialchars($p['profile_photo']) ?>" alt="<?= sanitize($p['business_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                    <?php else: ?>
                    <?= strtoupper(substr($p['business_name'],0,1)) ?>
                    <?php endif; ?>
                  </div>
                  <div class="flex-grow-1 min-width-0">
                    <div class="fw-700 text-truncate"><?= sanitize($p['business_name']) ?></div>
                    <div class="provider-meta">
                      <i class="bi <?= $p['icon'] ?>" style="color:<?= $p['color'] ?>"></i>
                      <?= sanitize($p['category_name']) ?>
                    </div>
                  </div>
                  <?php if ($p['avg_rating'] >= 4.5): ?>
                  <span class="badge bg-warning text-dark" style="font-size:.65rem">TOP RATED</span>
                  <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-1 mb-2">
                  <?= starHtml($p['avg_rating']) ?>
                  <span class="fw-700 ms-1"><?= $p['avg_rating'] ?: '–' ?></span>
                  <span class="text-muted small">(<?= $p['review_count'] ?> reviews)</span>
                </div>

                <p class="text-muted small mb-3" style="line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                  <?= sanitize($p['bio'] ?? 'Experienced professional home service provider ready to help.') ?>
                </p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                  <span class="badge" style="background:#eff6ff;color:#2563eb;font-weight:600">
                    <i class="bi bi-briefcase me-1"></i><?= $p['experience_years'] ?> yrs exp
                  </span>
                  <span class="badge" style="background:#f0fdf4;color:#059669;font-weight:600">
                    <i class="bi bi-check2-all me-1"></i><?= $p['completed_jobs'] ?? 0 ?> jobs
                  </span>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2" style="border-top:1px solid #f1f5f9">
                  <div>
                    <div class="provider-price" style="font-size:1.15rem">₹<?= number_format($p['base_price'], 0) ?></div>
                    <div style="font-size:.7rem;color:#94a3b8">starting price</div>
                  </div>
                  <a href="<?= APP_URL ?>/modules/user/book.php?provider_id=<?= $p['id'] ?>" class="btn btn-primary btn-sm px-4 fw-600">
                    <i class="bi bi-calendar-plus me-1"></i>Book Now
                  </a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
          <ul class="pagination pagination-sm gap-1">
            <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link rounded" href="<?= browseUrl(['page' => $page-1]) ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link rounded" href="<?= browseUrl(['page' => $i]) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <li class="page-item">
              <a class="page-link rounded" href="<?= browseUrl(['page' => $page+1]) ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
            <?php endif; ?>
          </ul>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
