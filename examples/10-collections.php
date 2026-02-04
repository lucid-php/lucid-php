<?php

declare(strict_types=1);

/**
 * Example 10: Collections
 * 
 * Demonstrates:
 * - Array manipulation with fluent interface
 * - Filtering and mapping
 * - Sorting and grouping
 * - Aggregations (sum, avg, max, min)
 * - Chaining operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Collection\Collection;

echo "Collections Examples:\n";
echo "====================\n\n";

// Sample data
$users = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30, 'salary' => 50000, 'department' => 'Engineering'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25, 'salary' => 60000, 'department' => 'Engineering'],
    ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => 35, 'salary' => 45000, 'department' => 'Sales'],
    ['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com', 'age' => 28, 'salary' => 55000, 'department' => 'Marketing'],
    ['id' => 5, 'name' => 'Charlie Wilson', 'email' => 'charlie@example.com', 'age' => 32, 'salary' => 70000, 'department' => 'Engineering'],
];

// ===========================
// Example 1: Basic Operations
// ===========================

echo "=== Example 1: Basic Operations ===\n\n";

$collection = new Collection($users);

echo "Count: " . $collection->count() . "\n";
echo "First: " . $collection->first()['name'] . "\n";
echo "Last: " . $collection->last()['name'] . "\n";
echo "Is empty: " . ($collection->isEmpty() ? 'Yes' : 'No') . "\n\n";

// ===========================
// Example 2: Filtering
// ===========================

echo "=== Example 2: Filtering ===\n\n";

// Filter by age
$youngUsers = $collection->filter(fn($user) => $user['age'] < 30);
echo "Users under 30:\n";
foreach ($youngUsers->toArray() as $user) {
    echo "  - {$user['name']} (age {$user['age']})\n";
}
echo "\n";

// Filter engineers
$engineers = $collection->filter(fn($user) => $user['department'] === 'Engineering');
echo "Engineers:\n";
foreach ($engineers->toArray() as $user) {
    echo "  - {$user['name']}\n";
}
echo "\n";

// ===========================
// Example 3: Mapping/Transforming
// ===========================

echo "=== Example 3: Mapping/Transforming ===\n\n";

// Extract just names
$names = $collection->map(fn($user) => $user['name']);
echo "All names: " . implode(', ', $names->toArray()) . "\n\n";

// Extract emails
$emails = $collection->pluck('email');
echo "All emails:\n";
foreach ($emails->toArray() as $email) {
    echo "  - {$email}\n";
}
echo "\n";

// Transform to summary
$summaries = $collection->map(function($user) {
    return "{$user['name']} ({$user['department']}) - \${$user['salary']}";
});

echo "User summaries:\n";
foreach ($summaries->toArray() as $summary) {
    echo "  - {$summary}\n";
}
echo "\n";

// ===========================
// Example 4: Sorting
// ===========================

echo "=== Example 4: Sorting ===\n\n";

// Sort by age using custom callback
$sortedByAge = $collection->sort(fn($a, $b) => $a['age'] <=> $b['age']);
echo "Sorted by age:\n";
foreach ($sortedByAge->toArray() as $user) {
    echo "  - {$user['name']}: {$user['age']} years old\n";
}
echo "\n";

// Sort by salary (descending)
$sortedBySalary = $collection->sort(fn($a, $b) => $b['salary'] <=> $a['salary']);
echo "Sorted by salary (high to low):\n";
foreach ($sortedBySalary->toArray() as $user) {
    echo "  - {$user['name']}: \${$user['salary']}\n";
}
echo "\n";

// ===========================
// Example 5: Grouping
// ===========================

echo "=== Example 5: Grouping ===\n\n";

// Group by department
$byDepartment = $collection->groupBy('department');
echo "Users by department:\n";
foreach ($byDepartment->toArray() as $dept => $deptUsers) {
    echo "  {$dept}:\n";
    foreach ($deptUsers as $user) {
        echo "    - {$user['name']}\n";
    }
}
echo "\n";

// ===========================
// Example 6: Aggregations
// ===========================

echo "=== Example 6: Aggregations ===\n\n";

$totalSalary = $collection->sum('salary');
$avgSalary = $collection->avg('salary');
$ages = $collection->pluck('age');
$maxAge = $ages->max();
$minAge = $ages->min();

echo "Statistics:\n";
echo "  Total Salary: \$" . number_format($totalSalary, 2) . "\n";
echo "  Average Salary: \$" . number_format($avgSalary, 2) . "\n";
echo "  Oldest: {$maxAge} years\n";
echo "  Youngest: {$minAge} years\n\n";

// ===========================
// Example 7: Chaining Operations
// ===========================

echo "=== Example 7: Chaining Operations ===\n\n";

// Find high-earning engineers
$topEngineers = $collection
    ->filter(fn($user) => $user['department'] === 'Engineering')
    ->filter(fn($user) => $user['salary'] > 55000)
    ->sort(fn($a, $b) => $b['salary'] <=> $a['salary'])
    ->map(fn($user) => "{$user['name']}: \${$user['salary']}");

echo "Top-paid engineers (>55k):\n";
foreach ($topEngineers->toArray() as $engineer) {
    echo "  - {$engineer}\n";
}
echo "\n";

// ===========================
// Example 8: Unique Values
// ===========================

echo "=== Example 8: Unique Values ===\n\n";

$departments = $collection->pluck('department')->unique();
echo "Departments: " . implode(', ', $departments->toArray()) . "\n\n";

// ===========================
// Example 9: Chunking
// ===========================

echo "=== Example 9: Chunking ===\n\n";

$chunks = $collection->chunk(2);
echo "Users in chunks of 2:\n";
$chunkNum = 1;
foreach ($chunks->toArray() as $chunk) {
    echo "  Chunk {$chunkNum}:\n";
    foreach ($chunk as $user) {
        echo "    - {$user['name']}\n";
    }
    $chunkNum++;
}
echo "\n";

// ===========================
// Example 10: Take & Skip
// ===========================

echo "=== Example 10: Take & Skip ===\n\n";

$firstThree = $collection->take(3);
echo "First 3 users:\n";
foreach ($firstThree->toArray() as $user) {
    echo "  - {$user['name']}\n";
}
echo "\n";

$skipTwo = $collection->skip(2);
echo "After skipping 2:\n";
foreach ($skipTwo->toArray() as $user) {
    echo "  - {$user['name']}\n";
}
echo "\n";

// ===========================
// Example 11: Checking Conditions
// ===========================

echo "=== Example 11: Checking Conditions ===\n\n";

$hasHighEarner = $collection->some(fn($user) => $user['salary'] > 65000);
echo "Has user earning >65k: " . ($hasHighEarner ? 'Yes' : 'No') . "\n";

$allAdults = $collection->every(fn($user) => $user['age'] >= 18);
echo "All users are adults: " . ($allAdults ? 'Yes' : 'No') . "\n";

$hasEngineers = $collection->some(fn($user) => $user['department'] === 'Engineering');
echo "Has engineers: " . ($hasEngineers ? 'Yes' : 'No') . "\n\n";

// ===========================
// Example 12: Real-World Use Cases
// ===========================

echo "=== Example 12: Real-World Use Cases ===\n\n";

// Use case 1: Calculate department statistics
echo "Department Statistics:\n";
$deptStats = $collection
    ->groupBy('department')
    ->map(function($deptUsers, $dept) {
        $users = new Collection($deptUsers);
        return [
            'department' => $dept,
            'count' => $users->count(),
            'avg_salary' => $users->avg('salary'),
            'total_salary' => $users->sum('salary')
        ];
    });

foreach ($deptStats->toArray() as $stat) {
    echo "  {$stat['department']}:\n";
    echo "    Employees: {$stat['count']}\n";
    echo "    Avg Salary: \$" . number_format($stat['avg_salary'], 2) . "\n";
    echo "    Total Salary: \$" . number_format($stat['total_salary'], 2) . "\n";
}
echo "\n";

// Use case 2: Generate email list for a department
echo "Email list for Engineering:\n";
$engineeringEmails = $collection
    ->filter(fn($user) => $user['department'] === 'Engineering')
    ->pluck('email')
    ->join(', ');
echo "  {$engineeringEmails}\n\n";

// Use case 3: Format for API response
echo "API Response Format:\n";
$apiResponse = $collection
    ->map(fn($user) => [
        'id' => $user['id'],
        'name' => $user['name'],
        'department' => $user['department']
    ])
    ->values(); // Reset array keys

echo json_encode($apiResponse->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// ===========================
// Best Practices
// ===========================

echo "=== Best Practices ===\n\n";

echo "1. Chain operations for readability\n";
echo "   ✓ \$users->filter(...)->map(...)->sort(...)\n";
echo "   ✗ Multiple intermediate variables\n\n";

echo "2. Use descriptive callbacks\n";
echo "   ✓ fn(\$user) => \$user['age'] > 18\n";
echo "   ✗ fn(\$u) => \$u['a'] > 18\n\n";

echo "3. Know when to use collections\n";
echo "   ✓ Complex array operations\n";
echo "   ✓ Multiple transformations\n";
echo "   ✗ Simple single operations\n\n";

echo "4. Consider performance\n";
echo "   ✓ Filter before map for efficiency\n";
echo "   ✓ Use pluck() for extracting single field\n";
echo "   ✗ Chain too many operations on large datasets\n";
