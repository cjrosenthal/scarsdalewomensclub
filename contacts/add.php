<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/ContactManagement.php';
Application::init();
require_login();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

// For repopulating form after errors - get from URL parameters
$form = [];
$formFields = ['first_name', 'last_name', 'email', 'organization', 'phone_number'];
foreach ($formFields as $field) {
    if (isset($_GET[$field])) {
        $form[$field] = $_GET[$field];
    }
}

header_html('Add Contact');
?>

<h2>Add Contact</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <form method="post" action="/contacts/add_eval.php" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="redirect" value="/contacts/list.php">
    
    <h3>Contact Information</h3>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>First Name
        <input type="text" name="first_name" value="<?=h($form['first_name'] ?? '')?>" required>
      </label>
      <label>Last Name
        <input type="text" name="last_name" value="<?=h($form['last_name'] ?? '')?>" required>
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
      <button class="primary" type="submit">Create Contact</button>
      <a class="button" href="/contacts/list.php">Cancel</a>
    </div>
  </form>
</div>

<?php footer_html(); ?>
