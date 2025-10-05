<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

header_html('Add Lead');
?>

<h2>Add Lead</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <form method="post" action="/leads/add_eval.php" class="stack" id="addLeadForm">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="redirect" value="/leads/list.php">
    <input type="hidden" name="selected_contact_id" id="selectedContactId" value="">
    <input type="hidden" name="allow_duplicate_phone" id="allowDuplicatePhone" value="0">
    
    <h3>Primary Contact</h3>
    
    <!-- Selected Contact Display (hidden by default) -->
    <div id="selectedContactDisplay" style="display:none;">
      <div style="background:#f5f5f5;padding:12px;border-radius:4px;margin-bottom:12px;">
        <strong>Selected Contact:</strong> <span id="selectedContactName"></span>
        <div style="margin-top:8px;">
          <a href="#" class="button small" id="changeContactBtn">Change Contact</a>
          <a href="#" class="button small" id="clearContactBtn">Clear &amp; Create New</a>
        </div>
      </div>
    </div>
    
    <!-- Contact Form Fields (shown by default) -->
    <div id="contactFormFields">
      <div style="margin-bottom:12px;">
        <a href="#" class="button small" id="selectExistingContactBtn">Select Existing Contact</a>
      </div>
      
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <label>First Name
          <input type="text" name="first_name" id="firstName" value="">
        </label>
        <label>Last Name
          <input type="text" name="last_name" id="lastName" value="">
        </label>
      </div>

      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
        <label>Email
          <input type="email" name="email" id="email" value="">
        </label>
        <label>Phone Number
          <input type="tel" name="phone_number" id="phoneNumber" value="">
        </label>
      </div>

      <label>Organization
        <input type="text" name="organization" id="organization" value="">
      </label>
    </div>

    <h3 style="margin-top:2rem;">Lead Details</h3>
    
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Channel
        <input type="text" name="channel" value="" placeholder="e.g., Google, Zola">
      </label>
      <label>Party Type
        <input type="text" name="party_type" value="" placeholder="e.g., Wedding, Corporate">
      </label>
    </div>

    <label>Number of People
      <input type="number" name="number_of_people" min="0" value="">
    </label>

    <label>Description
      <textarea name="description" rows="4"></textarea>
    </label>

    <label style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" name="tour_scheduled" value="1">
      <span>Tour Scheduled?</span>
    </label>

    <div class="actions">
      <button class="primary" type="submit">Create Lead</button>
      <a class="button" href="/leads/list.php">Cancel</a>
    </div>
  </form>
</div>

<!-- Contact Selection Modal -->
<div id="contactSelectionModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Select Contact</h3>
      <button class="modal-close" id="closeContactModal">&times;</button>
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

<!-- Duplicate Contact Detection Modal -->
<div id="duplicateModal" class="modal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Possible Duplicate Contact</h3>
      <button class="modal-close" id="closeDuplicateModal">&times;</button>
    </div>
    <div class="modal-body">
      <p id="duplicateMessage" style="margin-bottom:1rem;"></p>
      <div id="duplicateContactsList" style="margin-bottom:1.5rem;"></div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button class="button" id="cancelDuplicateBtn">Cancel</button>
        <button class="button" id="createAnywayBtn" style="display:none;">Create Anyway</button>
        <button class="button primary" id="useExistingBtn">Use Existing Contact</button>
      </div>
    </div>
  </div>
</div>

<style>
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
.duplicate-contact {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 8px;
  background: #f9f9f9;
  cursor: pointer;
  transition: background 0.2s;
}
.duplicate-contact:hover {
  background: #e8f4f8;
}
.duplicate-contact.selected {
  background: #d4edfa;
  border-color: #0066cc;
}
.match-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.85em;
  margin-left: 8px;
}
.match-badge.email {
  background: #ffebee;
  color: #c62828;
}
.match-badge.phone {
  background: #fff3e0;
  color: #ef6c00;
}
</style>

