<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/LeadManagement.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /leads/list.php?err=' . urlencode('Invalid lead ID.'));
    exit;
}

$lead = LeadManagement::findById($id);
if (!$lead) {
    header('Location: /leads/list.php?err=' . urlencode('Lead not found.'));
    exit;
}

$secondaryContacts = LeadManagement::getSecondaryContacts($id);
$contactUsageCount = LeadManagement::countLeadsUsingContact($lead['main_contact_id']);

header_html('Edit Lead');
?>

<h2>Edit Lead</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-button active" data-tab="view-tab">Primary Contact</button>
    <button class="tab-button" data-tab="replace-tab">Replace Primary Contact</button>
  </div>
  
  <form method="post" action="/leads/edit_eval.php" class="stack" id="editLeadForm">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/leads/edit.php?id=<?=h($id)?>">
    <input type="hidden" name="action" id="formAction" value="update_lead_only">
    <input type="hidden" name="new_contact_id" id="newContactId" value="">
    
    <!-- Tab 1: View/Edit Contact -->
    <div id="view-tab" class="tab-content active">
      <h3>Primary Contact Information</h3>
      
      <!-- Read-only view (default) -->
      <div id="contactReadOnly">
        <div style="background:#f5f5f5;padding:16px;border-radius:4px;margin-bottom:16px;">
          <div style="margin-bottom:12px;">
            <strong>Name:</strong> <?=h($lead['first_name'] . ' ' . $lead['last_name'])?>
          </div>
          <?php if ($lead['email']): ?>
            <div style="margin-bottom:12px;">
              <strong>Email:</strong> <?=h($lead['email'])?>
            </div>
          <?php endif; ?>
          <?php if ($lead['phone_number']): ?>
            <div style="margin-bottom:12px;">
              <strong>Phone:</strong> <?=h($lead['phone_number'])?>
            </div>
          <?php endif; ?>
          <?php if ($lead['organization']): ?>
            <div style="margin-bottom:12px;">
              <strong>Organization:</strong> <?=h($lead['organization'])?>
            </div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:12px;">
          <button type="button" class="button" id="editContactBtn">Edit Contact</button>
          <button type="button" class="button" id="replaceContactBtn">Replace Contact</button>
        </div>
      </div>
      
      <!-- Editable form (hidden by default) -->
      <div id="contactEditForm" style="display:none;">
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
          <label>First Name
            <input type="text" name="first_name" id="firstName" value="<?=h($lead['first_name'])?>" required>
          </label>
          <label>Last Name
            <input type="text" name="last_name" id="lastName" value="<?=h($lead['last_name'])?>" required>
          </label>
        </div>

        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
          <label>Email
            <input type="email" name="email" id="email" value="<?=h($lead['email'] ?? '')?>">
          </label>
          <label>Phone Number
            <input type="tel" name="phone_number" id="phoneNumber" value="<?=h($lead['phone_number'] ?? '')?>">
          </label>
        </div>

        <label>Organization
          <input type="text" name="organization" id="organization" value="<?=h($lead['organization'] ?? '')?>">
        </label>
        
        <div style="display:flex;gap:12px;">
          <button type="button" class="button" id="cancelEditBtn">Cancel</button>
        </div>
      </div>
    </div>
    
    <!-- Tab 2: Replace Contact -->
    <div id="replace-tab" class="tab-content" style="display:none;">
      <h3>Replace Primary Contact</h3>
      <p>Search for and select a different contact to be the primary contact for this lead.</p>
      
      <div id="selectedReplacementDisplay" style="display:none;">
        <div style="background:#f5f5f5;padding:12px;border-radius:4px;margin:12px 0;">
          <strong>New Primary Contact:</strong> <span id="selectedReplacementName"></span>
          <div style="margin-top:8px;">
            <a href="#" class="button small" id="clearReplacementBtn">Clear Selection</a>
          </div>
        </div>
      </div>
      
      <div id="currentContactDisplay">
        <div style="background:#f0f8ff;padding:12px;border-radius:4px;margin:12px 0;">
          <strong>Current Primary Contact:</strong> <?=h($lead['first_name'] . ' ' . $lead['last_name'])?>
        </div>
        <button type="button" class="button" id="searchReplacementBtn">Search for Contact</button>
      </div>
    </div>

    <h3 style="margin-top:2rem;">Lead Details</h3>
    
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Channel
        <input type="text" name="channel" value="<?=h($lead['channel'] ?? '')?>" placeholder="e.g., Google, Zola">
      </label>
      <label>Party Type
        <input type="text" name="party_type" value="<?=h($lead['party_type'] ?? '')?>" placeholder="e.g., Wedding, Corporate">
      </label>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Number of People
        <input type="number" name="number_of_people" min="0" value="<?=h($lead['number_of_people'] ?? '')?>">
      </label>
      <label>Status
        <select name="status">
          <option value="new" <?=$lead['status'] === 'new' ? 'selected' : ''?>>New</option>
          <option value="active" <?=$lead['status'] === 'active' ? 'selected' : ''?>>Active</option>
          <option value="converted_to_reservation" <?=$lead['status'] === 'converted_to_reservation' ? 'selected' : ''?>>Converted to Reservation</option>
          <option value="deleted" <?=$lead['status'] === 'deleted' ? 'selected' : ''?>>Deleted</option>
        </select>
      </label>
    </div>

    <label>Description
      <textarea name="description" rows="4"><?=h($lead['description'] ?? '')?></textarea>
    </label>

    <div class="actions">
      <button class="primary" type="submit">Update Lead</button>
      <a class="button" href="/leads/list.php">Cancel</a>
    </div>
  </form>
