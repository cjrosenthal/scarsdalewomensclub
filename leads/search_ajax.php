<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
Application::init();
require_login();

header('Content-Type: application/json');

// Get parameters
$status = trim($_GET['status'] ?? 'active');
if (!in_array($status, ['new', 'active', 'converted_to_reservation', 'deleted'])) {
    $status = 'active';
}
$keyword = trim($_GET['keyword'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = max(1, min(100, (int)($_GET['pageSize'] ?? 25)));

// Get leads and count
if ($keyword !== '') {
    $leads = LeadManagement::search($status, $keyword, $page, $pageSize);
    $totalCount = LeadManagement::getTotalCount($status, $keyword);
} else {
    $leads = LeadManagement::listAll($status, $page, $pageSize);
    $totalCount = LeadManagement::getTotalCount($status);
}

$totalPages = max(1, (int)ceil($totalCount / $pageSize));

// Build HTML
ob_start();
?>
<?php if (empty($leads)): ?>
  <div class="card">
    <p class="small">No leads found.</p>
  </div>
<?php else: ?>
  <div class="card">
    <table class="list">
      <thead>
        <tr>
          <th>Main Contact</th>
          <th>Channel</th>
          <th>Party Type</th>
          <th># People</th>
          <th>Created</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $lead): ?>
          <tr>
            <td><?= h($lead['first_name'] . ' ' . $lead['last_name']) ?></td>
            <td><?= h($lead['channel'] ?? '') ?></td>
            <td><?= h($lead['party_type'] ?? '') ?></td>
            <td><?= $lead['number_of_people'] !== null ? h((string)$lead['number_of_people']) : '' ?></td>
            <td class="small"><?= h(date('M j, Y', strtotime($lead['created_at']))) ?></td>
            <td class="small">
              <a class="button small" href="/leads/edit.php?id=<?= (int)$lead['id'] ?>">Edit</a>
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
