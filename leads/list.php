<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

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

header_html('Leads');
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
  <h2>Leads</h2>
  <a class="button" href="/leads/add.php">Add Lead</a>
</div>

<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card" style="margin-bottom:1rem;">
  <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
    <label style="width:150px;">
      Status
      <select id="statusSelect">
        <option value="active" <?=$status === 'active' ? 'selected' : ''?>>Active</option>
        <option value="new" <?=$status === 'new' ? 'selected' : ''?>>New</option>
        <option value="converted_to_reservation" <?=$status === 'converted_to_reservation' ? 'selected' : ''?>>Converted</option>
        <option value="deleted" <?=$status === 'deleted' ? 'selected' : ''?>>Deleted</option>
      </select>
    </label>
    <label style="flex:1;min-width:200px;">
      Search
      <input type="text" id="searchInput" value="<?=h($keyword)?>" placeholder="Search leads...">
    </label>
    <label style="width:120px;">
      Page Size
      <select id="pageSizeSelect">
        <option value="10" <?=$pageSize === 10 ? 'selected' : ''?>>10</option>
        <option value="25" <?=$pageSize === 25 ? 'selected' : ''?>>25</option>
        <option value="50" <?=$pageSize === 50 ? 'selected' : ''?>>50</option>
        <option value="100" <?=$pageSize === 100 ? 'selected' : ''?>>100</option>
      </select>
    </label>
  </div>
</div>

<div id="leadsContainer">
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
        <div id="paginationContainer" style="display:flex;gap:0.5rem;align-items:center;justify-content:center;flex-wrap:wrap;">
          <?php if ($page > 1): ?>
            <a class="button small" href="?status=<?=urlencode($status)?>&keyword=<?=urlencode($keyword)?>&page=<?=$page-1?>&pageSize=<?=$pageSize?>">Previous</a>
          <?php endif; ?>
          
          <span style="margin:0 0.5rem;">Page <?=$page?> of <?=$totalPages?> (<?=$totalCount?> total)</span>
          
          <?php if ($page < $totalPages): ?>
            <a class="button small" href="?status=<?=urlencode($status)?>&keyword=<?=urlencode($keyword)?>&page=<?=$page+1?>&pageSize=<?=$pageSize?>">Next</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function() {
  var statusSelect = document.getElementById('statusSelect');
  var searchInput = document.getElementById('searchInput');
  var pageSizeSelect = document.getElementById('pageSizeSelect');
  var debounceTimer = null;
  
  function updateLeads() {
    var status = statusSelect.value;
    var keyword = searchInput.value.trim();
    var pageSize = pageSizeSelect.value;
    
    // Use AJAX to fetch updated leads
    var xhr = new XMLHttpRequest();
    var url = '/leads/search_ajax.php?status=' + encodeURIComponent(status) +
              '&keyword=' + encodeURIComponent(keyword) + 
              '&page=1&pageSize=' + encodeURIComponent(pageSize);
    
    xhr.open('GET', url, true);
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            document.getElementById('leadsContainer').innerHTML = data.html;
            
            // Update URL without reload
            var newUrl = '?status=' + encodeURIComponent(status) +
                         '&keyword=' + encodeURIComponent(keyword) + 
                         '&page=1&pageSize=' + encodeURIComponent(pageSize);
            window.history.replaceState({}, '', newUrl);
          }
        } catch (e) {
          console.error('Error parsing response:', e);
        }
      }
    };
    xhr.send();
  }
  
  // Immediate update on status change
  statusSelect.addEventListener('change', updateLeads);
  
  // Debounced search
  searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updateLeads, 600);
  });
  
  // Immediate update on page size change
  pageSizeSelect.addEventListener('change', updateLeads);
  
  // Handle pagination clicks via event delegation
  document.getElementById('leadsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('pagination-link')) {
      e.preventDefault();
      var page = e.target.getAttribute('data-page');
      var status = statusSelect.value;
      var keyword = searchInput.value.trim();
      var pageSize = pageSizeSelect.value;
      
      var xhr = new XMLHttpRequest();
      var url = '/leads/search_ajax.php?status=' + encodeURIComponent(status) +
                '&keyword=' + encodeURIComponent(keyword) + 
                '&page=' + encodeURIComponent(page) + 
                '&pageSize=' + encodeURIComponent(pageSize);
      
      xhr.open('GET', url, true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
              document.getElementById('leadsContainer').innerHTML = data.html;
              
              // Update URL without reload
              var newUrl = '?status=' + encodeURIComponent(status) +
                           '&keyword=' + encodeURIComponent(keyword) + 
                           '&page=' + encodeURIComponent(page) + 
                           '&pageSize=' + encodeURIComponent(pageSize);
              window.history.replaceState({}, '', newUrl);
            }
          } catch (e) {
            console.error('Error parsing response:', e);
          }
        }
      };
      xhr.send();
    }
  });
})();
</script>

<?php footer_html(); ?>
