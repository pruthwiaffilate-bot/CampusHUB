<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'download_database') {
        require_role(['admin']);
        verify_csrf();
        $backupPath = tempnam(sys_get_temp_dir(), 'campushub-backup-');
        if ($backupPath === false) {
            throw new RuntimeException('Unable to prepare a temporary database backup.');
        }
        unlink($backupPath);
        try {
            $pdo->exec('VACUUM INTO ' . $pdo->quote($backupPath));
            if (!is_readable($backupPath)) {
                throw new RuntimeException('The temporary database backup could not be read.');
            }
            header('Content-Type: application/vnd.sqlite3');
            header('Content-Disposition: attachment; filename="campushub-backup-' . date('Y-m-d-His') . '.sqlite"');
            header('Content-Length: ' . (string) filesize($backupPath));
            readfile($backupPath);
        } catch (Throwable $exception) {
            error_log('CampusHub database backup failed: ' . $exception->getMessage());
            if (is_file($backupPath)) {
                unlink($backupPath);
            }
            http_response_code(500);
            exit('The database backup could not be created.');
        } finally {
            if (is_file($backupPath)) {
                unlink($backupPath);
            }
        }
        exit;
    }

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

            case 'update_student':
                require_role(['admin']);
                $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $prn = trim((string) ($_POST['prn'] ?? ''));
                $course = trim((string) ($_POST['course'] ?? ''));
                $year = trim((string) ($_POST['year'] ?? ''));
                if (!$studentId || $name === '' || $username === '' || $prn === '' || $course === '' || $year === '' ||
                    ($password !== '' && strlen($password) < 8)) {
                    throw new DomainException('Complete every required field. A new password must be at least 8 characters.');
                }
                $check = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
                $check->execute([$studentId]);
                $userId = $check->fetchColumn();
                if (!$userId) {
                    throw new DomainException('That student profile no longer exists.');
                }
                $pdo->beginTransaction();
                $statement = $pdo->prepare('UPDATE users SET username = ?, full_name = ? WHERE id = ?');
                $statement->execute([$username, $name, $userId]);
                $statement = $pdo->prepare('UPDATE students SET prn = ?, course = ?, year = ? WHERE id = ?');
                $statement->execute([$prn, $course, $year, $studentId]);
                if ($password !== '') {
                    $statement = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $statement->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
                }
                $pdo->commit();
                flash('Student profile updated.');
                redirect('students');

            case 'update_profile':
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $currentPassword = (string) ($_POST['current_password'] ?? '');
                $newPassword = (string) ($_POST['new_password'] ?? '');
                if ($name === '' || strlen($name) > 120 || $currentPassword === '' ||
                    ($newPassword !== '' && strlen($newPassword) < 8)) {
                    throw new DomainException('Enter your name and current password. A new password must be at least 8 characters.');
                }
                $statement = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
                $statement->execute([$user['id']]);
                $passwordHash = $statement->fetchColumn();
                if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
                    throw new DomainException('Your current password was not correct.');
                }
                if ($newPassword === '') {
                    $statement = $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?');
                    $statement->execute([$name, $user['id']]);
                } else {
                    $statement = $pdo->prepare('UPDATE users SET full_name = ?, password_hash = ? WHERE id = ?');
                    $statement->execute([$name, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
                }
                $_SESSION['user']['full_name'] = $name;
                flash('Your account profile was updated.');
                redirect('profile');

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

            case 'update_faculty':
                require_role(['admin']);
                $facultyId = filter_var($_POST['faculty_id'] ?? null, FILTER_VALIDATE_INT);
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $department = trim((string) ($_POST['department'] ?? ''));
                $designation = trim((string) ($_POST['designation'] ?? ''));
                if (!$facultyId || $name === '' || $username === '' || $department === '' || $designation === '' ||
                    ($password !== '' && strlen($password) < 8)) {
                    throw new DomainException('Complete every required field. A new password must be at least 8 characters.');
                }
                $check = $pdo->prepare('SELECT user_id FROM faculty WHERE id = ?');
                $check->execute([$facultyId]);
                $userId = $check->fetchColumn();
                if (!$userId) {
                    throw new DomainException('That faculty profile no longer exists.');
                }
                $pdo->beginTransaction();
                $statement = $pdo->prepare('UPDATE users SET username = ?, full_name = ? WHERE id = ?');
                $statement->execute([$username, $name, $userId]);
                $statement = $pdo->prepare(
                    'UPDATE faculty SET department = ?, designation = ? WHERE id = ?'
                );
                $statement->execute([$department, $designation, $facultyId]);
                if ($password !== '') {
                    $statement = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $statement->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
                }
                $pdo->commit();
                flash('Faculty profile updated.');
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

            case 'update_room':
                require_role(['admin', 'warden']);
                $roomId = filter_var($_POST['room_id'] ?? null, FILTER_VALIDATE_INT);
                $room = trim((string) ($_POST['room_number'] ?? ''));
                $building = trim((string) ($_POST['building'] ?? ''));
                $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
                $status = (string) ($_POST['status'] ?? '');
                if (!$roomId || $room === '' || $building === '' || $capacity === false || $capacity < 1 ||
                    !in_array($status, ['Active', 'Maintenance', 'Inactive'], true)) {
                    throw new DomainException('Enter a room number, building, positive capacity, and valid status.');
                }
                $occupiedQuery = $pdo->prepare(
                    "SELECT COUNT(*) FROM allocations WHERE room_id = ? AND status = 'Active'"
                );
                $occupiedQuery->execute([$roomId]);
                $occupied = (int) $occupiedQuery->fetchColumn();
                if ($occupied > $capacity) {
                    throw new DomainException("Capacity cannot be lower than the room's {$occupied} active allocation(s).");
                }
                if ($occupied > 0 && $status !== 'Active') {
                    throw new DomainException('End active allocations before changing an occupied room to unavailable.');
                }
                $statement = $pdo->prepare(
                    'UPDATE rooms SET room_number = ?, building = ?, capacity = ?, status = ? WHERE id = ?'
                );
                $statement->execute([$room, $building, $capacity, $status, $roomId]);
                if ($statement->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT 1 FROM rooms WHERE id = ?');
                    $check->execute([$roomId]);
                    if (!$check->fetchColumn()) {
                        throw new DomainException('That room no longer exists.');
                    }
                }
                flash('Room details updated.');
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

            case 'update_notice':
                require_role(['admin']);
                $noticeId = filter_var($_POST['notice_id'] ?? null, FILTER_VALIDATE_INT);
                $title = trim((string) ($_POST['title'] ?? ''));
                $content = trim((string) ($_POST['content'] ?? ''));
                $audience = (string) ($_POST['audience'] ?? '');
                if (!$noticeId || $title === '' || $content === '' ||
                    !in_array($audience, ['Everyone', 'Students', 'Faculty', 'Hostel'], true)) {
                    throw new DomainException('Enter a notice title and message, and choose a valid audience.');
                }
                $statement = $pdo->prepare(
                    'UPDATE notices SET title = ?, content = ?, audience = ? WHERE id = ?'
                );
                $statement->execute([$title, $content, $audience, $noticeId]);
                if ($statement->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT 1 FROM notices WHERE id = ?');
                    $check->execute([$noticeId]);
                    if (!$check->fetchColumn()) {
                        throw new DomainException('That notice no longer exists.');
                    }
                }
                flash('Notice updated.');
                redirect('notices');

            case 'delete_notice':
                require_role(['admin']);
                $noticeId = filter_var($_POST['notice_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$noticeId) {
                    throw new DomainException('Select a valid notice.');
                }
                $statement = $pdo->prepare('DELETE FROM notices WHERE id = ?');
                $statement->execute([$noticeId]);
                flash($statement->rowCount() ? 'Notice deleted.' : 'That notice was already removed.');
                redirect('notices');

            case 'create_staff_account':
                require_role(['admin']);
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $staffRole = (string) ($_POST['role'] ?? '');
                if ($name === '' || $username === '' || strlen($password) < 8 ||
                    !in_array($staffRole, ['admin', 'warden'], true)) {
                    throw new DomainException('Enter a name and username. Passwords must be at least 8 characters.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
                );
                $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $staffRole, $name]);
                flash(user_role_label($staffRole) . ' account created.');
                redirect('accounts');

            case 'toggle_user':
                require_role(['admin']);
                $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$targetId || $targetId === (int) $user['id']) {
                    throw new DomainException('You cannot change your own account status here.');
                }
                $targetQuery = $pdo->prepare('SELECT id, role, active FROM users WHERE id = ?');
                $targetQuery->execute([$targetId]);
                $target = $targetQuery->fetch();
                if (!$target) {
                    throw new DomainException('That account no longer exists.');
                }
                if ($target['role'] === 'admin' && (int) $target['active'] === 1) {
                    $activeAdmins = (int) $pdo->query(
                        "SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"
                    )->fetchColumn();
                    if ($activeAdmins <= 1) {
                        throw new DomainException('At least one active administrator account must remain.');
                    }
                }
                $statement = $pdo->prepare('UPDATE users SET active = ? WHERE id = ?');
                $statement->execute([(int) $target['active'] === 1 ? 0 : 1, $targetId]);
                flash((int) $target['active'] === 1 ? 'Account deactivated.' : 'Account activated.');
                redirect('accounts');

            case 'reset_user_password':
                require_role(['admin']);
                $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                $password = (string) ($_POST['password'] ?? '');
                if (!$targetId || strlen($password) < 8) {
                    throw new DomainException('Enter a new password with at least 8 characters.');
                }
                $statement = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $statement->execute([password_hash($password, PASSWORD_DEFAULT), $targetId]);
                if ($statement->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT 1 FROM users WHERE id = ?');
                    $check->execute([$targetId]);
                    if (!$check->fetchColumn()) {
                        throw new DomainException('That account no longer exists.');
                    }
                }
                flash('Account password reset.');
                redirect('accounts');

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
$allowedViews = ['dashboard', 'students', 'faculty', 'rooms', 'allocations', 'complaints', 'notices', 'reports', 'accounts', 'database', 'profile', 'login'];
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
    'accounts' => ['admin'],
    'database' => ['admin'],
    'profile' => ['admin', 'student', 'faculty', 'warden'],
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
    'accounts' => 'User accounts',
    'database' => 'Database & backup',
    'profile' => 'My profile',
    'login' => 'Welcome back',
];
if ($view === 'login') {
    require __DIR__ . '/views/login.php';
    exit;
}

$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

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
    ['accounts', 'users', 'User accounts', ['admin']],
    ['database', 'database', 'Database & backup', ['admin']],
];
$visibleNav = array_values(array_filter($navItems, static fn(array $item): bool => in_array($role, $item[3], true)));
$unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Submitted', 'In Progress')")->fetchColumn();

require __DIR__ . '/views/layout.php';
