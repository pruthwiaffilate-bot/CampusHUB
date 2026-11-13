<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'login') {
        verify_csrf();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = $pdo->prepare('SELECT * FROM users WHERE username = ? AND active = 1');
        $statement->execute([$username]);
        $account = $statement->fetch();
        if ($account && password_verify($password, $account['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $account['id'],
                'username' => $account['username'],
                'full_name' => $account['full_name'],
                'role' => $account['role'],
            ];
            redirect('dashboard');
        }
        flash('Those details did not match an active account. Please try again.', 'error');
        redirect('login');
    }

    if ($action === 'logout') {
        verify_csrf();
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('login');
    }

    $user = require_user();
    verify_csrf();

    try {
        switch ($action) {
            case 'save_student':
                require_role(['admin']);
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $prn = trim((string) ($_POST['prn'] ?? ''));
                $course = trim((string) ($_POST['course'] ?? ''));
                $year = trim((string) ($_POST['year'] ?? ''));
                if ($name === '' || $username === '' || strlen($password) < 8 || $prn === '' || $course === '' || $year === '') {
                    throw new DomainException('Complete every field. Passwords must be at least 8 characters.');
                }
                $pdo->beginTransaction();
                $statement = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'student', ?)"
                );
                $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);
                $statement = $pdo->prepare(
                    'INSERT INTO students (user_id, prn, course, year) VALUES (?, ?, ?, ?)'
                );
                $statement->execute([(int) $pdo->lastInsertId(), $prn, $course, $year]);
                $pdo->commit();
                flash('Student profile created successfully.');
                redirect('students');

            case 'save_faculty':
                require_role(['admin']);
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $department = trim((string) ($_POST['department'] ?? ''));
                $designation = trim((string) ($_POST['designation'] ?? ''));
                if ($name === '' || $username === '' || strlen($password) < 8 || $department === '' || $designation === '') {
                    throw new DomainException('Complete every field. Passwords must be at least 8 characters.');
                }
                $pdo->beginTransaction();
                $statement = $pdo->prepare(
                    "INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, 'faculty', ?)"
                );
                $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);
                $statement = $pdo->prepare(
                    'INSERT INTO faculty (user_id, department, designation) VALUES (?, ?, ?)'
                );
                $statement->execute([(int) $pdo->lastInsertId(), $department, $designation]);
                $pdo->commit();
                flash('Faculty profile created successfully.');
                redirect('faculty');

            case 'save_room':
                require_role(['admin', 'warden']);
                $room = trim((string) ($_POST['room_number'] ?? ''));
                $building = trim((string) ($_POST['building'] ?? ''));
                $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
                $status = (string) ($_POST['status'] ?? 'Active');
                if ($room === '' || $building === '' || $capacity === false || $capacity < 1 ||
                    !in_array($status, ['Active', 'Maintenance', 'Inactive'], true)) {
                    throw new DomainException('Enter a room number, building, positive capacity, and valid status.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO rooms (room_number, building, capacity, status) VALUES (?, ?, ?, ?)'
                );
                $statement->execute([$room, $building, $capacity, $status]);
                flash('Room added to the hostel directory.');
                redirect('rooms');

            case 'save_allocation':
                require_role(['admin', 'warden']);
                $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
                $roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$studentId || !$roomId) {
                    throw new DomainException('Choose a student and an available room.');
                }
                $pdo->exec('BEGIN IMMEDIATE');
                $studentQuery = $pdo->prepare('SELECT id FROM students WHERE id = ?');
                $studentQuery->execute([$studentId]);
                $roomQuery = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'Active'");
                $roomQuery->execute([$roomId]);
                $room = $roomQuery->fetch();
                $activeStudentQuery = $pdo->prepare(
                    "SELECT COUNT(*) FROM allocations WHERE student_id = ? AND status = 'Active'"
                );
                $activeStudentQuery->execute([$studentId]);
                $activeRoomQuery = $pdo->prepare(
                    "SELECT COUNT(*) FROM allocations WHERE room_id = ? AND status = 'Active'"
                );
                $activeRoomQuery->execute([$roomId]);
                if (!$studentQuery->fetch() || !$room) {
                    throw new DomainException('The selected student or active room could not be found.');
                }
                if ((int) $activeStudentQuery->fetchColumn() > 0) {
                    throw new DomainException('This student already has an active room allocation.');
                }
                if ((int) $activeRoomQuery->fetchColumn() >= (int) $room['capacity']) {
                    throw new DomainException('That room has reached its current capacity.');
                }
                $statement = $pdo->prepare(
                    "INSERT INTO allocations (student_id, room_id, start_date, status) VALUES (?, ?, date('now'), 'Active')"
                );
                $statement->execute([$studentId, $roomId]);
                $pdo->commit();
                flash('Room allocation saved.');
                redirect('allocations');

            case 'end_allocation':
                require_role(['admin', 'warden']);
                $allocationId = filter_var($_POST['allocation_id'] ?? null, FILTER_VALIDATE_INT);
                $statement = $pdo->prepare(
                    "UPDATE allocations SET status = 'Ended', end_date = date('now') WHERE id = ? AND status = 'Active'"
                );
                $statement->execute([$allocationId]);
                flash($statement->rowCount() ? 'Allocation marked as ended.' : 'No active allocation was changed.');
                redirect('allocations');

            case 'save_complaint':
                require_role(['student']);
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $studentQuery = $pdo->prepare('SELECT id FROM students WHERE user_id = ?');
                $studentQuery->execute([$user['id']]);
                $studentId = $studentQuery->fetchColumn();
                if (!$studentId) {
                    throw new DomainException('Your account is not linked to a student profile. Contact the administrator.');
                }
                if ($title === '' || $description === '' || text_length($title) > 120 || text_length($description) > 2000) {
                    throw new DomainException('Enter a title and description (up to 120 and 2,000 characters).');
                }
                $statement = $pdo->prepare(
                    "INSERT INTO complaints (student_id, title, description, status) VALUES (?, ?, ?, 'Submitted')"
                );
                $statement->execute([$studentId, $title, $description]);
                flash('Your request has been submitted. You can follow its progress here.');
                redirect('complaints');

            case 'update_complaint':
                require_role(['admin', 'warden']);
                $complaintId = filter_var($_POST['complaint_id'] ?? null, FILTER_VALIDATE_INT);
                $status = (string) ($_POST['status'] ?? '');
                if (!$complaintId || !in_array($status, ['Submitted', 'In Progress', 'Resolved', 'Closed'], true)) {
                    throw new DomainException('Select a valid complaint status.');
                }
                $statement = $pdo->prepare(
                    'UPDATE complaints SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
                $statement->execute([$status, $complaintId]);
                flash('Complaint status updated.');
                redirect('complaints');

            case 'save_notice':
                require_role(['admin']);
                $title = trim((string) ($_POST['title'] ?? ''));
                $content = trim((string) ($_POST['content'] ?? ''));
                $audience = (string) ($_POST['audience'] ?? 'Everyone');
                if ($title === '' || $content === '' ||
                    !in_array($audience, ['Everyone', 'Students', 'Faculty', 'Hostel'], true)) {
                    throw new DomainException('Enter a notice title and message, and choose a valid audience.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO notices (created_by, title, content, audience) VALUES (?, ?, ?, ?)'
                );
                $statement->execute([$user['id'], $title, $content, $audience]);
                flash('Notice published.');
                redirect('notices');

            default:
                throw new DomainException('That action is not available.');
        }
    } catch (DomainException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash($exception->getMessage(), 'error');
        redirect((string) ($_POST['return_to'] ?? 'dashboard'));
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('CampusHub database operation failed: ' . $exception->getMessage());
        flash('The change could not be saved. Check for duplicate details and try again.', 'error');
        redirect((string) ($_POST['return_to'] ?? 'dashboard'));
    }
}

