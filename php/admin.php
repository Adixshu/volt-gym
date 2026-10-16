<?php
declare(strict_types=1);

// Harden the private dashboard response and its session cookie.
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/config.php';

// Escape database and session values before rendering them into HTML.
function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectToAdmin(): never
{
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

// Handle sign-in, sign-out, and inbox read-status actions.
$errorMessage = '';
$isAuthenticated = isset($_SESSION['admin_id'], $_SESSION['admin_username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['admin_csrf'], $submittedToken)) {
        http_response_code(400);
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif (isset($_POST['logout']) && $isAuthenticated) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        redirectToAdmin();
    } elseif ($isAuthenticated && isset($_POST['read_action'])) {
        $submissionType = is_string($_POST['submission_type'] ?? null) ? $_POST['submission_type'] : '';
        $submissionId = filter_var($_POST['submission_id'] ?? null, FILTER_VALIDATE_INT);
        $readAction = is_string($_POST['read_action']) ? $_POST['read_action'] : '';

        if (!in_array($submissionType, ['review', 'inquiry'], true)
            || $submissionId === false
            || $submissionId < 1
            || !in_array($readAction, ['read', 'unread'], true)
        ) {
            http_response_code(400);
            $errorMessage = 'That inbox update is invalid. Refresh the page and try again.';
        } else {
            try {
                $connection = databaseConnection();
                if ($readAction === 'read') {
                    $statement = $connection->prepare(
                        'INSERT INTO admin_read_status (submission_type, submission_id) VALUES (:type, :id)
                         ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP'
                    );
                } else {
                    $statement = $connection->prepare(
                        'DELETE FROM admin_read_status WHERE submission_type = :type AND submission_id = :id'
                    );
                }
                $statement->execute(['type' => $submissionType, 'id' => $submissionId]);
                header('Location: admin.php#new-items');
                exit;
            } catch (Throwable $error) {
                error_log($error->getMessage());
                $errorMessage = 'The inbox status could not be updated. Check that the database tables are up to date.';
            }
        }
    } elseif (!$isAuthenticated) {
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        try {
            $statement = databaseConnection()->prepare(
                'SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1'
            );
            $statement->execute(['username' => $username]);
            $admin = $statement->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
                redirectToAdmin();
            }
            $errorMessage = 'The username or password was not recognized.';
        } catch (Throwable $error) {
            error_log($error->getMessage());
            $errorMessage = 'Admin sign-in is unavailable. Check that MySQL is running and the database is set up.';
        }
    }
}

$reviewSubmissions = [];
$inquirySubmissions = [];
$dashboardError = $errorMessage;

// Load and sort the inbox only for an authenticated dashboard session.
if ($isAuthenticated && $errorMessage === '') {
    try {
        $connection = databaseConnection();
        $reviewSubmissions = $connection->query(
            "SELECT reviews.id, 'review' AS submission_type, reviews.name, reviews.rating,
                    reviews.message, reviews.email, reviews.phone, NULL AS goal, reviews.created_at,
                    admin_read_status.read_at
             FROM reviews
             LEFT JOIN admin_read_status
               ON admin_read_status.submission_type = 'review'
              AND admin_read_status.submission_id = reviews.id"
        )->fetchAll();
        $inquirySubmissions = $connection->query(
            "SELECT inquiries.id, 'inquiry' AS submission_type, inquiries.name, NULL AS rating,
                    inquiries.message, inquiries.email, inquiries.phone, inquiries.goal, inquiries.created_at,
                    admin_read_status.read_at
             FROM inquiries
             LEFT JOIN admin_read_status
               ON admin_read_status.submission_type = 'inquiry'
              AND admin_read_status.submission_id = inquiries.id"
        )->fetchAll();
        $sortSubmissions = static fn (array $left, array $right): int =>
            [$right['created_at'], (int) $right['id']] <=> [$left['created_at'], (int) $left['id']];
        usort($reviewSubmissions, $sortSubmissions);
        usort($inquirySubmissions, $sortSubmissions);
    } catch (Throwable $error) {
        error_log($error->getMessage());
        $dashboardError = 'Feedback and enquiries could not be loaded. Check that the database tables are up to date.';
    }
}

$csrfToken = escape($_SESSION['admin_csrf']);
// Apply the saved site theme before the dashboard's stylesheet is displayed.
$themeScript = <<<'HTML'
<script>
  try {
    if (localStorage.getItem("volt-theme") === "light")
      document.documentElement.classList.add("light-theme");
  } catch (error) {}
