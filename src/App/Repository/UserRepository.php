<?php

declare(strict_types=1);

namespace App\Repository;

use Core\Database\AbstractRepository;
use App\Entity\User;

class UserRepository extends AbstractRepository
{
    public function create(string $name, string $email, string $password): User
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
        
        $this->db->execute($sql, [
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword,
        ]);
        
        $id = (int) $this->db->lastInsertId();
        
        return new User(
            id: $id,
            name: $name,
            email: $email,
            password: $hashedPassword
        );
    }

    /**
     * @return User[]
     */
    public function findAll(): array
    {
        $rows = $this->db->query("SELECT * FROM users");
        
        return array_map(fn($row) => new User(
            id: (int) $row['id'],
            name: $row['name'],
            email: $row['email'],
            password: $row['password'],
            created_at: $row['created_at']
        ), $rows);
    }

    public function findByEmail(string $email): ?User
    {
        $rows = $this->db->query("SELECT * FROM users WHERE email = :email LIMIT 1", ['email' => $email]);

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
