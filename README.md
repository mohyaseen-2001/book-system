# CoworkSpace Reservation System

A PHP and MySQL coworking reservation app with user accounts, time-slot availability, bookings, cancellation, and an administrator dashboard.

## Setup
1. Put the folder in XAMPP htdocs; start Apache and MySQL.
2. New database: import database.sql in phpMyAdmin.
3. Existing database: back it up, then run database-upgrade.sql once. Do not import database.sql over existing data.
4. Check local MySQL credentials at the top of db.php.
5. Visit http://localhost/Coworking%20Space%20Reservation%20System/.

## Admin account
Register through the website, then promote that user in phpMyAdmin:
UPDATE users SET role='admin' WHERE email='your-email@example.com';
There are no seeded demo accounts.

## Rules
Pending and confirmed bookings use slot capacity. Rejected and cancelled bookings do not. Users can reserve a slot once and cancel their own pending/confirmed reservation before it begins. Admins can confirm, reject, and cancel bookings. Slots with active bookings cannot be deleted or rescheduled. Inactive accounts cannot log in.
