<?php

declare(strict_types=1);

namespace App\Repository;

use Core\Database\AbstractRepository;
use App\Entity\User;

class TokenRepository extends AbstractRepository
{
    public function createToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        
        $this->db->execute(
            "INSERT INTO personal_access_tokens (user_id, token) VALUES (:user_id, :token)",
            [
                'user_id' => $userId,
                'token' => $token
            ]
        );

        return $token;
    }

    public function findUserByToken(string $token): ?User
    {
        $sql = "
            SELECT u.* 
            FROM users u
            JOIN personal_access_tokens t ON t.user_id = u.id
            WHERE t.token = :token
        ";

        $rows = $this->db->query($sql, ['token' => $token]);

        // PHP 8.5: array_first() returns first element or null if array is empty
        $row = array_first($rows);

        if ($row === null) {
            return null;
        }

        return new User(
            id: (int) $row['id'],
            name: $row['name'],
            email: $row['email'],
            password: $row['password'],
            created_at: $row['created_at']
        );
    }
}
