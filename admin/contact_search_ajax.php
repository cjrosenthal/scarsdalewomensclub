<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

header('Content-Type: application/json');

// Get parameters
$keyword = trim($_GET['keyword'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 25)));

// Get contacts and count
if ($keyword !== '') {
    $contacts = ContactManagement::search($keyword, $page, $pageSize);
    $totalCount = ContactManagement::getTotalCount($keyword);
} else {
    $contacts = ContactManagement::listAll($page, $pageSize);
    $totalCount = ContactManagement::getTotalCount();
}

$totalPages = max(1, (int)ceil($totalCount / $pageSize));

// Build HTML
ob_start();
?>
<?php if (empty($contacts)): ?>
  <div class="card">
    <p class="small">No contacts found.</p>
  </div>
<?php else: ?>
  <div class="card">
    <table class="list">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Organization</th>
          <th>Phone</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $contact): ?>
          <tr>
            <td><?= h($contact['first_name'] . ' ' . $contact['last_name']) ?></td>
            <td><?= h($contact['email'] ?? '') ?></td>
            <td><?= h($contact['organization'] ?? '') ?></td>
            <td><?= h($contact['phone_number'] ?? '') ?></td>
            <td class="small">
              <a class="button small" href="/admin/contact_edit.php?id=<?= (int)$contact['id'] ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  
  <?php if ($totalPages > 1): ?>
    <div class="card" style="margin-top:1rem;">
      <div style="display:flex;gap:0.5rem;align-items:center;justify-content:center;flex-wrap:wrap;">
        <?php if ($page > 1): ?>
          <a class="button small pagination-link" href="#" data-page="<?=$page-1?>">Previous</a>
        <?php endif; ?>
        
        <span style="margin:0 0.5rem;">Page <?=$page?> of <?=$totalPages?> (<?=$totalCount?> total)</span>
        
        <?php if ($page < $totalPages): ?>
          <a class="button small pagination-link" href="#" data-page="<?=$page+1?>">Next</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'html' => $html,
    'totalCount' => $totalCount,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);
