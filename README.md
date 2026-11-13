# CampusHub

A friendly, role-based college and hostel portal built with PHP and SQLite. It includes a dashboard, student and faculty directories, hostel room and allocation management, student complaints, campus notices, and staff reports.

## Run offline on your computer

Once PHP and this project are present on your computer, CampusHub can run without an internet connection. It uses PHP's built-in local web server and a local SQLite database. It does not need MySQL, Composer, npm, or a connection to an online service.

### 1. Prepare the files and PHP

- Copy or extract the complete project folder onto your computer. If the computer is already offline, transfer the folder using a USB drive or another local method.
- Install PHP 8.1 or later before going offline. XAMPP is an option on Windows; it includes PHP and Apache. The app uses SQLite, so MySQL is not required.
- Confirm PHP is available in a terminal:

  **Windows (PowerShell or Command Prompt):**
  ```powershell
  php -v
  php -m | findstr /I "PDO SQLite"
  ```

  If `php` is not on your PATH, use the PHP executable included with XAMPP, for example:
  ```powershell
  C:\xampp\php\php.exe -v
  C:\xampp\php\php.exe -m | findstr /I "PDO SQLite"
  ```

  **macOS or Linux:**
  ```bash
  php -v
  php -m | grep -Ei 'pdo_sqlite|sqlite3'
  ```

- The PHP modules must include `PDO` and `pdo_sqlite` (usually `sqlite3` is enabled too). If `pdo_sqlite` is missing, enable/install the SQLite extension for your PHP distribution, then open a new terminal and check again. For XAMPP, edit its `php.ini`, enable the `pdo_sqlite` extension if it is commented out, save, and restart the terminal.

### 2. Start CampusHub

#### Windows with XAMPP (Apache)

1. If the computer is offline and XAMPP is not installed yet, transfer the XAMPP installer to it and install XAMPP. Make sure its PHP version is 8.1 or later.
2. Copy the project folder into XAMPP's web root, normally `C:\xampp\htdocs\CampusHUB`. Check that the app's `index.php` is directly inside that folder (not one folder deeper).
3. Open **XAMPP Control Panel**. In the **Apache** row, click **Start**. You do not need to start MySQL.
4. If `pdo_sqlite` was missing during the check above, edit `C:\xampp\php\php.ini`, enable the `pdo_sqlite` extension (and `sqlite3`, if present), save the file, then stop and restart Apache in the Control Panel.
5. On the same computer, open a browser to [http://localhost/CampusHUB/](http://localhost/CampusHUB/). If Apache is configured to use a different port, include it in the address, for example `http://localhost:8080/CampusHUB/`.
6. Sign in with one of the demo accounts below. The SQLite database is created automatically under `C:\xampp\htdocs\CampusHUB\storage\campushub.sqlite` on the first request.
7. When finished, return to XAMPP Control Panel and click **Stop** next to Apache. Your local database remains there for the next time you start Apache.

The Windows account or Apache process must have permission to write to the project folder so it can create the `storage` directory and database. If Apache starts but the page is unavailable, check the Apache port and XAMPP's Apache error log.

#### Built-in PHP server (Windows, macOS, or Linux)

Open a terminal and change directory to the project folder. Replace the example path with where you copied the folder.

**Windows:**
```powershell
cd C:\path\to\CampusHUB
php -S 127.0.0.1:8000
```

If PHP is not on PATH, use XAMPP's PHP executable:
```powershell
cd C:\path\to\CampusHUB
C:\xampp\php\php.exe -S 127.0.0.1:8000
```

For this built-in-server option, you do not need to start Apache or MySQL in XAMPP.

**macOS or Linux:**
```bash
cd /path/to/CampusHUB
php -S 127.0.0.1:8000
```

Keep the terminal window open while using the app. Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in a browser on the same computer. To stop the local server, focus the terminal and press **Ctrl+C**.

If port `8000` is already in use, start the server on another port (for example `php -S 127.0.0.1:8001`) and open that port in the browser.

### 3. First run and local data

On its first run, CampusHub creates the `storage/` directory, creates `storage/campushub.sqlite`, and adds sample records. The project folder must be writable by your operating-system account so PHP can create and update this local database. Your records remain on this computer between sessions; starting or stopping the server does not erase them.

To reset the demo and recreate the original sample data, stop the server first, delete only `storage/campushub.sqlite`, then start the server again. Back up that SQLite file (with the server stopped) to preserve your local records.

### Demo accounts

Use these accounts for the first-run sample data:

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `admin123` |
| Student | `student` | `student123` |
| Faculty | `faculty` | `faculty123` |
| Hostel warden | `warden` | `warden123` |

These are demo credentials for local evaluation only. Change or remove them before any real deployment. New student and faculty accounts are created by the administrator from their directory pages.

## Included workflows

- Role-aware sign-in and navigation for administrators, students, faculty, and hostel wardens.
- Add student and faculty profiles with login accounts.
- Maintain rooms and capacity; assign students to active rooms without exceeding capacity or creating a second active allocation.
- End an allocation and retain its history.
- Submit private student complaints and update their status as authorized staff.
- Publish notices to everyone or a selected audience.
- View record summaries and hostel/request reports.

## Notes

The interface uses local CSS and system fonts and makes no external font or asset requests, so it remains usable offline. The project synopsis proposes PHP and MySQL for eventual deployment; adapting the schema and connection configuration for the institution's MySQL environment is a separate deployment step.

The application is a project prototype, not a production-ready student information system. Use real institutional data only after reviewing access policies, HTTPS, backups, account lifecycle, privacy requirements, and deployment security.