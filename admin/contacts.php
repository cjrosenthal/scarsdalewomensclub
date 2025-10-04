<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

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

header_html('Contacts');
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
  <h2>Contacts</h2>
  <a class="button" href="/admin/contact_add.php">Add Contact</a>
</div>

<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card" style="margin-bottom:1rem;">
  <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
    <label style="flex:1;min-width:200px;">
      Search
      <input type="text" id="searchInput" value="<?=h($keyword)?>" placeholder="Search contacts...">
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

<div id="contactsContainer">
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
        <div id="paginationContainer" style="display:flex;gap:0.5rem;align-items:center;justify-content:center;flex-wrap:wrap;">
          <?php if ($page > 1): ?>
            <a class="button small" href="?keyword=<?=urlencode($keyword)?>&page=<?=$page-1?>&pageSize=<?=$pageSize?>">Previous</a>
          <?php endif; ?>
          
          <span style="margin:0 0.5rem;">Page <?=$page?> of <?=$totalPages?> (<?=$totalCount?> total)</span>
          
          <?php if ($page < $totalPages): ?>
            <a class="button small" href="?keyword=<?=urlencode($keyword)?>&page=<?=$page+1?>&pageSize=<?=$pageSize?>">Next</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function() {
  var searchInput = document.getElementById('searchInput');
  var pageSizeSelect = document.getElementById('pageSizeSelect');
  var debounceTimer = null;
  
  function updateContacts() {
    var keyword = searchInput.value.trim();
    var pageSize = pageSizeSelect.value;
    
    // Use AJAX to fetch updated contacts
    var xhr = new XMLHttpRequest();
    var url = '/admin/contact_search_ajax.php?keyword=' + encodeURIComponent(keyword) + 
              '&page=1&pageSize=' + encodeURIComponent(pageSize);
    
    xhr.open('GET', url, true);
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            document.getElementById('contactsContainer').innerHTML = data.html;
            
            // Update URL without reload
            var newUrl = '?keyword=' + encodeURIComponent(keyword) + 
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
  
  // Debounced search
  searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updateContacts, 600);
  });
  
  // Immediate update on page size change
  pageSizeSelect.addEventListener('change', updateContacts);
  
  // Handle pagination clicks via event delegation
  document.getElementById('contactsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('pagination-link')) {
      e.preventDefault();
      var page = e.target.getAttribute('data-page');
      var keyword = searchInput.value.trim();
      var pageSize = pageSizeSelect.value;
      
      var xhr = new XMLHttpRequest();
      var url = '/admin/contact_search_ajax.php?keyword=' + encodeURIComponent(keyword) + 
                '&page=' + encodeURIComponent(page) + 
                '&pageSize=' + encodeURIComponent(pageSize);
      
      xhr.open('GET', url, true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
              document.getElementById('contactsContainer').innerHTML = data.html;
              
              // Update URL without reload
              var newUrl = '?keyword=' + encodeURIComponent(keyword) + 
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