$view = (string) ($_GET['view'] ?? ($user ? 'dashboard' : 'login'));
$allowedViews = ['dashboard', 'students', 'faculty', 'rooms', 'allocations', 'complaints', 'notices', 'reports', 'login'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}
$viewRoles = [
    'dashboard' => ['admin', 'student', 'faculty', 'warden'],
    'students' => ['admin'],
    'faculty' => ['admin', 'faculty'],
    'rooms' => ['admin', 'warden'],
    'allocations' => ['admin', 'warden', 'student'],
    'complaints' => ['admin', 'student', 'warden'],
    'notices' => ['admin', 'student', 'faculty', 'warden'],
    'reports' => ['admin', 'warden'],
];
if ($view !== 'login' && !$user) {
    redirect('login');
}
if ($view === 'login' && $user) {
    redirect('dashboard');
}
if ($view !== 'login') {
    require_role($viewRoles[$view]);
}
$pageTitles = [
    'dashboard' => 'Overview',
    'students' => 'Students',
    'faculty' => 'Faculty',
    'rooms' => 'Hostel rooms',
    'allocations' => 'Room allocations',
    'complaints' => 'Complaints & requests',
    'notices' => 'Campus notices',
    'reports' => 'Reports & insights',
    'login' => 'Welcome back',
];
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($view === 'login') {
    require __DIR__ . '/views/login.php';
    exit;
}

$user = require_user();
$role = $user['role'];
$navItems = [
    ['dashboard', 'grid', 'Overview', ['admin', 'student', 'faculty', 'warden']],
    ['students', 'users', 'Students', ['admin']],
    ['faculty', 'faculty', 'Faculty', ['admin', 'faculty']],
    ['rooms', 'building', 'Hostel rooms', ['admin', 'warden']],
    ['allocations', 'bed', 'Allocations', ['admin', 'warden', 'student']],
    ['complaints', 'message', 'Complaints', ['admin', 'student', 'warden']],
    ['notices', 'bell', 'Notices', ['admin', 'student', 'faculty', 'warden']],
    ['reports', 'chart', 'Reports', ['admin', 'warden']],
];
$visibleNav = array_values(array_filter($navItems, static fn(array $item): bool => in_array($role, $item[3], true)));
$unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Submitted', 'In Progress')")->fetchColumn();

require __DIR__ . '/views/layout.php';
