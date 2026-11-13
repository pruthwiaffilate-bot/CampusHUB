# CampusHub

A friendly, role-based college and hostel portal built with PHP and SQLite. It includes a dashboard, student and faculty directories, hostel room and allocation management, student complaints, campus notices, and staff reports.

## Run locally

**Requirements:** PHP 8.1+ with the PDO SQLite extension enabled.

1. Download or clone this project.
2. Open a terminal in the project folder.
3. Start the PHP development server:

   ```bash
   php -S localhost:8000
   ```

4. Visit [http://localhost:8000](http://localhost:8000).

The app creates `storage/campushub.sqlite` and inserts sample records the first time it runs. The `storage/` directory must be writable by PHP. To start over, stop the server, remove that specific SQLite file, and start the server again.

### Demo accounts

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

This local starter uses SQLite so it can run without installing or configuring a separate database server. The project synopsis proposes PHP and MySQL for its eventual deployment; adapting the schema and connection configuration for the institution's MySQL environment is a deployment step. Google Fonts are used when an internet connection is available; the interface remains usable with system font fallbacks.

The application is a project prototype, not a production-ready student information system. Use real institutional data only after reviewing access policies, HTTPS, backups, account lifecycle, privacy requirements, and deployment security.