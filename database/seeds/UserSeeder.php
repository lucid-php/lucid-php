<?php

declare(strict_types=1);

namespace Database\Seeds;

use Core\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Check if user exists to avoid duplicate entry errors
        $exists = $this->db->query("SELECT id FROM users WHERE email = :email", ['email' => 'admin@example.com']);

        if (empty($exists)) {
            $this->db->execute(
                "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)",
                [
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'password' => password_hash('secret123', PASSWORD_BCRYPT) 
                ]
            );
            echo "Seeded Admin User.\n";
        } else {
            echo "Admin User already exists.\n";
        }
    }
}
