<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/UserContext.php';
require_once __DIR__ . '/ActivityLog.php';

class ContactManagement {
    private static function pdo(): PDO {
        return pdo();
    }

    private static function str(string $v): string {
        return trim($v);
    }

    // Activity logging - do not perform extra queries, just log what's provided.
    private static function log(UserContext $ctx, string $action, int $contactId, array $details = []): void {
        try {
            $meta = $details;
            $meta['contact_id'] = $contactId;
            ActivityLog::log($ctx, $action, $meta);
        } catch (\Throwable $e) {
            // Best-effort logging; never disrupt the main flow.
        }
    }


    /**
     * Get total count of contacts (with optional keyword filter)
     */
    public static function getTotalCount(?string $keyword = null): int {
        if ($keyword === null || trim($keyword) === '') {
            $st = self::pdo()->query('SELECT COUNT(*) as cnt FROM contacts');
            $row = $st->fetch();
            return (int)($row['cnt'] ?? 0);
        }

        // Tokenize keyword and build search conditions
        $tokens = array_filter(array_map('trim', explode(' ', $keyword)));
        if (empty($tokens)) {
            $st = self::pdo()->query('SELECT COUNT(*) as cnt FROM contacts');
            $row = $st->fetch();
            return (int)($row['cnt'] ?? 0);
        }

        $conditions = [];
        $params = [];
        foreach ($tokens as $token) {
            $likeToken = '%' . $token . '%';
            $conditions[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR organization LIKE ? OR phone_number LIKE ?)';
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
        }

        $sql = 'SELECT COUNT(*) as cnt FROM contacts WHERE ' . implode(' AND ', $conditions);
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * List all contacts with pagination
     */
    public static function listAll(int $page = 1, int $pageSize = 25): array {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize)); // Cap at 100
        $offset = ($page - 1) * $pageSize;

