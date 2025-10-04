<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/RoomManagement.php';
Application::init();
require_admin();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

// For repopulating form after errors - get from URL parameters
$form = [];
$formFields = ['name', 'capacity'];
foreach ($formFields as $field) {
    if (isset($_GET[$field])) {
        $form[$field] = $_GET[$field];
    }
}

header_html('Add Room');
?>

<h2>Add Room</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <form method="post" action="/rooms/add_eval.php" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="redirect" value="/rooms/list.php">
    
    <h3>Room Information</h3>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Name
        <input type="text" name="name" value="<?=h($form['name'] ?? '')?>" required>
      </label>
      <label>Capacity
        <input type="number" name="capacity" value="<?=h($form['capacity'] ?? '')?>" min="0" required>
      </label>
    </div>

    <div class="actions">
      <button class="primary" type="submit">Create Room</button>
      <a class="button" href="/rooms/list.php">Cancel</a>
    </div>
  </form>
</div>

<?php footer_html(); ?>