</script>
HTML;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Admin dashboard | Volt Gym</title>
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <?= $themeScript ?>
    <link rel="stylesheet" href="../assets/css/admin.css?v=3" />
  </head>
  <body>
    <!-- Shared dashboard header and account actions. -->
    <header class="admin-header">
      <a class="brand" href="../index.html" aria-label="Volt Gym home">
        <span class="brand-mark"><i class="bi bi-lightning-charge-fill"></i></span>
        <span>VOLT <b>GYM</b><small>ADMIN</small></span>
      </a>
      <div class="header-actions">
        <a class="back-link" href="../index.html"><i class="bi bi-arrow-left"></i> View site</a>
        <button class="theme-toggle" type="button" aria-label="Switch theme">
          <i class="bi bi-sun-fill"></i>
        </button>
        <?php if ($isAuthenticated): ?>
          <form method="post" class="logout-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />
            <button class="logout-button" type="submit" name="logout" value="1">
              Sign out <i class="bi bi-box-arrow-right"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <main class="<?= $isAuthenticated ? 'dashboard' : 'login-layout' ?>">
      <!-- The sign-in form is shown until an administrator is authenticated. -->
      <?php if (!$isAuthenticated): ?>
        <section class="login-card" aria-labelledby="login-title">
          <p class="eyebrow"><span></span> VOLT GYM · STAFF ACCESS</p>
          <h1 id="login-title">Admin <em>sign in.</em></h1>
          <p class="login-copy">Sign in to review member feedback and visit enquiries.</p>
          <?php if ($errorMessage !== ''): ?>
            <p class="alert" role="alert"><?= escape($errorMessage) ?></p>
          <?php endif; ?>
          <form method="post" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />
            <label for="username">Username</label>
            <input
              id="username"
              name="username"
              type="text"
              maxlength="60"
              autocomplete="username"
              required
              autofocus
            />
            <label for="password">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
            />
            <button class="primary-button" type="submit">
              Sign in <i class="bi bi-arrow-right"></i>
            </button>
          </form>
          <a class="login-back" href="../index.html">← Back to Volt Gym</a>
        </section>
      <?php else: ?>
        <!-- Authenticated inbox overview and submission groups. -->
        <section class="dashboard-heading">
          <div>
            <p class="eyebrow"><span></span> MEMBER VOICE</p>
            <h1>Feedback <em>& enquiries.</em></h1>
            <p>Welcome back, <?= escape($_SESSION['admin_username']) ?>. Here’s what members have shared.</p>
          </div>
          <span class="live-badge"><i class="bi bi-circle-fill"></i> Live inbox</span>
        </section>

        <?php if ($dashboardError !== ''): ?>
          <p class="alert dashboard-alert" role="alert"><?= escape($dashboardError) ?></p>
        <?php else: ?>
          <?php
          $allSubmissions = array_merge($reviewSubmissions, $inquirySubmissions);
          $newSubmissions = array_filter($allSubmissions, static fn (array $submission): bool => $submission['read_at'] === null);
          $readSubmissions = array_filter($allSubmissions, static fn (array $submission): bool => $submission['read_at'] !== null);
          $inboxGroups = [
              'new' => [
                  'title' => 'New items',
                  'description' => 'NEEDS YOUR ATTENTION',
                  'items' => [
                      ['title' => 'Reviews', 'type' => 'review', 'items' => array_filter($reviewSubmissions, static fn (array $item): bool => $item['read_at'] === null)],
                      ['title' => 'Enquiries', 'type' => 'inquiry', 'items' => array_filter($inquirySubmissions, static fn (array $item): bool => $item['read_at'] === null)],
                  ],
              ],
              'read' => [
                  'title' => 'Already read',
                  'description' => 'PREVIOUSLY REVIEWED',
                  'items' => [
                      ['title' => 'Reviews', 'type' => 'review', 'items' => array_filter($reviewSubmissions, static fn (array $item): bool => $item['read_at'] !== null)],
                      ['title' => 'Enquiries', 'type' => 'inquiry', 'items' => array_filter($inquirySubmissions, static fn (array $item): bool => $item['read_at'] !== null)],
                  ],
              ],
          ];
          ?>
          <section class="summary-grid" aria-label="Submission totals">
            <article class="summary-card">
              <span class="summary-icon"><i class="bi bi-star"></i></span>
              <div><p>Total reviews</p><strong><?= count($reviewSubmissions) ?></strong></div>
            </article>
            <article class="summary-card">
              <span class="summary-icon"><i class="bi bi-inbox"></i></span>
              <div><p>Total enquiries</p><strong><?= count($inquirySubmissions) ?></strong></div>
            </article>
          </section>

          <?php foreach ($inboxGroups as $groupKey => $inbox): ?>
            <?php if ($groupKey === 'new'): ?>
              <section class="inbox-section new-inbox" id="new-items" aria-labelledby="new-items-title">
                <div class="section-heading">
                  <div>
                    <p class="eyebrow"><span></span> <?= escape($inbox['description']) ?></p>
                    <h2 id="new-items-title"><?= escape($inbox['title']) ?></h2>
                  </div>
                </div>
            <?php else: ?>
              <details class="read-inbox" id="read-items">
                <summary class="read-inbox-summary">
                  <span>
                    <span class="eyebrow"><span></span> <?= escape($inbox['description']) ?></span>
                    <strong><?= escape($inbox['title']) ?></strong>
                  </span>
                  <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </summary>
                <div class="read-inbox-content">
            <?php endif; ?>
                <?php foreach ($inbox['items'] as $category): ?>
                  <details class="inbox-category">
                    <summary class="category-heading">
                      <h3 id="<?= escape($category['type']) ?>-<?= escape($groupKey) ?>-title">
                        <?= escape($category['title']) ?>
                        <small><?= $groupKey === 'new' ? 'New' : 'Read' ?> · <?= count($category['items']) ?></small>
                      </h3>
                      <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </summary>
                  <?php if ($category['items'] === []): ?>
                    <div class="empty-state"><i class="bi <?= $category['type'] === 'review' ? 'bi-star' : 'bi-inbox' ?>"></i><p><?= $groupKey === 'new' ? 'No new ' : 'No read ' ?><?= escape(strtolower($category['title'])) ?>.</p><span><?= $groupKey === 'new' ? 'New ' . escape(strtolower($category['title'])) . ' will appear here.' : 'Items marked as read will be kept here.' ?></span></div>
                  <?php else: ?>
                    <div class="record-list">
                      <?php foreach ($category['items'] as $submission): ?>
                        <article class="record-card <?= $submission['submission_type'] === 'inquiry' ? 'inquiry-card' : '' ?>">
                          <div class="record-topline">
                            <div class="record-person">
                              <span class="avatar"><?= escape(strtoupper(substr($submission['name'], 0, 1))) ?></span>
                              <div>
                                <h3><?= escape($submission['name']) ?></h3>
                                <time><?= escape($submission['created_at']) ?></time>
                              </div>
                            </div>
                            <div class="record-actions">
                              <span class="record-status <?= $groupKey === 'new' ? 'unread' : '' ?>"><?= $groupKey === 'new' ? 'New' : 'Read' ?></span>
                              <?php if ($submission['submission_type'] === 'review'): ?>
                                <span class="rating" aria-label="<?= (int) $submission['rating'] ?> out of 5 stars">
                                  <?= str_repeat('★', (int) $submission['rating']) ?><span><?= str_repeat('★', 5 - (int) $submission['rating']) ?></span>
                                </span>
                              <?php endif; ?>
                              <span class="contact-links">
                                <?php if ($submission['email'] !== null && $submission['email'] !== ''): ?>
                                  <a class="contact-link" href="mailto:<?= escape($submission['email']) ?>">
                                    <i class="bi bi-envelope"></i> <?= escape($submission['email']) ?>
                                  </a>
                                <?php endif; ?>
                                <?php if ($submission['phone'] !== null && $submission['phone'] !== ''): ?>
                                  <a class="contact-link" href="tel:<?= escape((string) preg_replace('/[^\d+]/', '', $submission['phone'])) ?>">
                                    <i class="bi bi-telephone"></i> <?= escape($submission['phone']) ?>
                                  </a>
                                <?php endif; ?>
                              </span>
                            </div>
                          </div>
                          <?php if ($submission['goal'] !== null && $submission['goal'] !== ''): ?>
                            <p class="goal-tag"><i class="bi bi-bullseye"></i> <?= escape($submission['goal']) ?></p>
                          <?php endif; ?>
                          <?php if ($submission['message'] !== null && $submission['message'] !== ''): ?>
                            <p class="record-message"><?= nl2br(escape($submission['message'])) ?></p>
                          <?php endif; ?>
                          <form method="post" class="read-status-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>" />
                            <input type="hidden" name="submission_type" value="<?= escape($submission['submission_type']) ?>" />
                            <input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>" />
                            <input type="hidden" name="read_action" value="<?= $groupKey === 'new' ? 'read' : 'unread' ?>" />
                            <button type="submit" class="read-status-button">
                              <i class="bi <?= $groupKey === 'new' ? 'bi-check2' : 'bi-arrow-counterclockwise' ?>"></i>
                              <?= $groupKey === 'new' ? 'Mark as read' : 'Move to new' ?>
                            </button>
                          </form>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  </details>
                <?php endforeach; ?>
            <?php if ($groupKey === 'new'): ?>
              </section>
            <?php else: ?>
                </div>
              </details>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      <?php endif; ?>
    </main>
    <script>
      const themeToggle = document.querySelector(".theme-toggle");
      const updateThemeButton = () => {
        const light = document.documentElement.classList.contains("light-theme");
        themeToggle.setAttribute("aria-label", light ? "Switch to dark theme" : "Switch to light theme");
        themeToggle.querySelector("i").className = light ? "bi bi-moon-fill" : "bi bi-sun-fill";
      };
      updateThemeButton();
      themeToggle.addEventListener("click", () => {
        const light = document.documentElement.classList.toggle("light-theme");
        localStorage.setItem("volt-theme", light ? "light" : "dark");
        updateThemeButton();
      });
    </script>
  </body>
</html>
