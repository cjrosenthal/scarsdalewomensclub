<?php
require_once __DIR__ . '/../partials.php';
require_once __DIR__ . '/../lib/RoomManagement.php';
Application::init();
require_admin();

$msg = trim($_GET['msg'] ?? '');
$err = trim($_GET['err'] ?? '');

$rooms = RoomManagement::listAll();

header_html('Rooms');
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
  <h2>Rooms</h2>
  <a class="button" href="/admin/room_add.php">Add Room</a>
</div>

<?php if ($msg): ?><p class="flash"><?=h($msg)?></p><?php endif; ?>
<?php if ($err): ?><p class="error"><?=h($err)?></p><?php endif; ?>

<?php if (empty($rooms)): ?>
  <div class="card">
    <p class="small">No rooms found.</p>
  </div>
<?php else: ?>
  <div class="card">
    <table class="list">
      <thead>
        <tr>
          <th>Name</th>
          <th>Capacity</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $room): ?>
          <tr>
            <td><?= h($room['name'] ?? '') ?></td>
            <td><?= h($room['capacity'] ?? '0') ?></td>
            <td class="small">
              <a class="button small" href="/admin/room_edit.php?id=<?= (int)$room['id'] ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php footer_html(); ?>