</div>

<!-- Secondary Contacts Section -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <h3 style="margin:0;">Secondary Contacts</h3>
    <button class="button small" id="addSecondaryContactBtn">Add Secondary Contact</button>
  </div>
  
  <?php if (empty($secondaryContacts)): ?>
    <p class="small">No secondary contacts.</p>
  <?php else: ?>
    <div class="stack" style="gap:8px;">
      <?php foreach ($secondaryContacts as $contact): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;background:#f5f5f5;border-radius:4px;">
          <div>
            <strong><?=h($contact['first_name'] . ' ' . $contact['last_name'])?></strong>
            <?php if ($contact['email']): ?>
              <span class="small" style="margin-left:12px;">Email: <?=h($contact['email'])?></span>
            <?php endif; ?>
            <?php if ($contact['organization']): ?>
              <span class="small" style="margin-left:12px;">Org: <?=h($contact['organization'])?></span>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:8px;">
            <button class="button small edit-secondary-btn" data-id="<?=(int)$contact['id']?>" data-name="<?=h($contact['first_name'] . ' ' . $contact['last_name'])?>">Edit</button>
            <button class="button small danger delete-secondary-btn" data-id="<?=(int)$contact['id']?>" data-name="<?=h($contact['first_name'] . ' ' . $contact['last_name'])?>">Delete</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Comments Section -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
    <h3 style="margin:0;">Comments</h3>
  </div>
  
  <!-- Add Comment Form -->
  <form id="addCommentForm" style="margin-bottom:2rem;">
    <label>Add Comment
      <textarea id="commentText" rows="3" placeholder="Add a comment about this lead..." required></textarea>
    </label>
    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
      <input type="checkbox" id="tourScheduled">
      <span>Tour Scheduled?</span>
    </label>
    <button type="submit" class="button primary">Add Comment</button>
    <div id="commentError" style="color:#c62828;margin-top:8px;display:none;"></div>
  </form>
  
  <!-- Comments List -->
  <div id="commentsList">
    <?php
    $comments = LeadManagement::getComments($id);
    if (empty($comments)): ?>
      <p class="small" id="noCommentsMsg">No comments yet.</p>
    <?php else: ?>
      <?php foreach ($comments as $comment): ?>
        <div class="comment-item">
          <div class="comment-header">
            <strong><?=h(($comment['first_name'] ?? 'Unknown') . ' ' . ($comment['last_name'] ?? 'User'))?></strong>
            <span class="comment-date"><?=h(date('M j, Y g:i A', strtotime($comment['created_at'])))?></span>
          </div>
          <div class="comment-text"><?=h($comment['comment_text'])?></div>
          <?php if ($comment['tour_scheduled']): ?>
            <span class="tour-badge">📅 Tour Scheduled</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Delete Lead Section -->
