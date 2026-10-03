-- KampusKonek database: schema + demo data
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < kampuskonek.sql
-- All demo accounts use the password:  Password123!   (change them before any real use)

CREATE DATABASE IF NOT EXISTS kampuskonek_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kampuskonek_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS announcements, tasks, events, org_members, organizations, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- users: one row per account. role = what kind of account it is.
-- the real permissions come from org_members (see below).
-- ---------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('system_admin','adviser','officer','student') NOT NULL DEFAULT 'student',
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- organizations: student councils and student orgs
-- ---------------------------------------------------------------
CREATE TABLE organizations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL UNIQUE,
    acronym     VARCHAR(20)  NOT NULL,
    type        ENUM('council','organization') NOT NULL DEFAULT 'organization',
    description TEXT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- org_members: who holds which seat in which org (the role relationship).
-- every org must keep at least one 'adviser' and one 'co_adviser'.
-- ---------------------------------------------------------------
CREATE TABLE org_members (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    org_id    INT NOT NULL,
    user_id   INT NOT NULL,
    position  ENUM('adviser','co_adviser','president','vice_president','officer','member') NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_seat_per_org (org_id, user_id),
    FOREIGN KEY (org_id)  REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- events: draft -> pending -> approved / disapproved (adviser decides)
-- ---------------------------------------------------------------
CREATE TABLE events (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    org_id         INT NOT NULL,
    title          VARCHAR(150) NOT NULL,
    description    TEXT NULL,
    venue          VARCHAR(150) NOT NULL,
    event_date     DATE NOT NULL,
    start_time     TIME NULL,
    end_time       TIME NULL,
    status         ENUM('draft','pending','approved','disapproved','cancelled') NOT NULL DEFAULT 'draft',
    created_by     INT NOT NULL,
    reviewed_by    INT NULL,
    reviewed_at    DATETIME NULL,
    review_remarks VARCHAR(500) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_org_status (org_id, status),
    INDEX idx_event_date (event_date),
    FOREIGN KEY (org_id)      REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- tasks: belong to an event, given to one person.
-- status          = work progress (todo / in_progress / done)
-- approval_status = adviser's decision on the task request
-- is_restricted   = only admin, advisers and President/VP may change it
-- ---------------------------------------------------------------
CREATE TABLE tasks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    event_id        INT NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT NULL,
    assigned_to     INT NULL,
    priority        ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    deadline        DATE NOT NULL,
    status          ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
    approval_status ENUM('pending','approved','disapproved') NOT NULL DEFAULT 'pending',
    is_restricted   TINYINT(1) NOT NULL DEFAULT 0,
    created_by      INT NOT NULL,
    reviewed_by     INT NULL,
    reviewed_at     DATETIME NULL,
    review_remarks  VARCHAR(500) NULL,
    completed_at    DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_task_event (event_id),
    INDEX idx_task_assignee (assigned_to),
    INDEX idx_task_deadline (deadline),
    FOREIGN KEY (event_id)    REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- announcements: org updates. org_id NULL = campus-wide (admin only)
-- ---------------------------------------------------------------
CREATE TABLE announcements (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    org_id         INT NULL,
    title          VARCHAR(150) NOT NULL,
    body           TEXT NOT NULL,
    status         ENUM('pending','published','rejected') NOT NULL DEFAULT 'pending',
    posted_by      INT NOT NULL,
    reviewed_by    INT NULL,
    reviewed_at    DATETIME NULL,
    review_remarks VARCHAR(500) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_status (status),
    FOREIGN KEY (org_id)      REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (posted_by)   REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===============================================================
-- demo data (dates are relative to the day you import this file)
-- ===============================================================
INSERT INTO users (id, full_name, email, password_hash, role) VALUES
 (1,  'Campus Admin',        'admin@admin.nu-laguna.edu.ph',          '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'system_admin'),
 (2,  'Prof. Elena Ramirez', 'eramirez@admin.nu-laguna.edu.ph',       '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'adviser'),
 (3,  'Mr. Paolo Navarro',   'pnavarro@admin.nu-laguna.edu.ph',       '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'adviser'),
 (4,  'Dr. Marites Santos',  'msantos@admin.nu-laguna.edu.ph',        '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'adviser'),
 (5,  'Ms. Rina Villanueva', 'rvillanueva@admin.nu-laguna.edu.ph',    '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'adviser'),
 (6,  'Daniel Cruz',         '2022100145@admin.nu-laguna-edu.ph',     '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (7,  'Bianca Reyes',        '2022100287@admin.nu-laguna.edu.ph',     '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (8,  'Carlo Mendoza',       '2023100312@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (9,  'Aira Dela Cruz',      '2023100458@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (10, 'Jomar Lim',           '2024100521@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (11, 'Kyla Bautista',       '2024100633@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (12, 'Tessa Garcia',        '2022100999@admin.nu-laguna.edu.ph',     '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (13, 'Miguel Torres',       '2023100777@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (14, 'Nina Castillo',       '2024100810@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'officer'),
 (15, 'Lea Aquino',          '2024100905@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'student'),
 (16, 'Joshua Perez',        '2025100112@students.nu-laguna.edu.ph',  '$2y$10$0Xtd1syFuSQC0mSpkaPbCufpRSNjCue3k3qPxh2u8VMokgjh34KMS', 'student');

INSERT INTO organizations (id, name, acronym, type, description) VALUES
 (1, 'School of Computer Studies Student Council', 'SCS-SC', 'council',
     'The official student council of the School of Computer Studies. We run school-wide events, orientation, and student welfare programs.'),
 (2, 'Dev & Design Society', 'DDS', 'organization',
     'A student organization for aspiring developers and designers. Workshops, jam nights, and project showcases.');

INSERT INTO org_members (org_id, user_id, position) VALUES
 (1, 2,  'adviser'),    (1, 3,  'co_adviser'),
 (1, 6,  'president'),  (1, 7,  'vice_president'),
 (1, 8,  'officer'),    (1, 9,  'officer'),
 (1, 10, 'member'),     (1, 11, 'member'),
 (2, 4,  'adviser'),    (2, 5,  'co_adviser'),
 (2, 12, 'president'),  (2, 13, 'officer'),
 (2, 14, 'member');

INSERT INTO events (id, org_id, title, description, venue, event_date, start_time, end_time, status, created_by, reviewed_by, reviewed_at, review_remarks) VALUES
 (1, 1, 'SCS General Assembly',
    'Semester-opening assembly. We present the council plan, the year calendar, and open the floor for student concerns.',
    'AVR 1', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:00', '17:00', 'approved', 6, 2, NOW(), 'Approved. Please coordinate the sound system with the AV office.'),
 (2, 1, 'Freshmen Orientation',
    'Welcome program for incoming BSCS and BSIT freshmen: campus tour, department introductions, and org booths.',
    'University Gym', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '09:00', '12:00', 'approved', 7, 2, NOW(), NULL),
 (3, 1, 'Sports Fest Kickoff',
    'Opening ceremony and parade of teams for the school sports fest.',
    'Campus Oval', DATE_ADD(CURDATE(), INTERVAL 20 DAY), '08:00', '11:00', 'pending', 8, NULL, NULL, NULL),
 (4, 1, 'Tech Talk: Careers in Software',
    'Alumni speakers share how they started their careers. Still drafting the speaker list.',
    'AVR 2', DATE_ADD(CURDATE(), INTERVAL 28 DAY), '13:00', '15:00', 'draft', 9, NULL, NULL, NULL),
 (5, 1, 'Leadership Training',
    'Half-day training for incoming committee heads.',
    'Room 301', DATE_ADD(CURDATE(), INTERVAL 9 DAY), '13:00', '17:00', 'disapproved', 8, 3, NOW(), 'Venue is not reserved yet. Attach the reservation slip, then resubmit.'),
 (6, 1, 'Club Fair',
    'Booths from every student organization.',
    'Atrium', DATE_SUB(CURDATE(), INTERVAL 6 DAY), '09:00', '16:00', 'approved', 6, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), NULL),
 (7, 2, 'Figma Jam Night',
    'Two-hour design jam. Bring a laptop, we provide the prompts and snacks.',
    'Computer Lab 3', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '18:00', '20:00', 'approved', 12, 4, NOW(), NULL),
 (8, 2, 'Hack Weekend',
    'Weekend build sprint with mentors from the faculty.',
    'Innovation Room', DATE_ADD(CURDATE(), INTERVAL 25 DAY), '09:00', '18:00', 'pending', 13, NULL, NULL, NULL);

INSERT INTO tasks (event_id, title, description, assigned_to, priority, deadline, status, approval_status, is_restricted, created_by, reviewed_by, reviewed_at, completed_at) VALUES
 (1, 'Prepare attendance sheet', 'Print the sheet by section and bring pens.', 9, 'medium', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'done', 'approved', 0, 7, 2, NOW(), NOW()),
 (1, 'Contact guest speakers', 'Confirm the program flow with the invited speakers.', 8, 'high', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'in_progress', 'approved', 0, 6, 2, NOW(), NULL),
 (1, 'Reserve AVR 1 and sound system', 'Venue permit and AV office request. Restricted: needs the President or VP to update.', 7, 'high', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'in_progress', 'approved', 1, 6, 2, NOW(), NULL),
 (1, 'Design the pubmat', NULL, 10, 'medium', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'done', 'approved', 0, 7, 2, NOW(), NOW()),
 (1, 'Print the programs', '100 copies, double-sided.', 10, 'low', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'todo', 'approved', 0, 7, 2, NOW(), NULL),
 (1, 'Snacks budget proposal', 'Quote from two suppliers.', 11, 'medium', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'todo', 'pending', 0, 8, NULL, NULL, NULL),
 (2, 'Coordinate with department heads', 'Get the list of faculty who will speak.', 9, 'medium', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'todo', 'approved', 0, 7, 2, NOW(), NULL),
 (2, 'Prepare welcome kits', 'ID lace, notebook, org flyers.', 11, 'medium', DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'in_progress', 'approved', 0, 6, 2, NOW(), NULL),
 (3, 'Reserve the oval', 'Submit the facility request.', 9, 'high', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'todo', 'pending', 0, 8, NULL, NULL, NULL),
 (6, 'Assign booth numbers', NULL, 8, 'medium', DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'done', 'approved', 0, 6, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
 (6, 'Set up tables and tarps', NULL, 10, 'low', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'done', 'approved', 0, 7, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
 (7, 'Book Computer Lab 3', 'Submit the lab request form.', 13, 'high', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'done', 'approved', 0, 12, 4, NOW(), NOW()),
 (7, 'Write the jam prompts', 'Three prompts, easy to hard.', 14, 'medium', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'in_progress', 'approved', 0, 12, 4, NOW(), NULL),
 (7, 'Pubmat for the jam', NULL, 13, 'medium', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'todo', 'approved', 0, 12, 4, NOW(), NULL);

INSERT INTO announcements (org_id, title, body, status, posted_by, reviewed_by, reviewed_at) VALUES
 (NULL, 'Welcome to KampusKonek', 'Events, tasks, and updates from every council and organization now live in one place. Log in with your school email to see what is coming up.', 'published', 1, NULL, NULL),
 (1, 'Volunteers needed for Freshmen Orientation', 'We need 15 student volunteers for the registration desks and campus tour. Message any SCS officer or sign up at the council office.', 'published', 6, NULL, NULL),
 (1, 'Officer meeting moved to Friday', 'This week\'s officer meeting moves to Friday, 3:00 PM at the council office. Bring your task updates.', 'pending', 8, NULL, NULL),
 (2, 'Figma Jam Night sign-ups are open', 'Slots are limited to 30 students. Bring your own laptop. Beginners are welcome.', 'published', 12, NULL, NULL);
