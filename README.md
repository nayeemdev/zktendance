# ZKTendance

Office attendance, leave and payroll management for companies using ZKTeco biometric devices. Built with Laravel 13.

## Features

- **First time setup wizard** for company, country, currency (default Bangladesh, BDT), timezone, main branch, office hours and the admin account.
- **Multiple branches**, each with its own weekends, holidays, devices and overtime rule.
- **ZKTeco devices**, any number per branch, in two connection modes:
  - **Pull**: the server connects to the device on UDP port 4370 and downloads punches every 5 minutes, or when you press "Download Punches Now".
  - **Push (ADMS / iClock)**: the device sends punches to the server over HTTP. Best for cloud hosting or remote branches.
  - Test the connection, sync the device time, send employees to the device, view device users, restart, clear logs.
- **Attendance processing** from raw punches: check in and out, worked time, late, early leave, half day, absent, overtime, weekend and holiday work. Night shifts that cross midnight are supported.
- **Shifts** with grace minutes, half day threshold and break time.
- **Shift roster**: assign shifts for date ranges to selected employees, a branch or a department, with optional rotation (for example morning and evening every 7 days). Weekly roster view.
- **Manual attendance and correction requests** with a reason on every change.
- **Leave management**: leave types, yearly balances, carry forward, half day, overlap and balance checks, approval flow. Approved leave updates attendance automatically.
- **Salary structures**: components (Basic, House Rent, Medical, Conveyance, PF and so on) as a % of gross, % of basic or a fixed amount. Salary history with increments.
- **Overtime rules**: minimum minutes, rounding, daily cap, rate base and multipliers. The default follows the Bangladesh Labour Act (Basic / 208 x 2). A rule can require approval, then only overtime approved by HR or the branch manager is paid.
- **Payroll** from attendance: absent and unpaid leave deduction, late deduction (for example 3 lates = 1 day), overtime, bonuses and deductions, loan installments, income tax (TDS) with editable Bangladesh tax slabs, pro rata for new joiners and leavers.
- **Payslips** as PDF, emailed to employees on approval, CSV export for the bank.
- **Reports**: daily attendance, monthly summary, monthly attendance sheet, late and early leave, leave balance, all with CSV export.
- **Employee portal**: today's punches, monthly attendance, leave apply and cancel, correction requests, payslip download, notices.
- **Notifications** in the app (bell icon) and by email: new leave and correction requests for HR, approval results for employees, payslip ready, and device offline alerts for admins.
- **Audit log** (admin only): who created, changed or deleted employees, salaries, manual attendance, leave, loans, payroll, settings, devices and users, with old and new values. Logins are recorded too.
- **Roles**: Admin (everything), HR (everything except settings, branches, devices and users), Branch Manager (dashboard, attendance, leave and corrections of one branch), Employee (portal only).
- **Leave approval** in one step (manager or HR approves) or two steps (manager recommends, HR gives final approval), chosen in Settings.

## Requirements

- PHP 8.3 or newer with the `sockets`, `pdo_mysql` (or `pdo_sqlite`), `mbstring`, `gd` and `zip` extensions
- Composer
- MySQL 8 / MariaDB 10.6, or SQLite for testing

## Installation

```bash
git clone https://github.com/nayeemdev/zktendance.git
cd zktendance
composer install
cp .env.example .env
php artisan key:generate
```

Set the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zktendance
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
php artisan storage:link
php artisan serve
```

Open the site and you will be taken to the setup wizard.

### Demo data

To try the system with sample branches, employees, devices and a month of punches:

```bash
php artisan migrate:fresh --seed
```

| Login | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| HR | hr@example.com | password |
| Employee | employee1@example.com | password |

## Background jobs

Add the Laravel scheduler to cron on the server:

```cron
* * * * * cd /path/to/zktendance && php artisan schedule:run >> /dev/null 2>&1
```

It runs:

| Command | When | Purpose |
|---|---|---|
| `zk:sync` | every 5 minutes | Download punches from pull mode devices |
| `attendance:process` | every 15 minutes | Build attendance for yesterday and today |
| `devices:check` | every 10 minutes | Alert admins when a device stops responding |
| `leave:allocate` | 1 January | Create the new year's leave balances with carry forward |

Run a queue worker so payslip and notification emails are sent:

```bash
php artisan queue:work
```

The commands can also be run by hand, for example `php artisan attendance:process 2026-08-01 2026-08-31`.

## Connecting ZKTeco devices

First give every employee a **Device User ID** that matches their user ID (PIN) on the device.

### Pull mode (K40, MB460, iFace, F18, X628 and most terminals)

1. Give the device a fixed IP on the office network, for example `192.168.1.201`.
2. On the device keep **Comm Key = 0** and UDP port **4370**.
3. In **ZKTeco Devices > Add Device**, pick the model, select Pull and enter the IP.
4. Press **Test Connection**, then **Download Punches Now**.

The server must be able to reach the device, so run it on the office network or connect over a VPN.

### Push mode (SpeedFace, ProFace, SenseFace, MB560, UFace800 and other ADMS devices)

1. Add the device in the app with **Push** mode and its **serial number** (System Info > Device Info).
2. On the device open **Comm. > Cloud Server Setting** and enter your server address and port.
3. The device calls `https://your-server/iclock/cdata` and uploads punches in real time.

Commands such as sending users, restarting and clearing logs are queued and picked up the next time the device checks in. A device that contacts the server before it is registered is listed on the Devices page so you can add it.

## Payroll flow

1. Make sure punches are synced, corrections and leaves are approved, and every employee has a salary.
2. Add one time bonuses or deductions in **Bonus & Deductions**.
3. **Payroll Runs > Run Payroll**, pick the month and optionally a branch. A draft is created.
4. Review, fix data and **Regenerate** as often as needed. Single payslip lines can also be edited, added or removed on the payslip page while the payroll is a draft (regenerating replaces these edits).
5. **Approve**. Loan installments are recorded and payslips become visible to employees (and are emailed if enabled in Settings).
6. **Mark as Paid** after the bank transfer. Use **Export CSV** for the bank sheet.

The income tax slabs are seeded with the Bangladesh slabs for FY 2026-27. Check them against the current Finance Act in **Settings**.

## Project structure

The code follows a plain Laravel flow: **route > controller > form request > service > model > view**.

```
app/
  Http/Controllers/Admin        admin and HR screens
  Http/Controllers/Portal       employee portal
  Http/Controllers/AdmsController.php   ZKTeco push endpoint (/iclock/*)
  Http/Requests                 validation
  Services                      business logic (attendance, leave, payroll, tax, devices)
  Services/Device               ZKTeco pull client behind the DeviceClient interface
  Models
resources/views                 Blade views with Bootstrap 5
routes/web.php                  web routes
routes/adms.php                 device push routes
routes/console.php              schedule
```

## Tests

```bash
php artisan test
```