<div class="card">
  <h3>Delete Lead</h3>
  <p>Permanently mark this lead as deleted. This action can be reversed by changing the status back to active.</p>
  <form method="post" action="/leads/delete_eval.php" onsubmit="return confirm('Are you sure you want to delete this lead?');">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/leads/list.php">
    <button type="submit" class="button danger">Delete Lead</button>
  </form>
</div>

<!-- Edit Contact Warning Modal -->
<div id="editWarningModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Contact Warning</h3>
      <button class="modal-close" id="closeWarningModal">&times;</button>
    </div>
    <div class="modal-body">
      <p id="warningMessage"></p>
      <div style="margin-top:1.5rem;display:flex;gap:12px;justify-content:flex-end;">
        <button class="button" id="cancelWarningBtn">Cancel</button>
        <button class="button primary" id="proceedEditBtn">Proceed to Edit</button>
      </div>
    </div>
  </div>
</div>

<!-- Contact Search Modal -->
<div id="contactSearchModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitle">Select Contact</h3>
      <button class="modal-close" id="closeModal">&times;</button>
    </div>
    <div class="modal-body">
      <label>Search Contacts
        <input type="text" id="modalSearchInput" placeholder="Search by name, email, organization...">
      </label>
      <div id="contactSearchResults" style="margin-top:1rem;max-height:400px;overflow-y:auto;">
        <p class="small">Start typing to search for contacts...</p>
      </div>
    </div>
  </div>
</div>

