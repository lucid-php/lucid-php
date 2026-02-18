<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title ?? 'Lucid-PHP') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        header {
            background: #2563eb;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header h1 { font-size: 1.5rem; font-weight: 600; }
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .user-list {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        .user-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info h3 { font-size: 1.125rem; margin-bottom: 0.25rem; }
        .user-info p { color: #6b7280; font-size: 0.875rem; }
        .badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <header>
        <h1>Lucid-PHP</h1>
    </header>
    <main>
        <div class="card">
            <h2><?= $escape($title) ?></h2>
            <p style="color: #6b7280; margin-top: 0.5rem;"><?= $escape($subtitle ?? '') ?></p>
        </div>

        <?php if (empty($users)): ?>
            <div class="card empty-state">
                <p>No users found. Create some via the API!</p>
                <p style="margin-top: 1rem; font-size: 0.875rem;">
                    POST /api/users with JSON: {"name": "...", "email": "...", "password": "..."}
                </p>
            </div>
        <?php else: ?>
            <div class="user-list">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <h3><?= $escape($user['name']) ?></h3>
                            <p><?= $escape($user['email']) ?></p>
                        </div>
                        <span class="badge">ID: <?= $escape((string)$user['id']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 2rem;">
            <h3 style="margin-bottom: 0.5rem;">About This Page</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">
                This is a plain PHP template rendered with <code>Response::view()</code>. 
                No magic, no custom syntax - just explicit variable passing and XSS-safe output via <code>$escape()</code>.
            </p>
        </div>
    </main>
</body>
</html>
