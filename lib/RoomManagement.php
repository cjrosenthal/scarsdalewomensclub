<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/UserContext.php';
require_once __DIR__ . '/ActivityLog.php';

class RoomManagement {
    private static function pdo(): PDO {
        return pdo();
    }

    private static function str(string $v): string {
        return trim($v);
    }

    // Activity logging - do not perform extra queries, just log what's provided.
    private static function log(UserContext $ctx, string $action, int $roomId, array $details = []): void {
        try {
            $meta = $details;
            $meta['room_id'] = $roomId;
            ActivityLog::log($ctx, $action, $meta);
        } catch (\Throwable $e) {
            // Best-effort logging; never disrupt the main flow.
        }
    }

    private static function assertAdmin(?UserContext $ctx): void {
        if (!$ctx || !$ctx->admin) { 
            throw new RuntimeException('Admins only'); 
        }
    }

    /**
     * List all rooms ordered by name
     */
    public static function listAll(): array {
        $st = self::pdo()->query('SELECT id, name, capacity, created_at FROM rooms ORDER BY name ASC');
        return $st->fetchAll() ?: [];
    }

    /**
     * Find room by ID
     */
    public static function findById(int $id): ?array {
        $st = self::pdo()->prepare('SELECT id, name, capacity, created_at FROM rooms WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Create a new room (admin only)
     */
    public static function create(UserContext $ctx, array $data): int {
        self::assertAdmin($ctx);
        
        $name = self::str($data['name'] ?? '');
        $capacity = (int)($data['capacity'] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('Room name is required.');
        }

        if ($capacity < 0) {
            throw new InvalidArgumentException('Capacity cannot be negative.');
        }

        $st = self::pdo()->prepare(
            "INSERT INTO rooms (name, capacity, created_at) VALUES (?, ?, NOW())"
        );
        $st->execute([$name, $capacity]);
        $id = (int)self::pdo()->lastInsertId();
        
        self::log($ctx, 'room.create', $id, ['name' => $name, 'capacity' => $capacity]);
        
        return $id;
    }

    /**
     * Update an existing room (admin only)
     */
    public static function update(UserContext $ctx, int $id, array $data): bool {
        self::assertAdmin($ctx);
        
        $name = self::str($data['name'] ?? '');
        $capacity = (int)($data['capacity'] ?? 0);

        if ($name === '') {
            throw new InvalidArgumentException('Room name is required.');
        }

        if ($capacity < 0) {
            throw new InvalidArgumentException('Capacity cannot be negative.');
        }

        $st = self::pdo()->prepare("UPDATE rooms SET name = ?, capacity = ? WHERE id = ?");
        $ok = $st->execute([$name, $capacity, $id]);
        
        if ($ok) {
            self::log($ctx, 'room.update', $id, ['name' => $name, 'capacity' => $capacity]);
        }
        
        return $ok;
    }

    /**
     * Delete a room (admin only)
     */
    public static function delete(UserContext $ctx, int $id): bool {
        self::assertAdmin($ctx);
        
        // Get room name for logging before deletion
        $room = self::findById($id);
        $roomName = $room ? $room['name'] : 'Unknown';
        
        $st = self::pdo()->prepare('DELETE FROM rooms WHERE id = ?');
        $ok = $st->execute([$id]);
        
        if ($ok) {
            self::log($ctx, 'room.delete', $id, ['name' => $roomName]);
        }
        
        return $ok;
    }
}
