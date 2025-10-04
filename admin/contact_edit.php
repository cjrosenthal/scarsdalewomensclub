<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/contacts.php?err=' . urlencode('Invalid contact ID.'));
    exit;
}

$contact = ContactManagement::findById($id);
if (!$contact) {
    header('Location: /admin/contacts.php?err=' . urlencode('Contact not found.'));
    exit;
}

// For repopulating form after errors - prioritize URL parameters over DB data
$form = [
    'first_name' => $_GET['first_name'] ?? $contact['first_name'],
    'last_name' => $_GET['last_name'] ?? $contact['last_name'],
    'email' => $_GET['email'] ?? $contact['email'],
    'organization' => $_GET['organization'] ?? $contact['organization'],
    'phone_number' => $_GET['phone_number'] ?? $contact['phone_number']
];

header_html('Edit Contact');
?>

<h2>Edit Contact</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <form method="post" action="/admin/contact_edit_eval.php" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/admin/contacts.php">
    
    <h3>Contact Information</h3>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>First Name
        <input type="text" name="first_name" value="<?=h($form['first_name'])?>" required>
      </label>
      <label>Last Name
        <input type="text" name="last_name" value="<?=h($form['last_name'])?>" required>
      </label>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Email
        <input type="email" name="email" value="<?=h($form['email'] ?? '')?>">
      </label>
      <label>Phone Number
        <input type="tel" name="phone_number" value="<?=h($form['phone_number'] ?? '')?>">
      </label>
    </div>

    <label>Organization
      <input type="text" name="organization" value="<?=h($form['organization'] ?? '')?>">
    </label>

    <div class="actions">
      <button class="primary" type="submit">Update Contact</button>
      <a class="button" href="/admin/contacts.php">Cancel</a>
    </div>
  </form>
</div>

<div class="card">
  <h3>Delete Contact</h3>
  <p>Permanently delete this contact. This action cannot be undone.</p>
  <form method="post" action="/admin/contact_delete_eval.php" onsubmit="return confirm('Are you sure you want to delete this contact? This action cannot be undone.');">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/admin/contacts.php">
    <button type="submit" class="button danger">Delete Contact</button>
  </form>
</div>

<?php footer_html(); ?>
