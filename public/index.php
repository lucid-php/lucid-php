<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Application;
use Core\Config\Config;
use Core\Database\Database;
use Core\Event\EventDispatcher;
use Core\Http\ExceptionHandler;
use Core\Middleware\ExceptionMiddleware;
use Core\Queue\QueueInterface;
use Core\Queue\SyncQueue;
use Core\Queue\DatabaseQueue;
use Core\Mail\MailerInterface;
use Core\Mail\SmtpMailer;
use Core\Mail\LogMailer;
use Core\Mail\ArrayMailer;
use Core\Log\Logger;
use Core\Log\Handler\StderrHandler;
use App\Controllers\HomeController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Event\UserCreated;
use App\Event\UserDeleted;
use App\Listener\SendWelcomeEmail;
use App\Listener\LogUserCreation;
use App\Listener\CleanupUserData;

$app = new Application();

// --- Configuration (Explicit PHP Files) ---
$config = new Config(__DIR__ . '/../config');

// --- Exception Handler (Explicit Error Responses) ---
$debug = $config->get('app.debug', false);
$exceptionHandler = new ExceptionHandler($debug);
$exceptionMiddleware = new ExceptionMiddleware($exceptionHandler);

// Register exception handler in container so middleware can resolve it
$app->getContainer()->set(ExceptionHandler::class, $exceptionHandler);
$app->getContainer()->set(ExceptionMiddleware::class, $exceptionMiddleware);

// Register exception middleware globally (catches all exceptions)
$app->addGlobalMiddleware(ExceptionMiddleware::class);

// --- Database Setup (Zero-Magic) ---
$dbDriver = $config->get('database.driver');

if ($dbDriver === 'sqlite') {
    $dbPath = __DIR__ . '/../' . $config->get('database.sqlite.path');
    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }
    $dsn = 'sqlite:' . $dbPath;
    $username = null;
    $password = null;
} elseif ($dbDriver === 'mysql') {
    $host = $config->get('database.mysql.host');
    $port = $config->get('database.mysql.port');
    $dbname = $config->get('database.mysql.database');
    $charset = $config->get('database.mysql.charset');
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $username = $config->get('database.mysql.username');
    $password = $config->get('database.mysql.password');
} else {
    throw new \RuntimeException("Unsupported database driver: $dbDriver");
}

$db = new Database($dsn, $username, $password);
$app->getContainer()->set(Database::class, $db);
$app->getContainer()->set(Config::class, $config);

// --- Queue System Setup (Explicit Driver Configuration) ---
$queueDriver = $config->get('queue.driver', 'sync');

if ($queueDriver === 'database') {
    $queue = new DatabaseQueue($db);
} else {
    // Default: Sync queue (executes jobs immediately)
    $queue = new SyncQueue($app->getContainer());
}

$app->getContainer()->set(QueueInterface::class, $queue);

// --- Mail System Setup (Explicit Driver Configuration) ---
$mailDriver = $config->get('mail.driver', 'log');
$mailFrom = $config->get('mail.from.address', 'noreply@example.com');

if ($mailDriver === 'smtp') {
    $mailer = new SmtpMailer(
        host: $config->get('mail.smtp.host'),
        port: $config->get('mail.smtp.port'),
        username: $config->get('mail.smtp.username'),
        password: $config->get('mail.smtp.password'),
        encryption: $config->get('mail.smtp.encryption'),
        defaultFrom: $mailFrom,
    );
} elseif ($mailDriver === 'array') {
    $mailer = new ArrayMailer($mailFrom);
} else {
    // Default: Log mailer (logs emails for development)
    $logger = $app->getContainer()->has(Logger::class)
        ? $app->getContainer()->get(Logger::class)
        : new Logger(handlers: [new StderrHandler()]);
    
    $mailer = new LogMailer($logger, $mailFrom);
}

$app->getContainer()->set(MailerInterface::class, $mailer);

// --- Event System Setup (Explicit Listener Registration) ---
$events = new EventDispatcher($app->getContainer());

// Explicitly register event listeners (no magic discovery)
$events->listen(UserCreated::class, [
    SendWelcomeEmail::class,
    LogUserCreation::class,
]);

$events->listen(UserDeleted::class, [
    CleanupUserData::class,
]);

// Register EventDispatcher in container
$app->getContainer()->set(EventDispatcher::class, $events);

// Explicitly register controllers
$app->registerControllers([
    HomeController::class,
    ApiController::class,
    AuthController::class,
]);

$app->run();
