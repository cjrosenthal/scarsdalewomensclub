<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/RoomManagement.php';
Application::init();
require_admin();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /admin/rooms.php?err=' . urlencode('Invalid room ID.'));
    exit;
}

$room = RoomManagement::findById($id);
if (!$room) {
    header('Location: /admin/rooms.php?err=' . urlencode('Room not found.'));
    exit;
}

// For repopulating form after errors - prioritize URL parameters over DB data
$form = [
    'name' => $_GET['name'] ?? $room['name'],
    'capacity' => $_GET['capacity'] ?? $room['capacity']
];

header_html('Edit Room');
?>

<h2>Edit Room</h2>
<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<div class="card">
  <form method="post" action="/admin/room_edit_eval.php" class="stack">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/admin/rooms.php">
    
    <h3>Room Information</h3>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
      <label>Name
        <input type="text" name="name" value="<?=h($form['name'])?>" required>
      </label>
      <label>Capacity
        <input type="number" name="capacity" value="<?=h($form['capacity'])?>" min="0" required>
      </label>
    </div>

    <div class="actions">
      <button class="primary" type="submit">Update Room</button>
      <a class="button" href="/admin/rooms.php">Cancel</a>
    </div>
  </form>
</div>

<div class="card">
  <h3>Delete Room</h3>
  <p>Permanently delete this room. This action cannot be undone.</p>
  <form method="post" action="/admin/room_delete_eval.php" onsubmit="return confirm('Are you sure you want to delete this room? This action cannot be undone.');">
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <input type="hidden" name="id" value="<?=h($id)?>">
    <input type="hidden" name="redirect" value="/admin/rooms.php">
    <button type="submit" class="button danger">Delete Room</button>
  </form>
</div>

<?php footer_html(); ?>
