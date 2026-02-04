<?php

declare(strict_types=1);

/**
 * Example 1: Routing Basics
 * 
 * Demonstrates:
 * - Attribute-based routing
 * - HTTP methods (GET, POST, PUT, DELETE)
 * - Route prefixes
 * - Request/Response handling
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Attribute\Route;
use Core\Attribute\RoutePrefix;
use Core\Http\Request;
use Core\Http\Response;

#[RoutePrefix('/api/posts')]
class PostController
{
    #[Route('GET', '/')]
    public function index(Request $request): Response
    {
        $posts = [
            ['id' => 1, 'title' => 'First Post', 'status' => 'published'],
            ['id' => 2, 'title' => 'Second Post', 'status' => 'draft'],
            ['id' => 3, 'title' => 'Third Post', 'status' => 'published'],
        ];
        
        return Response::json($posts);
    }
    
    #[Route('POST', '/')]
    public function create(Request $request): Response
    {
        $data = $request->body;
        
        // Simulate creating a post
        $post = [
            'id' => 4,
            'title' => $data['title'] ?? 'Untitled',
            'content' => $data['content'] ?? '',
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return Response::json($post, 201);
    }
    
    #[Route('GET', '/:id')]
    public function show(Request $request): Response
    {
        // In a real app, you'd extract the :id parameter
        // This is a simplified example
        $post = [
            'id' => 1,
            'title' => 'First Post',
            'content' => 'This is the content of the first post.',
            'status' => 'published'
        ];
        
        return Response::json($post);
    }
    
    #[Route('PUT', '/:id')]
    public function update(Request $request): Response
    {
        $data = $request->body;
        
        $post = [
            'id' => 1,
            'title' => $data['title'] ?? 'Updated Title',
            'content' => $data['content'] ?? 'Updated content',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return Response::json($post);
    }
    
    #[Route('DELETE', '/:id')]
    public function delete(Request $request): Response
    {
        return Response::json(['message' => 'Post deleted successfully']);
    }
}

echo "Routing Examples:\n";
echo "================\n\n";

echo "GET    /api/posts      - List all posts\n";
echo "POST   /api/posts      - Create a new post\n";
echo "GET    /api/posts/:id  - Get a specific post\n";
echo "PUT    /api/posts/:id  - Update a post\n";
echo "DELETE /api/posts/:id  - Delete a post\n\n";

echo "To test these routes, add PostController to your routes/web.php:\n";
echo "\$router->registerControllers([\n";
echo "    PostController::class,\n";
echo "]);\n\n";

echo "Then use curl:\n";
echo "curl http://localhost:8000/api/posts\n";
echo "curl -X POST -H 'Content-Type: application/json' -d '{\"title\":\"New Post\"}' http://localhost:8000/api/posts\n";