        $st = self::pdo()->prepare(
            'SELECT id, first_name, last_name, email, organization, phone_number, created_at 
             FROM contacts 
             ORDER BY last_name ASC, first_name ASC 
             LIMIT ? OFFSET ?'
        );
        $st->execute([$pageSize, $offset]);
        return $st->fetchAll() ?: [];
    }

    /**
     * Search contacts by keyword with pagination
     */
    public static function search(string $keyword, int $page = 1, int $pageSize = 25): array {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return self::listAll($page, $pageSize);
        }

        // Tokenize keyword
        $tokens = array_filter(array_map('trim', explode(' ', $keyword)));
        if (empty($tokens)) {
            return self::listAll($page, $pageSize);
        }

        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize)); // Cap at 100
        $offset = ($page - 1) * $pageSize;

        // Build search conditions - each token must match at least one field
        $conditions = [];
        $params = [];
        foreach ($tokens as $token) {
            $likeToken = '%' . $token . '%';
            $conditions[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR organization LIKE ? OR phone_number LIKE ?)';
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
            $params[] = $likeToken;
        }

        $sql = 'SELECT id, first_name, last_name, email, organization, phone_number, created_at 
                FROM contacts 
                WHERE ' . implode(' AND ', $conditions) . ' 
                ORDER BY last_name ASC, first_name ASC 
                LIMIT ? OFFSET ?';
        
        $params[] = $pageSize;
        $params[] = $offset;

        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll() ?: [];
    }

    /**
     * Find contact by ID
     */
    public static function findById(int $id): ?array {
        $st = self::pdo()->prepare(
            'SELECT id, first_name, last_name, email, organization, phone_number, created_at 
             FROM contacts 
             WHERE id = ? 
             LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Create a new contact
     */
    public static function create(UserContext $ctx, array $data): int {
        
        $firstName = self::str($data['first_name'] ?? '');
        $lastName = self::str($data['last_name'] ?? '');
        $email = self::str($data['email'] ?? '');
        $organization = self::str($data['organization'] ?? '');
        $phoneNumber = self::str($data['phone_number'] ?? '');

        if ($firstName === '') {
            throw new InvalidArgumentException('First name is required.');
        }

        if ($lastName === '') {
            throw new InvalidArgumentException('Last name is required.');
        }

        $st = self::pdo()->prepare(
            "INSERT INTO contacts (first_name, last_name, email, organization, phone_number, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $st->execute([
            $firstName,
            $lastName,
            $email !== '' ? $email : null,
            $organization !== '' ? $organization : null,
            $phoneNumber !== '' ? $phoneNumber : null
        ]);
        $id = (int)self::pdo()->lastInsertId();
        
        self::log($ctx, 'contact.create', $id, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'organization' => $organization,
            'phone_number' => $phoneNumber
        ]);
        
        return $id;
    }

    /**
     * Update an existing contact
     */
    public static function update(UserContext $ctx, int $id, array $data): bool {
        
        $firstName = self::str($data['first_name'] ?? '');
        $lastName = self::str($data['last_name'] ?? '');
        $email = self::str($data['email'] ?? '');
        $organization = self::str($data['organization'] ?? '');
        $phoneNumber = self::str($data['phone_number'] ?? '');

        if ($firstName === '') {
            throw new InvalidArgumentException('First name is required.');
        }

        if ($lastName === '') {
            throw new InvalidArgumentException('Last name is required.');
        }

        $st = self::pdo()->prepare(
            "UPDATE contacts 
             SET first_name = ?, last_name = ?, email = ?, organization = ?, phone_number = ? 
             WHERE id = ?"
        );
        $ok = $st->execute([
            $firstName,
            $lastName,
            $email !== '' ? $email : null,
            $organization !== '' ? $organization : null,
            $phoneNumber !== '' ? $phoneNumber : null,
            $id
        ]);
        
        if ($ok) {
            self::log($ctx, 'contact.update', $id, [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'organization' => $organization,
                'phone_number' => $phoneNumber
            ]);
        }
        
        return $ok;
    }

    /**
     * Delete a contact
     */
    public static function delete(UserContext $ctx, int $id): bool {
        
        // Get contact name for logging before deletion
        $contact = self::findById($id);
        $contactName = $contact ? ($contact['first_name'] . ' ' . $contact['last_name']) : 'Unknown';
        
        $st = self::pdo()->prepare('DELETE FROM contacts WHERE id = ?');
        $ok = $st->execute([$id]);
        
        if ($ok) {
            self::log($ctx, 'contact.delete', $id, ['name' => $contactName]);
        }
        
        return $ok;
    }

    /**
     * Find potential duplicate contacts by email or phone number
     * Returns array of contacts with match_type indicator
     */
    public static function findPotentialDuplicates(string $email = '', string $phoneNumber = ''): array {
        $email = self::str($email);
        $phoneNumber = self::str($phoneNumber);
        
        // If both are empty, return nothing
        if ($email === '' && $phoneNumber === '') {
            return [];
        }
        
        $conditions = [];
        $params = [];
        
        if ($email !== '') {
            $conditions[] = 'email = ?';
            $params[] = $email;
        }
        
        if ($phoneNumber !== '') {
            $conditions[] = 'phone_number = ?';
            $params[] = $phoneNumber;
        }
        
        $sql = 'SELECT id, first_name, last_name, email, organization, phone_number 
                FROM contacts 
                WHERE ' . implode(' OR ', $conditions);
        
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $contacts = $st->fetchAll() ?: [];
        
        // Add match_type to each contact
        foreach ($contacts as &$contact) {
            $matchTypes = [];
            if ($email !== '' && $contact['email'] === $email) {
                $matchTypes[] = 'email';
            }
            if ($phoneNumber !== '' && $contact['phone_number'] === $phoneNumber) {
                $matchTypes[] = 'phone';
            }
            $contact['match_type'] = implode(',', $matchTypes);
        }
        
        return $contacts;
    }
}
