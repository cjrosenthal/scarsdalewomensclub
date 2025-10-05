<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/UserContext.php';
require_once __DIR__ . '/ActivityLog.php';

class LeadManagement {
    private static function pdo(): PDO {
        return pdo();
    }

    private static function str(string $v): string {
        return trim($v);
    }

    // Activity logging
    private static function log(UserContext $ctx, string $action, int $leadId, array $details = []): void {
        try {
            $meta = $details;
            $meta['lead_id'] = $leadId;
            ActivityLog::log($ctx, $action, $meta);
        } catch (\Throwable $e) {
            // Best-effort logging; never disrupt the main flow.
        }
    }

    /**
     * Get total count of leads with optional status and keyword filter
     */
    public static function getTotalCount(string $status = 'active', ?string $keyword = null): int {
        $keyword = $keyword !== null ? trim($keyword) : null;
        
        if ($keyword === null || $keyword === '') {
            $st = self::pdo()->prepare('SELECT COUNT(*) as cnt FROM leads WHERE status = ?');
            $st->execute([$status]);
            $row = $st->fetch();
            return (int)($row['cnt'] ?? 0);
        }

        // Tokenize keyword and build search conditions
        $tokens = array_filter(array_map('trim', explode(' ', $keyword)));
        if (empty($tokens)) {
            $st = self::pdo()->prepare('SELECT COUNT(*) as cnt FROM leads WHERE status = ?');
            $st->execute([$status]);
            $row = $st->fetch();
            return (int)($row['cnt'] ?? 0);
        }

        // Search across lead fields and related contact fields
        $conditions = [];
        $params = [$status];
        foreach ($tokens as $token) {
            $likeToken = '%' . $token . '%';
            $conditions[] = '(l.channel LIKE ? OR l.party_type LIKE ? OR l.description LIKE ? OR 
                             c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR 
                             c.organization LIKE ? OR c.phone_number LIKE ?)';
            for ($i = 0; $i < 8; $i++) {
                $params[] = $likeToken;
            }
        }