<script>
(function() {
  var form = document.getElementById('addLeadForm');
  var selectedContactId = document.getElementById('selectedContactId');
  var selectedContactDisplay = document.getElementById('selectedContactDisplay');
  var selectedContactName = document.getElementById('selectedContactName');
  var contactFormFields = document.getElementById('contactFormFields');
  var selectExistingBtn = document.getElementById('selectExistingContactBtn');
  var changeContactBtn = document.getElementById('changeContactBtn');
  var clearContactBtn = document.getElementById('clearContactBtn');
  var allowDuplicatePhone = document.getElementById('allowDuplicatePhone');
  
  // Contact selection modal
  var modal = document.getElementById('contactSelectionModal');
  var closeModalBtn = document.getElementById('closeContactModal');
  var modalSearchInput = document.getElementById('modalSearchInput');
  var searchResults = document.getElementById('contactSearchResults');
  var searchTimer = null;
  
  // Duplicate detection modal
  var duplicateModal = document.getElementById('duplicateModal');
  var closeDuplicateModalBtn = document.getElementById('closeDuplicateModal');
  var duplicateMessage = document.getElementById('duplicateMessage');
  var duplicateContactsList = document.getElementById('duplicateContactsList');
  var cancelDuplicateBtn = document.getElementById('cancelDuplicateBtn');
  var createAnywayBtn = document.getElementById('createAnywayBtn');
  var useExistingBtn = document.getElementById('useExistingBtn');
  var currentDuplicates = [];
  var selectedDuplicateId = null;
  
  // Form fields
  var firstNameField = document.getElementById('firstName');
  var lastNameField = document.getElementById('lastName');
  var emailField = document.getElementById('email');
  var phoneField = document.getElementById('phoneNumber');
  var orgField = document.getElementById('organization');
  
  function showContactSelection() {
    selectedContactDisplay.style.display = 'block';
    contactFormFields.style.display = 'none';
    firstNameField.removeAttribute('required');
    lastNameField.removeAttribute('required');
  }
  
  function showContactForm() {
    selectedContactDisplay.style.display = 'none';
    contactFormFields.style.display = 'block';
    selectedContactId.value = '';
    selectedContactName.textContent = '';
    firstNameField.setAttribute('required', 'required');
    lastNameField.setAttribute('required', 'required');
  }
  
  function openModal() {
    modal.style.display = 'block';
    modalSearchInput.value = '';
    modalSearchInput.focus();
    searchResults.innerHTML = '<p class="small">Start typing to search for contacts...</p>';
  }
  
  function closeModal() {
    modal.style.display = 'none';
  }
  
  function closeDuplicateModalFn() {
    duplicateModal.style.display = 'none';
    currentDuplicates = [];
    selectedDuplicateId = null;
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
    selectedContactId.value = id;
    selectedContactName.textContent = name;
    showContactSelection();
    closeModal();
  }
  
  // Form submission with duplicate detection
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // If user has selected an existing contact, skip validation
    if (selectedContactId.value) {
      form.submit();
      return;
    }
    
    // Validate for duplicates
    var email = emailField.value.trim();
    var phone = phoneField.value.trim();
    
    // If both empty, just submit
    if (!email && !phone) {
      form.submit();
      return;
    }
    
    // Call validation endpoint
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/leads/add_validation_ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.success) {
            if (data.matches && data.matches.length > 0) {
              showDuplicateModal(data);
            } else {
              form.submit();
            }
          } else {
            alert('Error checking for duplicates: ' + (data.error || 'Unknown error'));
          }
        } catch (e) {
          console.error('Error parsing response:', e);
          form.submit(); // Submit anyway on error
        }
      }
    };
    xhr.send('email=' + encodeURIComponent(email) + '&phone_number=' + encodeURIComponent(phone));
  });
  
  function showDuplicateModal(data) {
    currentDuplicates = data.matches;
    selectedDuplicateId = currentDuplicates.length > 0 ? currentDuplicates[0].id : null;
    
    // Set message based on match type
    if (data.has_email_match) {
      duplicateMessage.textContent = 'A contact with this email address already exists. Would you like to use the existing contact instead?';
      createAnywayBtn.style.display = 'none'; // Cannot create with duplicate email
    } else {
      duplicateMessage.textContent = 'A contact with this phone number already exists. Would you like to use the existing contact instead?';
      createAnywayBtn.style.display = 'inline-block'; // Can create with duplicate phone
    }
    
    // Display matching contacts
    var html = '';
    currentDuplicates.forEach(function(contact, index) {
      var matchTypes = contact.match_type.split(',');
      var badges = '';
      matchTypes.forEach(function(type) {
        badges += '<span class="match-badge ' + type + '">' + type + ' match</span>';
      });
      
      html += '<div class="duplicate-contact' + (index === 0 ? ' selected' : '') + '" data-id="' + contact.id + '">';
      html += '<strong>' + contact.first_name + ' ' + contact.last_name + badges + '</strong>';
      if (contact.email) html += '<div class="small">Email: ' + contact.email + '</div>';
      if (contact.phone_number) html += '<div class="small">Phone: ' + contact.phone_number + '</div>';
      if (contact.organization) html += '<div class="small">Organization: ' + contact.organization + '</div>';
      html += '</div>';
    });
    duplicateContactsList.innerHTML = html;
    
    // Add click handlers
    var contactDivs = duplicateContactsList.querySelectorAll('.duplicate-contact');
    contactDivs.forEach(function(div) {
      div.addEventListener('click', function() {
        contactDivs.forEach(function(d) { d.classList.remove('selected'); });
        this.classList.add('selected');
        selectedDuplicateId = this.getAttribute('data-id');
      });
    });
    
    duplicateModal.style.display = 'block';
  }
  
  // Duplicate modal event listeners
  useExistingBtn.addEventListener('click', function() {
    if (selectedDuplicateId) {
      var selectedContact = currentDuplicates.find(function(c) { return c.id == selectedDuplicateId; });
      if (selectedContact) {
        selectContact(selectedDuplicateId, selectedContact.first_name + ' ' + selectedContact.last_name);
        closeDuplicateModalFn();
        // Submit the form
        form.submit();
      }
    }
  });
  
  createAnywayBtn.addEventListener('click', function() {
    allowDuplicatePhone.value = '1';
    closeDuplicateModalFn();
    form.submit();
  });
  
  cancelDuplicateBtn.addEventListener('click', closeDuplicateModalFn);
  closeDuplicateModalBtn.addEventListener('click', closeDuplicateModalFn);
  
  // Original event listeners
  selectExistingBtn.addEventListener('click', function(e) {
    e.preventDefault();
    openModal();
  });
  
  changeContactBtn.addEventListener('click', function(e) {
    e.preventDefault();
    openModal();
  });
  
  clearContactBtn.addEventListener('click', function(e) {
    e.preventDefault();
    showContactForm();
  });
  
  closeModalBtn.addEventListener('click', closeModal);
  
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeModal();
    }
  });
  
  duplicateModal.addEventListener('click', function(e) {
    if (e.target === duplicateModal) {
      closeDuplicateModalFn();
    }
  });
  
  modalSearchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function() {
      searchContacts(modalSearchInput.value);
    }, 300);
  });
  
  // ESC key to close modals
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      if (modal.style.display === 'block') {
        closeModal();
      }
      if (duplicateModal.style.display === 'block') {
        closeDuplicateModalFn();
      }
    }
  });
  
  // Initialize: show form by default
  showContactForm();
})();
</script>

<?php footer_html(); ?>
