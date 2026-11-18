# Secure Student Portal

A secure student portal built for the BSIT 309 IT Security termly group project at UPSA. It shows how security controls protect a small PHP and MySQL web application from common attacks.

## Features

- Student registration with a strong password policy
- Login with a 6-digit verification code (MFA)
- Student dashboard: profile, courses and results
- Admin panel: user list and security log
- Lockout after repeated failed logins

## Tech stack

HTML, CSS, JavaScript, PHP and MySQL, running on XAMPP (Apache and MySQL).

## Pages

| File | Purpose |
|---|---|
| `register.php` | Create an account |
| `login.php` | Password step, then verification code |
| `dashboard.php` | Student profile, courses and results |
| `admin.php` | Admin-only users and security log |
| `config.php` | Security settings and helper functions |
| `logout.php` | Ends the session |
| `database.sql` | Creates the database and tables |
| `style.css`, `script.js` | Styling, password meter and tabs |

## Security controls

| Control | How it is done |
|---|---|
| MFA | 6-digit code, stored hashed, expires in 5 minutes |
| Password policy | At least 10 characters with upper case, lower case, a number and a symbol |
| Password storage | bcrypt hashing with `password_hash` |
| SQL injection | PDO prepared statements on every query |
| Encryption at rest | Phone number stored with AES-256-GCM |
| Encryption in transit | HTTPS with a TLS certificate |
| Access control (RBAC) | Student and admin roles, checked on every protected page |
| Brute force and DoS | Lockout after 5 failed attempts per account or 20 per IP in 15 minutes |
| Sessions | Secure, HttpOnly, SameSite cookies, new session ID after login, 15-minute idle timeout |
| Other | CSRF tokens, output encoding against XSS, security headers |
| Logging | Every login, failure, lockout and denied access is saved in `security_logs` |

## Setup (Windows with XAMPP)

1. Install XAMPP and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this project folder into `C:\xampp\htdocs\`. The folder name becomes part of the address.
3. Open `http://localhost/phpmyadmin`, click the **SQL** tab, paste the contents of `database.sql` and click **Go**.
4. Open `config.php` and check that `DB_NAME` matches the database name used in `database.sql`.
5. Change `CHANGE_THIS_SECRET_PHRASE` in `config.php` to your own phrase.
6. Open `https://localhost/<folder-name>/register.php`. The browser warns about the self-signed certificate. Click **Advanced**, then **Continue to localhost**.

## Using the portal

1. Register an account.
2. Log in. The verification code is shown on screen because `DEMO_MODE` is on in `config.php`. A real system would send it by SMS or email.
3. Make an admin by running this in the phpMyAdmin SQL tab, using your Student ID:
   `UPDATE users SET role='admin' WHERE student_id='YOURID';`
4. Reset a lockout while testing:
   `DELETE FROM security_logs WHERE action IN ('LOGIN_FAIL','OTP_FAIL');`

## Attack tests

| Test | Steps | Expected result |
|---|---|---|
| SQL injection | Enter `' OR '1'='1` as the Student ID at login | Login rejected and logged |
| Brute force | Enter a wrong password repeatedly for one Student ID | Lockout message appears |
| Access control | Log in as a student and open `admin.php` | Access denied and logged |
| Man-in-the-middle | Compare `http://` with `https://` and view the `users` table | Padlock shown, phone stored as ciphertext |

## Limitations

- The MFA code is displayed on screen in demo mode.
- Network-level DDoS protection needs a firewall or reverse proxy. The app only limits repeated failed logins.
- JWT, Kerberos and webhooks were not used. Secure sessions handle authentication.
- GitHub stores the source code but cannot run PHP or MySQL, so the portal runs locally on XAMPP.

## Group 2 members

- Priscilla Bedu
- Yolanda Effah-Ntiamaoh
- Dede Demah
- Christabel Owusu
- Wesley Paa Kwaku Owereko Addo