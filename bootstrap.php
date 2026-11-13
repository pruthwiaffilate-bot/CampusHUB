<?php
declare(strict_types=1);

session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();

const APP_NAME = 'CampusHub';
const DB_PATH = __DIR__ . '/storage/campushub.sqlite';

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $directory = dirname(DB_PATH);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the local data directory.');
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    initialize_database($pdo);
    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL CHECK (role IN ('admin', 'student', 'faculty', 'warden')),
            full_name TEXT NOT NULL,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
            prn TEXT NOT NULL UNIQUE,
            course TEXT NOT NULL,
            year TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS faculty (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
            department TEXT NOT NULL,
            designation TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS rooms (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            room_number TEXT NOT NULL UNIQUE,
            building TEXT NOT NULL,
            capacity INTEGER NOT NULL CHECK (capacity > 0),
            status TEXT NOT NULL DEFAULT 'Active' CHECK (status IN ('Active', 'Maintenance', 'Inactive'))
        );
        CREATE TABLE IF NOT EXISTS allocations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL REFERENCES students(id),
            room_id INTEGER NOT NULL REFERENCES rooms(id),
            start_date TEXT NOT NULL,
            end_date TEXT,
            status TEXT NOT NULL DEFAULT 'Active' CHECK (status IN ('Active', 'Ended')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL REFERENCES students(id),
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'Submitted' CHECK (status IN ('Submitted', 'In Progress', 'Resolved', 'Closed')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_by INTEGER NOT NULL REFERENCES users(id),
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            audience TEXT NOT NULL DEFAULT 'Everyone' CHECK (audience IN ('Everyone', 'Students', 'Faculty', 'Hostel')),
            published_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    SQL);
    seed_database($pdo);
}

function seed_database(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $addUser = $pdo->prepare(
            'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
        );
        $addStudent = $pdo->prepare(
            'INSERT INTO students (user_id, prn, course, year) VALUES (?, ?, ?, ?)'
        );
        $addFaculty = $pdo->prepare(
            'INSERT INTO faculty (user_id, department, designation) VALUES (?, ?, ?)'
        );

        $users = [];
        foreach ([
            ['admin', 'admin123', 'admin', 'Aarav Mehta'],
            ['student', 'student123', 'student', 'Ananya Shah'],
            ['faculty', 'faculty123', 'faculty', 'Dr. Rohan Desai'],
            ['warden', 'warden123', 'warden', 'Priya Kulkarni'],
        ] as [$username, $password, $role, $name]) {
            $addUser->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $name]);
            $users[$username] = (int) $pdo->lastInsertId();
        }

        $addStudent->execute([$users['student'], 'CH2024001', 'Computer Science', 'Year 2']);
        $addFaculty->execute([$users['faculty'], 'Computer Science', 'Associate Professor']);

        $pdo->exec(<<<'SQL'
            INSERT INTO rooms (room_number, building, capacity, status) VALUES
            ('A-101', 'Maple House', 3, 'Active'),
            ('A-102', 'Maple House', 2, 'Active'),
            ('B-204', 'Cedar House', 2, 'Maintenance'),
            ('B-205', 'Cedar House', 3, 'Active')
        SQL);
        $pdo->exec(
            "INSERT INTO allocations (student_id, room_id, start_date, status)
             SELECT students.id, rooms.id, date('now', '-45 days'), 'Active'
             FROM students CROSS JOIN rooms WHERE students.prn = 'CH2024001' AND rooms.room_number = 'A-101'"
        );

        $addNotice = $pdo->prepare(
            'INSERT INTO notices (created_by, title, content, audience) VALUES (?, ?, ?, ?)'
        );
        $addNotice->execute([
            $users['admin'],
            'Welcome to CampusHub',
            'Your campus information, hostel updates, and support requests are now together in one place.',
            'Everyone',
        ]);
        $addNotice->execute([
            $users['warden'],
            'Hostel help desk hours',
            'The hostel office is open Monday to Friday, 9:00 AM–5:00 PM.',
            'Hostel',
        ]);

        $addComplaint = $pdo->prepare(
            'INSERT INTO complaints (student_id, title, description, status) VALUES (?, ?, ?, ?)'
        );
        $addComplaint->execute([
            1,
            'Study room lighting',
            'One of the lights in the common study room needs attention.',
            'In Progress',
        ]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function text_length(string $value): int
{
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: index.php?view=login');
        exit;
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_user();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to perform this action.');
    }
    return $user;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('This page expired. Please go back, refresh, and try again.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function redirect(string $view): never
{
    header('Location: index.php?view=' . rawurlencode($view));
    exit;
}

function user_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'student' => 'Student',
        'faculty' => 'Faculty',
        'warden' => 'Hostel warden',
        default => 'User',
    };
}
