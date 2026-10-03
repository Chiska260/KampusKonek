# KampusKonek

A web platform where student councils and organizations post updates, create events, delegate tasks with deadlines, track progress, and run adviser approvals in one place. Built with plain PHP 8.1+, MySQL, HTML, CSS, and JavaScript (no frameworks, no Composer).

## Run it on XAMPP

1. Install XAMPP (PHP 8.1 or newer) and start **Apache** and **MySQL** in the control panel.
2. Copy this whole `kampuskonek` folder into `C:\xampp\htdocs\`.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`), go to **Import**, and choose `database/kampuskonek.sql`. This creates the `kampuskonek_db` database with demo data.
4. If your MySQL `root` user has a password, put it in `config/config.php`.
5. Open `http://localhost/kampuskonek/`.

The app finds its own folder path, so it also works if you rename the folder or use it as a site root.

## Demo accounts

Every demo account uses the password **Password123!** (change them before any real use).

| Role | Email |
|---|---|
| System Admin | admin@admin.nu-laguna.edu.ph |
| Adviser (SCS-SC) | eramirez@admin.nu-laguna.edu.ph |
| Co-Adviser (SCS-SC) | pnavarro@admin.nu-laguna.edu.ph |
| Adviser (DDS) | msantos@admin.nu-laguna.edu.ph |
| President (SCS-SC) | 2022100145@admin.nu-laguna-edu.ph |
| Vice President (SCS-SC) | 2022100287@admin.nu-laguna.edu.ph |
| Officer (SCS-SC) | 2023100312@students.nu-laguna.edu.ph |
| Committee member (SCS-SC) | 2024100521@students.nu-laguna.edu.ph |
| President (DDS) | 2022100999@admin.nu-laguna.edu.ph |
| General student | 2024100905@students.nu-laguna.edu.ph |

Demo event and task dates are relative to the day you import the SQL file, so the dashboards always look current.

## Documents

- `docs/USER_MANUAL.md`: how students, officers, advisers, and admins use the system.
- `docs/DEVELOPER_GUIDE.md`: architecture, role logic, data flow, and the "When You Feel Lost" troubleshooting section.

## Folder map

```
config/       settings (database, email domains)
includes/     all the logic: db, auth, permissions, events, tasks, posts, layout
views/        the four role dashboards
actions/      POST-only handlers (approve, delete, status changes, ...)
assets/       css/style.css and js/app.js
database/     kampuskonek.sql (schema + demo data)
docs/         manuals
*.php         pages (login, dashboard, events, tasks, approvals, ...)
```

## Image rule

The project ships with no image files. Every spot that needs a picture (logos, banners, avatars, event flyers) is marked with `[image here]`, both as an HTML comment and as a dashed placeholder box. Search the project for `[image here]` when you are ready to add real images.
