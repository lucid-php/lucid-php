<?php

declare(strict_types=1);

/**
 * Example 14: File Upload
 * 
 * Demonstrates:
 * - Handling file uploads
 * - File validation (size, type, dimensions)
 * - Storing uploaded files
 * - Security best practices
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Upload\FileUploadHandler;
use Core\Upload\UploadedFile;

echo "File Upload Examples:\n";
echo "=====================\n\n";

// ===========================
// Example 1: Basic Upload Structure
// ===========================

echo "=== Example 1: Basic Upload Structure ===\n\n";

echo "HTML Form:\n";
echo "<form method=\"POST\" enctype=\"multipart/form-data\">\n";
echo "    <input type=\"file\" name=\"avatar\">\n";
echo "    <button type=\"submit\">Upload</button>\n";
echo "</form>\n\n";

echo "Controller handling:\n";
echo "public function upload(Request \$request): Response\n";
echo "{\n";
echo "    \$file = \$request->file('avatar');\n";
echo "    \n";
echo "    if (!\$file) {\n";
echo "        return Response::json(['error' => 'No file uploaded'], 400);\n";
echo "    }\n";
echo "    \n";
echo "    \$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');\n";
echo "    \$path = \$handler->store(\$file, 'avatars');\n";
echo "    \n";
echo "    return Response::json(['path' => \$path]);\n";
echo "}\n\n";

// ===========================
// Example 2: File Validation
// ===========================

echo "=== Example 2: File Validation ===\n\n";

echo "Validate file before storing:\n\n";

echo "\$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');\n\n";

echo "// Size validation (max 2MB)\n";
echo "\$handler->maxSize(2 * 1024 * 1024);\n\n";

echo "// Allowed MIME types\n";
echo "\$handler->allowedTypes(['image/jpeg', 'image/png', 'image/gif']);\n\n";

echo "// Allowed extensions\n";
echo "\$handler->allowedExtensions(['jpg', 'jpeg', 'png', 'gif']);\n\n";

echo "// Image dimensions (for images only)\n";
echo "\$handler->maxWidth(2000);\n";
echo "\$handler->maxHeight(2000);\n";
echo "\$handler->minWidth(100);\n";
echo "\$handler->minHeight(100);\n\n";

// ===========================
// Example 3: Storing Files
// ===========================

echo "=== Example 3: Storing Files ===\n\n";

echo "Different storage strategies:\n\n";

echo "// Store with original filename\n";
echo "\$path = \$handler->store(\$file, 'avatars');\n";
echo "// Result: avatars/profile.jpg\n\n";

echo "// Store with custom name\n";
echo "\$path = \$handler->storeAs(\$file, 'avatars', 'user-123.jpg');\n";
echo "// Result: avatars/user-123.jpg\n\n";

echo "// Store with generated unique name\n";
echo "\$path = \$handler->storeWithUniqueId(\$file, 'avatars');\n";
echo "// Result: avatars/a3f5c2d8-4b1e-4c5d-8e2f-9a1b2c3d4e5f.jpg\n\n";

// ===========================
// Example 4: Multiple File Uploads
// ===========================

echo "=== Example 4: Multiple File Uploads ===\n\n";

echo "HTML Form:\n";
echo "<form method=\"POST\" enctype=\"multipart/form-data\">\n";
echo "    <input type=\"file\" name=\"photos[]\" multiple>\n";
echo "    <button type=\"submit\">Upload</button>\n";
echo "</form>\n\n";

echo "Controller:\n";
echo "public function uploadMultiple(Request \$request): Response\n";
echo "{\n";
echo "    \$files = \$request->files('photos');\n";
echo "    \$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');\n";
echo "    \n";
echo "    \$paths = [];\n";
echo "    foreach (\$files as \$file) {\n";
echo "        \$paths[] = \$handler->store(\$file, 'photos');\n";
echo "    }\n";
echo "    \n";
echo "    return Response::json(['paths' => \$paths]);\n";
echo "}\n\n";

// ===========================
// Example 5: Image Upload with Validation
// ===========================

echo "=== Example 5: Image Upload with Validation ===\n\n";

echo "class ProfileController\n";
echo "{\n";
echo "    public function uploadAvatar(Request \$request): Response\n";
echo "    {\n";
echo "        \$file = \$request->file('avatar');\n";
echo "        \n";
echo "        if (!\$file) {\n";
echo "            return Response::json(['error' => 'No file provided'], 400);\n";
echo "        }\n";
echo "        \n";
echo "        \$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');\n";
echo "        \n";
echo "        // Validation rules\n";
echo "        \$handler->maxSize(5 * 1024 * 1024); // 5MB\n";
echo "        \$handler->allowedTypes(['image/jpeg', 'image/png', 'image/webp']);\n";
echo "        \$handler->maxWidth(1000);\n";
echo "        \$handler->maxHeight(1000);\n";
echo "        \n";
echo "        try {\n";
echo "            \$path = \$handler->store(\$file, 'avatars');\n";
echo "            \n";
echo "            return Response::json([\n";
echo "                'success' => true,\n";
echo "                'path' => \$path\n";
echo "            ]);\n";
echo "        } catch (\\Exception \$e) {\n";
echo "            return Response::json([\n";
echo "                'error' => \$e->getMessage()\n";
echo "            ], 422);\n";
echo "        }\n";
echo "    }\n";
echo "}\n\n";

// ===========================
// Example 6: Document Upload
// ===========================

echo "=== Example 6: Document Upload ===\n\n";

echo "public function uploadDocument(Request \$request): Response\n";
echo "{\n";
echo "    \$file = \$request->file('document');\n";
echo "    \n";
echo "    \$handler = new FileUploadHandler(__DIR__ . '/../storage/uploads');\n";
echo "    \n";
echo "    // Allow PDF and Office documents\n";
echo "    \$handler->allowedTypes([\n";
echo "        'application/pdf',\n";
echo "        'application/msword',\n";
echo "        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',\n";
echo "    ]);\n";
echo "    \n";
echo "    \$handler->maxSize(10 * 1024 * 1024); // 10MB\n";
echo "    \n";
echo "    \$path = \$handler->store(\$file, 'documents');\n";
echo "    \n";
echo "    return Response::json(['path' => \$path]);\n";
echo "}\n\n";

// ===========================
// Example 7: File Information
// ===========================

echo "=== Example 7: Accessing File Information ===\n\n";

echo "\$file = \$request->file('upload');\n\n";

echo "// File properties\n";
echo "\$name = \$file->getName();           // 'document.pdf'\n";
echo "\$size = \$file->getSize();           // 1048576 (bytes)\n";
echo "\$mimeType = \$file->getMimeType();   // 'application/pdf'\n";
echo "\$extension = \$file->getExtension(); // 'pdf'\n";
echo "\$tmpPath = \$file->getTmpPath();     // '/tmp/php12345'\n\n";

// ===========================
// Example 8: Security Best Practices
// ===========================

echo "=== Example 8: Security Best Practices ===\n\n";

echo "1. Always validate file types\n";
echo "   ✓ Check MIME type (server-side)\n";
echo "   ✓ Check file extension\n";
echo "   ✗ Don't trust client-provided data\n\n";

echo "2. Validate file size\n";
echo "   ✓ Set reasonable limits\n";
echo "   ✓ Prevents DoS attacks\n\n";

echo "3. Generate unique filenames\n";
echo "   ✓ Use UUIDs or hashes\n";
echo "   ✗ Don't use user-provided names directly\n\n";

echo "4. Store outside public directory\n";
echo "   ✓ storage/uploads/ (not public/uploads/)\n";
echo "   ✓ Serve files through controller with auth\n\n";

echo "5. Scan for malware (production)\n";
echo "   ✓ Use ClamAV or similar\n";
echo "   ✓ Especially for user-uploaded files\n\n";

// ===========================
// Example 9: Serving Uploaded Files
// ===========================

echo "=== Example 9: Serving Uploaded Files ===\n\n";

echo "Controller to serve files with authentication:\n\n";
echo "class FileController\n";
echo "{\n";
echo "    #[Route('GET', '/files/:filename')]\n";
echo "    public function download(Request \$request): Response\n";
echo "    {\n";
echo "        \$filename = \$request->params['filename'];\n";
echo "        \$filePath = __DIR__ . '/../storage/uploads/' . \$filename;\n";
echo "        \n";
echo "        // Validate user has access\n";
echo "        if (!\$this->userCanAccessFile(\$filename)) {\n";
echo "            return Response::text('Forbidden', 403);\n";
echo "        }\n";
echo "        \n";
echo "        if (!file_exists(\$filePath)) {\n";
echo "            return Response::text('Not Found', 404);\n";
echo "        }\n";
echo "        \n";
echo "        // Serve file\n";
echo "        header('Content-Type: ' . mime_content_type(\$filePath));\n";
echo "        header('Content-Length: ' . filesize(\$filePath));\n";
echo "        readfile(\$filePath);\n";
echo "        exit;\n";
echo "    }\n";
echo "}\n\n";

// ===========================
// Example 10: Configuration
// ===========================

echo "=== Example 10: Configuration (config/upload.php) ===\n\n";

echo "return [\n";
echo "    'path' => __DIR__ . '/../storage/uploads',\n";
echo "    \n";
echo "    'max_size' => 10 * 1024 * 1024, // 10MB\n";
echo "    \n";
echo "    'allowed_types' => [\n";
echo "        'image/jpeg',\n";
echo "        'image/png',\n";
echo "        'image/gif',\n";
echo "        'image/webp',\n";
echo "        'application/pdf',\n";
echo "    ],\n";
echo "    \n";
echo "    'images' => [\n";
echo "        'max_width' => 4000,\n";
echo "        'max_height' => 4000,\n";
echo "        'min_width' => 100,\n";
echo "        'min_height' => 100,\n";
echo "    ],\n";
echo "];\n\n";

// ===========================
// Summary
// ===========================

echo "=== Summary ===\n\n";

echo "Key Features:\n";
echo "  ✓ Type-safe file handling\n";
echo "  ✓ Comprehensive validation (size, type, dimensions)\n";
echo "  ✓ Secure file storage\n";
echo "  ✓ Multiple storage strategies\n";
echo "  ✓ Protection against common attacks\n\n";

echo "Common Validation Rules:\n";
echo "  Images: 5MB max, 1000x1000px, jpg/png/webp\n";
echo "  Documents: 10MB max, pdf/docx\n";
echo "  Videos: 100MB max, mp4/webm\n\n";