        $sql = 'SELECT COUNT(DISTINCT l.id) as cnt 
                FROM leads l
                INNER JOIN contacts c ON l.main_contact_id = c.id
                WHERE l.status = ? AND ' . implode(' AND ', $conditions);
        
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * List all leads with pagination and status filter
     */
    public static function listAll(string $status = 'active', int $page = 1, int $pageSize = 25): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $st = self::pdo()->prepare(
            'SELECT l.id, l.main_contact_id, l.channel, l.created_at, l.party_type, 
                    l.number_of_people, l.description, l.status,
                    c.first_name, c.last_name, c.email, c.organization, c.phone_number
             FROM leads l
             INNER JOIN contacts c ON l.main_contact_id = c.id
             WHERE l.status = ?
             ORDER BY l.created_at DESC 
             LIMIT ? OFFSET ?'
        );
        $st->execute([$status, $pageSize, $offset]);
        return $st->fetchAll() ?: [];
    }

    /**
     * Search leads by keyword with pagination and status filter
     */
    public static function search(string $status = 'active', string $keyword = '', int $page = 1, int $pageSize = 25): array {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return self::listAll($status, $page, $pageSize);
        }

        // Tokenize keyword
        $tokens = array_filter(array_map('trim', explode(' ', $keyword)));
        if (empty($tokens)) {
            return self::listAll($status, $page, $pageSize);
        }

        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        // Build search conditions - each token must match at least one field
        $conditions = [];
        $params = [$status];
        foreach ($tokens as $token) {
            $likeToken = '%' . $token . '%';
            $conditions[] = '(l.channel LIKE ? OR l.party_type LIKE ? OR l.description LIKE ? OR 
                             c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR 
                             c.organization LIKE ? OR c.phone_number LIKE ?)';
            for ($i = 0; $i < 8; $i++) {
                $params[] = $likeToken;
            }
        }

        $sql = 'SELECT DISTINCT l.id, l.main_contact_id, l.channel, l.created_at, l.party_type, 
                       l.number_of_people, l.description, l.status,
                       c.first_name, c.last_name, c.email, c.organization, c.phone_number
                FROM leads l
                INNER JOIN contacts c ON l.main_contact_id = c.id
                WHERE l.status = ? AND ' . implode(' AND ', $conditions) . '
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?';
        
        $params[] = $pageSize;
        $params[] = $offset;

        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll() ?: [];
    }

    /**
     * Find lead by ID with main contact info
     */
    public static function findById(int $id): ?array {
        $st = self::pdo()->prepare(
            'SELECT l.id, l.main_contact_id, l.channel, l.created_at, l.party_type, 
                    l.number_of_people, l.description, l.tour_scheduled, l.status,
                    c.first_name, c.last_name, c.email, c.organization, c.phone_number
             FROM leads l
             INNER JOIN contacts c ON l.main_contact_id = c.id
             WHERE l.id = ? 
             LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Get secondary contacts for a lead
     */
    public static function getSecondaryContacts(int $leadId): array {
        $st = self::pdo()->prepare(
            'SELECT c.id, c.first_name, c.last_name, c.email, c.organization, c.phone_number
             FROM lead_secondary_contacts lsc
             INNER JOIN contacts c ON lsc.contact_id = c.id
             WHERE lsc.lead_id = ?
             ORDER BY c.last_name ASC, c.first_name ASC'
        );
        $st->execute([$leadId]);
        return $st->fetchAll() ?: [];
    }

    /**
     * Create a new lead
     */
    public static function create(UserContext $ctx, array $data): int {
        $mainContactId = (int)($data['main_contact_id'] ?? 0);
        $channel = self::str($data['channel'] ?? '');
        $partyType = self::str($data['party_type'] ?? '');
        $numberOfPeople = isset($data['number_of_people']) && $data['number_of_people'] !== '' 
            ? (int)$data['number_of_people'] 
            : null;
        $description = self::str($data['description'] ?? '');
        $tourScheduled = !empty($data['tour_scheduled']);
        $status = self::str($data['status'] ?? 'active');

        if ($mainContactId <= 0) {
            throw new InvalidArgumentException('Main contact is required.');
        }

        // Verify contact exists
        $contactCheck = self::pdo()->prepare('SELECT id FROM contacts WHERE id = ? LIMIT 1');
        $contactCheck->execute([$mainContactId]);
        if (!$contactCheck->fetch()) {
            throw new InvalidArgumentException('Main contact does not exist.');
        }

        $st = self::pdo()->prepare(
            "INSERT INTO leads (main_contact_id, channel, party_type, number_of_people, description, tour_scheduled, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $st->execute([
            $mainContactId,
            $channel !== '' ? $channel : null,
            $partyType !== '' ? $partyType : null,
            $numberOfPeople,
            $description !== '' ? $description : null,
            $tourScheduled ? 1 : 0,
            $status
        ]);
        $id = (int)self::pdo()->lastInsertId();
        
        self::log($ctx, 'lead.create', $id, [
            'main_contact_id' => $mainContactId,
            'channel' => $channel,
            'party_type' => $partyType,
            'number_of_people' => $numberOfPeople,
            'tour_scheduled' => $tourScheduled,
            'status' => $status
        ]);
        
        return $id;
    }

    /**
     * Update an existing lead
     */
    public static function update(UserContext $ctx, int $id, array $data): bool {
        $mainContactId = (int)($data['main_contact_id'] ?? 0);
        $channel = self::str($data['channel'] ?? '');
        $partyType = self::str($data['party_type'] ?? '');
        $numberOfPeople = isset($data['number_of_people']) && $data['number_of_people'] !== '' 
            ? (int)$data['number_of_people'] 
            : null;
        $description = self::str($data['description'] ?? '');
        $tourScheduled = !empty($data['tour_scheduled']);
        $status = self::str($data['status'] ?? 'active');

        if ($mainContactId <= 0) {
            throw new InvalidArgumentException('Main contact is required.');
        }

        // Verify contact exists
        $contactCheck = self::pdo()->prepare('SELECT id FROM contacts WHERE id = ? LIMIT 1');
        $contactCheck->execute([$mainContactId]);
        if (!$contactCheck->fetch()) {
            throw new InvalidArgumentException('Main contact does not exist.');
        }

        $st = self::pdo()->prepare(
            "UPDATE leads 
             SET main_contact_id = ?, channel = ?, party_type = ?, number_of_people = ?, 
                 description = ?, tour_scheduled = ?, status = ? 
             WHERE id = ?"
        );
        $ok = $st->execute([
            $mainContactId,
            $channel !== '' ? $channel : null,
            $partyType !== '' ? $partyType : null,
            $numberOfPeople,
            $description !== '' ? $description : null,
            $tourScheduled ? 1 : 0,
            $status,
            $id
        ]);
        
        if ($ok) {
            self::log($ctx, 'lead.update', $id, [
                'main_contact_id' => $mainContactId,
                'channel' => $channel,
                'party_type' => $partyType,
                'number_of_people' => $numberOfPeople,
                'tour_scheduled' => $tourScheduled,
                'status' => $status
            ]);
        }
        
        return $ok;
    }

    /**
     * Delete a lead (soft delete - sets status to 'deleted')
     */
    public static function delete(UserContext $ctx, int $id): bool {
        // Get lead info for logging before deletion
        $lead = self::findById($id);
        $leadInfo = $lead ? [
            'main_contact' => $lead['first_name'] . ' ' . $lead['last_name'],
            'channel' => $lead['channel'],
            'party_type' => $lead['party_type']
        ] : [];
        
        $st = self::pdo()->prepare("UPDATE leads SET status = 'deleted' WHERE id = ?");
        $ok = $st->execute([$id]);
        
        if ($ok) {
            self::log($ctx, 'lead.delete', $id, $leadInfo);
        }
        
        return $ok;
    }

    /**
     * Count how many leads use a specific contact as their primary contact
     */
    public static function countLeadsUsingContact(int $contactId): int {
        $st = self::pdo()->prepare('SELECT COUNT(*) as cnt FROM leads WHERE main_contact_id = ?');
        $st->execute([$contactId]);
        $row = $st->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Add a secondary contact to a lead
     */
    public static function addSecondaryContact(UserContext $ctx, int $leadId, int $contactId): bool {
        // Verify lead exists and get its primary contact
        $leadCheck = self::pdo()->prepare('SELECT id, main_contact_id FROM leads WHERE id = ? LIMIT 1');
        $leadCheck->execute([$leadId]);
        $lead = $leadCheck->fetch();
        if (!$lead) {
            throw new InvalidArgumentException('Lead does not exist.');
        }

        // Check if trying to add primary contact as secondary
        if ($lead['main_contact_id'] == $contactId) {
            throw new InvalidArgumentException('Cannot add the primary contact as a secondary contact.');
        }

        // Verify contact exists
        $contactCheck = self::pdo()->prepare('SELECT id FROM contacts WHERE id = ? LIMIT 1');
        $contactCheck->execute([$contactId]);
        if (!$contactCheck->fetch()) {
            throw new InvalidArgumentException('Contact does not exist.');
        }

        try {
            $st = self::pdo()->prepare(
                "INSERT INTO lead_secondary_contacts (lead_id, contact_id, created_at) 
                 VALUES (?, ?, NOW())"
            );
            $ok = $st->execute([$leadId, $contactId]);
            
            if ($ok) {
                self::log($ctx, 'lead.add_secondary_contact', $leadId, ['contact_id' => $contactId]);
            }
            
            return $ok;
        } catch (\PDOException $e) {
            // Check if it's a duplicate key error
            if ($e->getCode() === '23000') {
                throw new InvalidArgumentException('This contact is already linked to this lead.');
            }
            throw $e;
        }
    }

    /**
     * Remove a secondary contact from a lead
     */
    public static function removeSecondaryContact(UserContext $ctx, int $leadId, int $contactId): bool {
        $st = self::pdo()->prepare(
            'DELETE FROM lead_secondary_contacts WHERE lead_id = ? AND contact_id = ?'
        );
        $ok = $st->execute([$leadId, $contactId]);
        
        if ($ok) {
            self::log($ctx, 'lead.remove_secondary_contact', $leadId, ['contact_id' => $contactId]);
        }
        
        return $ok;
    }

    /**
     * Get all comments for a lead with user information
     */
    public static function getComments(int $leadId): array {
        $st = self::pdo()->prepare(
            'SELECT lc.id, lc.lead_id, lc.comment_text, lc.created_at,
                    lc.created_by_user_id, u.first_name, u.last_name
             FROM lead_comments lc
             LEFT JOIN users u ON lc.created_by_user_id = u.id
             WHERE lc.lead_id = ?
             ORDER BY lc.created_at DESC'
        );
        $st->execute([$leadId]);
        return $st->fetchAll() ?: [];
    }

    /**
     * Add a comment to a lead
     */
    public static function addComment(UserContext $ctx, int $leadId, string $commentText): int {
        $commentText = self::str($commentText);
        
        if ($commentText === '') {
            throw new InvalidArgumentException('Comment text is required.');
        }

        // Verify lead exists
        $leadCheck = self::pdo()->prepare('SELECT id FROM leads WHERE id = ? LIMIT 1');
        $leadCheck->execute([$leadId]);
        if (!$leadCheck->fetch()) {
            throw new InvalidArgumentException('Lead does not exist.');
        }

        $st = self::pdo()->prepare(
            "INSERT INTO lead_comments (lead_id, comment_text, created_by_user_id, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $st->execute([
            $leadId,
            $commentText,
            $ctx->id
        ]);
        $id = (int)self::pdo()->lastInsertId();
        
        self::log($ctx, 'lead.add_comment', $leadId, [
            'comment_id' => $id
        ]);
        
        return $id;
    }
}