<style>
.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 2px solid #ddd;
  margin-bottom: 1.5rem;
}
.tab-button {
  background: none;
  border: none;
  padding: 12px 24px;
  cursor: pointer;
  font-size: 1rem;
  color: #666;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}
.tab-button:hover {
  color: #333;
  background: #f5f5f5;
}
.tab-button.active {
  color: #0066cc;
  border-bottom-color: #0066cc;
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
.modal {
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
}
.modal-content {
  background-color: white;
  margin: 5% auto;
  padding: 0;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-header h3 {
  margin: 0;
}
.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-close:hover {
  color: #000;
}
.modal-body {
  padding: 1.5rem;
}
.contact-result {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: background 0.2s;
}
.contact-result:hover {
  background: #f5f5f5;
}
.contact-result strong {
  display: block;
  margin-bottom: 4px;
}
.contact-result .small {
  color: #666;
}
.comment-item {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 12px;
  background: #fafafa;
}
.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}
.comment-date {
  font-size: 0.85em;
  color: #666;
}
.comment-text {
  color: #333;
  line-height: 1.5;
  white-space: pre-wrap;
}
.tour-badge {
  display: inline-block;
  margin-top: 8px;
  padding: 4px 8px;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 4px;
  font-size: 0.85em;
}
</style>

<script>
(function() {
  var leadId = <?=(int)$id?>;
  var contactUsageCount = <?=(int)$contactUsageCount?>;
  var tabButtons = document.querySelectorAll('.tab-button');
  var tabContents = document.querySelectorAll('.tab-content');
  var formAction = document.getElementById('formAction');
  var newContactId = document.getElementById('newContactId');
  var modal = document.getElementById('contactSearchModal');
  var warningModal = document.getElementById('editWarningModal');
  var modalTitle = document.getElementById('modalTitle');
  var closeModalBtn = document.getElementById('closeModal');
  var modalSearchInput = document.getElementById('modalSearchInput');
  var searchResults = document.getElementById('contactSearchResults');
  var searchTimer = null;
  var modalMode = ''; // 'replace', 'add-secondary', 'edit-secondary'
  var editingSecondaryId = null;
  
  // Contact edit elements
  var contactReadOnly = document.getElementById('contactReadOnly');
  var contactEditForm = document.getElementById('contactEditForm');
  var editContactBtn = document.getElementById('editContactBtn');
  var replaceContactBtn = document.getElementById('replaceContactBtn');
  var cancelEditBtn = document.getElementById('cancelEditBtn');
  
  // Warning modal elements
  var warningMessage = document.getElementById('warningMessage');
  var closeWarningModal = document.getElementById('closeWarningModal');
  var cancelWarningBtn = document.getElementById('cancelWarningBtn');
  var proceedEditBtn = document.getElementById('proceedEditBtn');
  
  // Tab switching
  tabButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      var targetTab = this.getAttribute('data-tab');
      
      // Update buttons
      tabButtons.forEach(function(btn) {
        btn.classList.remove('active');
      });
      this.classList.add('active');
      
      // Update content
      tabContents.forEach(function(content) {
        content.classList.remove('active');
        content.style.display = 'none';
      });
      var targetContent = document.getElementById(targetTab);
      if (targetContent) {
        targetContent.classList.add('active');
        targetContent.style.display = 'block';
      }
      
      // Reset to read-only view when switching to Tab 1
      if (targetTab === 'view-tab') {
        contactReadOnly.style.display = 'block';
        contactEditForm.style.display = 'none';
        formAction.value = 'update_lead_only';
      }
      
      // Update form action based on tab
      if (targetTab === 'replace-tab') {
        formAction.value = 'replace';
      }
    });
  });
  
  // Edit contact button - show warning first
  editContactBtn.addEventListener('click', function() {
    if (contactUsageCount > 1) {
      warningMessage.textContent = 'Please note: This contact is used in ' + contactUsageCount + ' lead(s). Editing this contact will update the contact information across all leads.';
    } else {
      warningMessage.textContent = 'You are editing the contact information. This will update the contact record.';
    }
    warningModal.style.display = 'block';
  });
  
  // Replace contact button - switch to tab 2
  replaceContactBtn.addEventListener('click', function() {
    // Click the replace tab
    tabButtons[1].click();
  });
  
  // Warning modal - proceed to edit
  proceedEditBtn.addEventListener('click', function() {
    warningModal.style.display = 'none';
    contactReadOnly.style.display = 'none';
    contactEditForm.style.display = 'block';
    formAction.value = 'update_contact_and_lead';
  });
  
  // Warning modal - cancel
  cancelWarningBtn.addEventListener('click', function() {
    warningModal.style.display = 'none';
  });
  
  closeWarningModal.addEventListener('click', function() {
    warningModal.style.display = 'none';
  });
  
  // Cancel edit button
  cancelEditBtn.addEventListener('click', function() {
    contactReadOnly.style.display = 'block';
    contactEditForm.style.display = 'none';
    formAction.value = 'update_lead_only';
  });
  
  // Replacement contact selection
  var searchReplacementBtn = document.getElementById('searchReplacementBtn');
  var selectedReplacementDisplay = document.getElementById('selectedReplacementDisplay');
  var selectedReplacementName = document.getElementById('selectedReplacementName');
  var currentContactDisplay = document.getElementById('currentContactDisplay');
  var clearReplacementBtn = document.getElementById('clearReplacementBtn');
  
  searchReplacementBtn.addEventListener('click', function() {
    modalMode = 'replace';
    modalTitle.textContent = 'Select New Primary Contact';
    openModal();
  });
  
  clearReplacementBtn.addEventListener('click', function(e) {
    e.preventDefault();
    newContactId.value = '';
    selectedReplacementName.textContent = '';
    selectedReplacementDisplay.style.display = 'none';
    currentContactDisplay.style.display = 'block';
  });
  
  // Secondary contact management
  var addSecondaryBtn = document.getElementById('addSecondaryContactBtn');
  addSecondaryBtn.addEventListener('click', function() {
    modalMode = 'add-secondary';
    modalTitle.textContent = 'Add Secondary Contact';
    openModal();
  });
  
  // Edit secondary contact
  var editSecondaryBtns = document.querySelectorAll('.edit-secondary-btn');
  editSecondaryBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      editingSecondaryId = this.getAttribute('data-id');
      modalMode = 'edit-secondary';
      modalTitle.textContent = 'Replace Secondary Contact';
      openModal();
    });
  });
  
  // Delete secondary contact
  var deleteSecondaryBtns = document.querySelectorAll('.delete-secondary-btn');
  deleteSecondaryBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var contactId = this.getAttribute('data-id');
      var contactName = this.getAttribute('data-name');
      if (!confirm('Remove ' + contactName + ' as a secondary contact?')) {
        return;
      }
      
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/leads/secondary_contact_remove_eval.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
              window.location.reload();
            } else {
              alert('Error: ' + (data.error || 'Unknown error'));
            }
          } catch (e) {
            alert('Error processing response');
          }
        }
      };
      xhr.send('csrf=' + encodeURIComponent('<?=h(csrf_token())?>') + 
               '&lead_id=' + encodeURIComponent(leadId) + 
               '&contact_id=' + encodeURIComponent(contactId));
    });
  });
  
  // Modal functions
  function openModal() {
    modal.style.display = 'block';
    modalSearchInput.value = '';
    modalSearchInput.focus();
    searchResults.innerHTML = '<p class="small">Start typing to search for contacts...</p>';
  }
  
  function closeModal() {
    modal.style.display = 'none';
    modalMode = '';
    editingSecondaryId = null;
  }
  
  function searchContacts(keyword) {
    if (!keyword || keyword.trim() === '') {
      searchResults.innerHTML = '<p class="small">Start typing to search for contacts...</p>';
      return;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/contacts/search_ajax.php?keyword=' + encodeURIComponent(keyword) + '&pageSize=20', true);
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            displaySearchResults(data);
          }
        } catch (e) {
          searchResults.innerHTML = '<p class="error">Error loading contacts</p>';
        }
      }
    };
    xhr.send();
  }
  
  function displaySearchResults(data) {
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = data.html;
    var rows = tempDiv.querySelectorAll('tbody tr');
    
    if (rows.length === 0) {
      searchResults.innerHTML = '<p class="small">No contacts found.</p>';
      return;
    }
    
    var html = '';
    rows.forEach(function(row) {
      var cells = row.querySelectorAll('td');
      if (cells.length >= 4) {
        var name = cells[0].textContent.trim();
        var email = cells[1].textContent.trim();
        var org = cells[2].textContent.trim();
        var phone = cells[3].textContent.trim();
        var editLink = cells[4].querySelector('a');
        var contactId = editLink ? editLink.href.match(/id=(\d+)/)[1] : '';
        
        html += '<div class="contact-result" data-id="' + contactId + '" data-name="' + name + '">';
        html += '<strong>' + name + '</strong>';
        if (email) html += '<div class="small">Email: ' + email + '</div>';
        if (org) html += '<div class="small">Organization: ' + org + '</div>';
        if (phone) html += '<div class="small">Phone: ' + phone + '</div>';
        html += '</div>';
      }
    });
    
    searchResults.innerHTML = html;
    
    var resultDivs = searchResults.querySelectorAll('.contact-result');
    resultDivs.forEach(function(div) {
      div.addEventListener('click', function() {
        selectContact(this.getAttribute('data-id'), this.getAttribute('data-name'));
      });
    });
  }
  
  function selectContact(id, name) {
    if (modalMode === 'replace') {
      newContactId.value = id;
      selectedReplacementName.textContent = name;
      selectedReplacementDisplay.style.display = 'block';
      currentContactDisplay.style.display = 'none';
      closeModal();
    } else if (modalMode === 'add-secondary') {
      addSecondaryContact(id);
    } else if (modalMode === 'edit-secondary') {
      replaceSecondaryContact(editingSecondaryId, id);
    }
  }
  
  function addSecondaryContact(contactId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/leads/secondary_contact_add_eval.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            window.location.reload();
          } else {
            alert('Error: ' + (data.error || 'Unknown error'));
            closeModal();
          }
        } catch (e) {
          alert('Error processing response');
          closeModal();
        }
      }
    };
    xhr.send('csrf=' + encodeURIComponent('<?=h(csrf_token())?>') + 
             '&lead_id=' + encodeURIComponent(leadId) + 
             '&contact_id=' + encodeURIComponent(contactId));
  }
  
  function replaceSecondaryContact(oldContactId, newContactId) {
    // Remove old, add new
    var xhr1 = new XMLHttpRequest();
    xhr1.open('POST', '/leads/secondary_contact_remove_eval.php', true);
    xhr1.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr1.onload = function() {
      if (xhr1.status === 200) {
        var xhr2 = new XMLHttpRequest();
        xhr2.open('POST', '/leads/secondary_contact_add_eval.php', true);
        xhr2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr2.onload = function() {
          if (xhr2.status === 200) {
            window.location.reload();
          }
        };
        xhr2.send('csrf=' + encodeURIComponent('<?=h(csrf_token())?>') + 
                 '&lead_id=' + encodeURIComponent(leadId) + 
                 '&contact_id=' + encodeURIComponent(newContactId));
      }
    };
    xhr1.send('csrf=' + encodeURIComponent('<?=h(csrf_token())?>') + 
             '&lead_id=' + encodeURIComponent(leadId) + 
             '&contact_id=' + encodeURIComponent(oldContactId));
  }
  
  // Modal events
  closeModalBtn.addEventListener('click', closeModal);
  
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });
  
  warningModal.addEventListener('click', function(e) {
    if (e.target === warningModal) {
      warningModal.style.display = 'none';
    }
  });
  
  modalSearchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      searchContacts(modalSearchInput.value);
    }, 300);
  });
  
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      if (modal.style.display === 'block') {
        closeModal();
      }
      if (warningModal.style.display === 'block') {
        warningModal.style.display = 'none';
      }
    }
  });
  
  // Comment form submission
  var addCommentForm = document.getElementById('addCommentForm');
  var commentText = document.getElementById('commentText');
  var tourScheduled = document.getElementById('tourScheduled');
  var commentError = document.getElementById('commentError');
  var commentsList = document.getElementById('commentsList');
  
  addCommentForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    var text = commentText.value.trim();
    if (!text) {
      commentError.textContent = 'Please enter a comment.';
      commentError.style.display = 'block';
      return;
    }
    
    commentError.style.display = 'none';
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/leads/add_comment_eval.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            // Reload page to show new comment
            window.location.reload();
          } else {
            commentError.textContent = data.error || 'Error adding comment.';
            commentError.style.display = 'block';
          }
        } catch (e) {
          commentError.textContent = 'Error processing response.';
          commentError.style.display = 'block';
        }
      }
    };
    xhr.send('csrf=' + encodeURIComponent('<?=h(csrf_token())?>') + 
             '&lead_id=' + encodeURIComponent(leadId) + 
             '&comment_text=' + encodeURIComponent(text) + 
             '&tour_scheduled=' + (tourScheduled.checked ? '1' : '0'));
  });
})();
</script>

<?php footer_html(); ?>
