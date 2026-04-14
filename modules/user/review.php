<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

// Allow both 'customer' role AND users who are customers (not providers)
// A customer's role in session is 'customer'
if (currentRole() === 'admin' || currentRole() === 'provider') {
    redirect(APP_URL . '/modules/' . currentRole() . '/dashboard.php');
}

$db  = getDB();
$uid = currentUserId();
$bid = (int)($_GET['booking_id'] ?? 0);

if (!$bid) redirect(APP_URL . '/modules/user/bookings.php');

// Fetch completed booking that belongs to this customer
$stmt = $db->prepare("
    SELECT b.*, p.business_name, p.id as pid, u.name as provider_name
    FROM bookings b 
    JOIN providers p ON b.provider_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
");
$stmt->execute([$bid, $uid]);
$booking = $stmt->fetch();

if (!$booking) {
    ?>
    <!DOCTYPE html><html><head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    </head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="text-center p-5">
        <i class="bi bi-exclamation-circle text-danger" style="font-size:4rem"></i>
        <h4 class="mt-3">Cannot Write Review</h4>
        <p class="text-muted">This booking either doesn't exist, isn't completed yet, or doesn't belong to your account.</p>
        <a href="<?= APP_URL ?>/modules/user/bookings.php" class="btn btn-primary">← Back to Bookings</a>
    </div>
    </body></html>
    <?php
    exit;
}

// Check if already reviewed
$chk = $db->prepare("SELECT id, rating, review_text FROM reviews WHERE booking_id = ?");
$chk->execute([$bid]);
$existingReview = $chk->fetch();

if ($existingReview) {
    redirect(APP_URL . '/modules/user/booking_detail.php?id=' . $bid . '&already_reviewed=1');
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $text   = sanitize($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a star rating before submitting.';
    } else {
        $ins = $db->prepare("INSERT INTO reviews (booking_id, customer_id, provider_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$bid, $uid, $booking['pid'], $rating, $text]);
        redirect(APP_URL . '/modules/user/booking_detail.php?id=' . $bid . '&reviewed=1');
    }
}

$pageTitle = 'Write a Review';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<style>
/* Star rating - click-friendly, left-to-right display */
.star-select { display: flex; gap: 8px; justify-content: center; margin: 1rem 0; }
.star-select input[type="radio"] { display: none; }
.star-select label {
  font-size: 2.5rem; color: #d1d5db; cursor: pointer;
  transition: color .15s, transform .1s;
}
.star-select label:hover { transform: scale(1.15); }
/* Highlight selected and all before it */
.star-select input[type="radio"]:checked ~ label { color: #d1d5db; }
.star-select label.selected,
.star-select label.hovered { color: #f59e0b; }
.rating-hint { font-size: .85rem; color: #6b7280; min-height: 1.2em; margin-top: .5rem; }
</style>



<?php require_once __DIR__ . '/../../includes/topnav_user.php'; ?>

<div class="user-main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
      </button>
      <div class="topbar-title">Write a Review</div>
    </div>
    <a href="<?= APP_URL ?>/modules/user/booking_detail.php?id=<?= $bid ?>" 
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="page-content">
    <div class="row justify-content-center">
      <div class="col-md-7 col-lg-6">

        <!-- Provider info card -->
        <div class="card mb-4">
          <div class="card-body text-center py-4">
            <div class="avatar-circle mx-auto mb-3" style="width:64px;height:64px;font-size:1.6rem">
              <?= strtoupper(substr($booking['business_name'], 0, 1)) ?>
            </div>
            <h5 class="fw-700 mb-1"><?= sanitize($booking['business_name']) ?></h5>
            <div class="text-muted small mb-1">
              <i class="bi bi-person"></i> <?= sanitize($booking['provider_name']) ?>
            </div>
            <div class="text-muted small">
              Booking <strong>#<?= $bid ?></strong> &bull; 
              Completed on <?= formatDate($booking['scheduled_date']) ?>
            </div>
          </div>
        </div>

        <!-- Review form card -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i class="bi bi-star-fill text-warning"></i> Rate Your Experience
            </span>
          </div>
          <div class="card-body">

            <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="reviewForm"><?= csrfField() ?>

              <!-- Star Rating -->
              <div class="text-center mb-4">
                <label class="form-label fw-600 d-block mb-1">
                  How would you rate this service?
                </label>
                <p class="text-muted small mb-3">Tap a star to select your rating</p>

                <!-- Stars displayed LEFT to RIGHT (1→5) -->
                <div class="star-select" id="starContainer">
                  <input type="hidden" name="rating" id="ratingInput" value="0">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                  <label class="star-label" data-value="<?= $i ?>">
                    <i class="bi bi-star-fill"></i>
                  </label>
                  <?php endfor; ?>
                </div>
                <div class="rating-hint" id="ratingHint">No rating selected</div>
              </div>

              <!-- Review Text -->
              <div class="mb-4">
                <label class="form-label fw-600">
                  Your Review 
                  <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea 
                  name="review_text" 
                  class="form-control" 
                  rows="4" 
                  maxlength="1000"
                  placeholder="Tell others about your experience — was the provider punctual? Was the work quality good? Would you recommend them?"
                ></textarea>
                <div class="text-muted small mt-1">Max 1000 characters</div>
              </div>

              <!-- Buttons -->
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning btn-lg fw-600" id="submitBtn" disabled>
                  <i class="bi bi-star-fill"></i> Submit Review
                </button>
                <a href="<?= APP_URL ?>/modules/user/booking_detail.php?id=<?= $bid ?>" 
                   class="btn btn-outline-secondary">
                  Skip – I'll review later
                </a>
              </div>

            </form>
          </div>
        </div>

        <!-- What happens after review -->
        <div class="card mt-3" style="background:#f0fdf4;border-color:#bbf7d0">
          <div class="card-body py-3">
            <div class="d-flex gap-2 align-items-start">
              <i class="bi bi-info-circle-fill text-success mt-1"></i>
              <div class="small text-success">
                <strong>Your review helps the community.</strong> Ratings are used to rank providers 
                so other customers can find the best professionals. Reviews are visible to everyone.
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>



<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const hints = ['', 'Poor – Not satisfied', 'Fair – Below expectations', 'Good – Met expectations', 'Very Good – Above expectations', 'Excellent – Highly recommended!'];
const stars  = document.querySelectorAll('.star-label');
const input  = document.getElementById('ratingInput');
const hint   = document.getElementById('ratingHint');
const btn    = document.getElementById('submitBtn');
let selected = 0;

function paintStars(upTo) {
  stars.forEach((s, i) => {
    s.style.color = i < upTo ? '#f59e0b' : '#d1d5db';
  });
}

stars.forEach((star, idx) => {
  const val = idx + 1;

  star.addEventListener('mouseenter', () => {
    paintStars(val);
    hint.textContent = hints[val];
  });

  star.addEventListener('mouseleave', () => {
    paintStars(selected);
    hint.textContent = selected ? hints[selected] : 'No rating selected';
  });

  star.addEventListener('click', () => {
    selected = val;
    input.value = val;
    paintStars(val);
    hint.textContent = hints[val];
    hint.style.color = '#f59e0b';
    btn.disabled = false;
    btn.textContent = '';
    btn.innerHTML = '<i class="bi bi-star-fill"></i> Submit ' + val + '-Star Review';
  });
});

// Prevent submit if no star selected
document.getElementById('reviewForm').addEventListener('submit', function(e) {
  if (!selected) {
    e.preventDefault();
    hint.style.color = '#dc2626';
    hint.textContent = '⚠ Please select a star rating first!';
  }
});
</script>
