<?php

declare(strict_types=1);

/**
 * Example 4: Database & Repository Pattern
 * 
 * Demonstrates:
 * - Raw SQL queries
 * - Repository pattern
 * - Transactions
 * - Query builder helpers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Database\Database;

// Example: User Repository
class UserRepository
{
    public function __construct(private Database $db) {}
    
    public function findAll(): array
    {
        return $this->db->query('SELECT * FROM users ORDER BY created_at DESC');
    }
    
    public function findById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM users WHERE id = :id',
            ['id' => $id]
        );
        
        return $result[0] ?? null;
    }
    
    public function findByEmail(string $email): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM users WHERE email = :email',
            ['email' => $email]
        );
        
        return $result[0] ?? null;
    }
    
    public function create(string $name, string $email, string $password): array
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $this->db->execute(
            'INSERT INTO users (name, email, password, created_at) 
             VALUES (:name, :email, :password, NOW())',
            [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword
            ]
        );
        
        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }
    
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $fieldsStr = implode(', ', $fields);
        
        return $this->db->execute(
            "UPDATE users SET $fieldsStr WHERE id = :id",
            $params
        );
    }
    
    public function delete(int $id): bool
    {
        return $this->db->execute(
            'DELETE FROM users WHERE id = :id',
            ['id' => $id]
        );
    }
    
    public function searchByName(string $search): array
    {
        return $this->db->query(
            'SELECT * FROM users WHERE name LIKE :search ORDER BY name',
            ['search' => "%$search%"]
        );
    }
}

// Example: Product Repository (for transaction demo)
class ProductRepository
{
    public function __construct(private Database $db) {}
    
    public function findById(int $id): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM products WHERE id = :id',
            ['id' => $id]
        );
        
        return $result[0] ?? null;
    }
}

// Example: Using Transactions
class OrderService
{
    public function __construct(
        private Database $db,
        private ProductRepository $products
    ) {}
    
    public function createOrder(int $userId, array $items): array
    {
        return $this->db->transaction(function() use ($userId, $items) {
            // Create order
            $this->db->execute(
                'INSERT INTO orders (user_id, total, status, created_at) 
                 VALUES (:user_id, 0, :status, NOW())',
                ['user_id' => $userId, 'status' => 'pending']
            );
            
            $orderId = (int) $this->db->lastInsertId();
            $total = 0;
            
            // Add order items and update inventory
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                
                // Get product price
                $product = $this->products->findById($productId);
                if (!$product) {
                    throw new \Exception("Product $productId not found");
                }
                
                $price = $product['price'];
                $subtotal = $price * $quantity;
                $total += $subtotal;
                
                // Insert order item
                $this->db->execute(
                    'INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
                     VALUES (:order_id, :product_id, :quantity, :price, :subtotal)',
                    [
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal
                    ]
                );
                
                // Update product inventory
                $this->db->execute(
                    'UPDATE products SET stock = stock - :quantity WHERE id = :id',
                    ['quantity' => $quantity, 'id' => $productId]
                );
            }
            
            // Update order total
            $this->db->execute(
                'UPDATE orders SET total = :total WHERE id = :id',
                ['total' => $total, 'id' => $orderId]
            );
            
            return ['order_id' => $orderId, 'total' => $total];
        });
    }
}

echo "Database & Repository Examples:\n";
echo "===============================\n\n";

echo "1. Basic Repository Pattern:\n";
echo "   - findAll() - Get all records\n";
echo "   - findById(\$id) - Get single record\n";
echo "   - create(\$data) - Insert new record\n";
echo "   - update(\$id, \$data) - Update record\n";
echo "   - delete(\$id) - Delete record\n\n";

echo "2. Raw SQL Queries:\n";
echo "   \$stmt = \$db->query('SELECT * FROM users WHERE id = :id', ['id' => 1]);\n";
echo "   \$user = \$stmt->fetch();\n\n";

echo "3. Transactions:\n";
echo "   \$result = \$db->transaction(function() use (\$db) {\n";
echo "       \$db->query('INSERT ...');\n";
echo "       \$db->query('UPDATE ...');\n";
echo "       return \$data;\n";
echo "   });\n\n";

echo "4. Example Usage:\n";
echo "   \$userRepo = new UserRepository(\$db);\n";
echo "   \$users = \$userRepo->findAll();\n";
echo "   \$user = \$userRepo->findById(1);\n";
echo "   \$user = \$userRepo->create('John', 'john@example.com', 'password');\n";
echo "   \$userRepo->update(1, ['name' => 'Jane']);\n";
echo "   \$userRepo->delete(1);\n\n";

echo "5. Complex Transaction Example:\n";
echo "   The OrderService->createOrder() shows how to:\n";
echo "   - Create an order\n";
echo "   - Add order items\n";
echo "   - Update product inventory\n";
echo "   - All within a single transaction\n";
echo "   - Automatic rollback if any query fails\n";
