# Deploying ZKTendance on CloudPanel

This guide sets up ZKTendance on a server running [CloudPanel](https://www.cloudpanel.io/) (Ubuntu or Debian), with a free SSL certificate, the scheduler, the queue worker and your ZKTeco devices.

In the examples below replace:

| Placeholder | Meaning |
|---|---|
| `attendance.example.com` | your domain or subdomain |
| `zkt` | the site user you create in CloudPanel |
| `zktendance` | database name and database user |
| `your-server-ip` | the public IP of the server |

---

## 1. Before you start

- A server with CloudPanel installed and at least 2 GB RAM.
- A domain or subdomain whose **A record points to the server IP**.
- The repository URL: `https://github.com/nayeemdev/zktendance.git` (for a private repository, add a deploy key to GitHub first, see step 4).
- SMTP details for sending email (Gmail, Zoho, Amazon SES, your hosting mail, and so on).

### Choose how your devices will connect

| Where the server is | Use this mode | Why |
|---|---|---|
| Cloud or VPS (most common) | **Push (ADMS)** | The device calls the server over the internet. No port forwarding needed. |
| A machine inside the office network | Pull or Push | Pull needs the server to reach the device IP on UDP 4370. |
| Cloud, but the device only supports pull | Pull over a **VPN** between office and server | Never expose UDP 4370 of the device to the internet. |

Push mode works with ZKTeco devices that have **Cloud Server Setting / ADMS** in the Comm menu (SpeedFace, ProFace, SenseFace, MB560, UFace800, iClock, newer K and MB models). If your device menu does not have it, you need pull mode.

---

## 2. Create the site

1. Log in to CloudPanel at `https://your-server-ip:8443`.
2. Go to **Sites > Add Site > Create a PHP Site**.
3. Fill in:
   - **Application**: `Laravel 11` (or the newest Laravel option in the list)
   - **Domain Name**: `attendance.example.com`
   - **PHP Version**: `8.4` (8.3 also works)
   - **Site User**: `zkt`, and a strong password
4. Click **Create**.

CloudPanel creates the folder `/home/zkt/htdocs/attendance.example.com` and points the web root to its `public` folder.

---

## 3. Create the database

1. Open the site and go to the **Databases** tab.
2. Click **Add Database**:
   - Database Name: `zktendance`
   - Database User Name: `zktendance`
   - Database User Password: click generate and **copy it**
3. Click **Add Database**.

---

## 4. Upload the code

Connect to the server as the site user (password from step 2):

```bash
ssh zkt@your-server-ip
```

Remove the placeholder files CloudPanel created and clone the project:

```bash
cd ~/htdocs/attendance.example.com
rm -rf ./* ./.[!.]* 2>/dev/null
git clone https://github.com/nayeemdev/zktendance.git .
```

If the repository is private, first create a key and add it in GitHub under **Repository > Settings > Deploy keys**:

```bash
ssh-keygen -t ed25519 -C "zktendance-server" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub
git clone git@github.com:nayeemdev/zktendance.git .
```

Check that PHP has the extensions the app needs:

```bash
php -v
php -m | grep -E "sockets|pdo_mysql|mbstring|gd|zip|intl"
```

All six must be listed. `sockets` is needed only for pull mode devices. If one is missing, ask your server admin to install it as root, for example `apt install php8.4-intl`, then restart PHP-FPM from CloudPanel.

---

## 5. Install and configure

```bash
cd ~/htdocs/attendance.example.com
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env
```

Set at least these values in `.env`:

```env
APP_NAME=ZKTendance
APP_ENV=production
APP_DEBUG=false
APP_URL=https://attendance.example.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zktendance
DB_USERNAME=zktendance
DB_PASSWORD=the-password-from-step-3

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="hr@example.com"
MAIL_FROM_NAME="${APP_NAME}"

ZKTECO_TIMEOUT=10
```

Remove the `#` in front of the `DB_` lines if they are commented out. For Gmail use `smtp.gmail.com`, port `587` and an App Password, not your normal password.

Create the tables and prepare the app:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Do **not** run `php artisan db:seed` on the live server. It creates demo users with the password `password`.

CloudPanel runs PHP as the site user, so `storage` and `bootstrap/cache` are already writable. If you ever see a permission error:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

---

## 6. SSL certificate

1. In CloudPanel open the site and go to **SSL/TLS > Actions > New Let's Encrypt Certificate**.
2. Click **Create and Install**.

Open `https://attendance.example.com`. You should see the **first time setup** page. Fill in company, country (Bangladesh, BDT, Asia/Dhaka), main branch, office hours and the admin account.

---

## 7. Scheduler (required)

The scheduler downloads punches from pull devices, builds attendance, checks devices and credits monthly leave. Without it attendance is not updated.

1. In CloudPanel open the site and go to **Cron Jobs > Add Cron Job**.
2. Fill in:
   - Name: `ZKTendance scheduler`
   - Minute `*`, Hour `*`, Day `*`, Month `*`, Weekday `*`
   - Command:

```
/usr/bin/php8.4 /home/zkt/htdocs/attendance.example.com/artisan schedule:run >> /dev/null 2>&1
```

Use `php8.3` in the path if you picked PHP 8.3. Check the jobs it runs with:

```bash
php artisan schedule:list
```

---

## 8. Queue worker (required for email)

Notification and payslip emails are sent by a queue worker. In-app notifications work without it, emails do not.

### Recommended: Supervisor

Log in as **root** (or a sudo user) and run:

```bash
apt install -y supervisor
nano /etc/supervisor/conf.d/zktendance.conf
```

Paste:

```ini
[program:zktendance-queue]
command=/usr/bin/php8.4 /home/zkt/htdocs/attendance.example.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=zkt
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/home/zkt/htdocs/attendance.example.com/storage/logs/queue.log
stopwaitsecs=3600
```

Start it:

```bash
supervisorctl reread
supervisorctl update
supervisorctl status zktendance-queue
```

The status must say `RUNNING`.

### Without root access

Add a second cron job in CloudPanel that runs every minute:

```
/usr/bin/php8.4 /home/zkt/htdocs/attendance.example.com/artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

---

## 9. Connect the ZKTeco devices

Give every employee a **Device User ID** equal to their user ID (PIN) on the device, or import users from the device later with **ZKTeco Devices > Device Users & Import**.

### Push mode (ADMS)

1. In the app go to **ZKTeco Devices > Add Device**, pick the model, choose **Push**, enter the **serial number** (on the device: System Info > Device Info) and save.
2. On the device open **Comm. > Cloud Server Setting** (on some models **ADMS**) and set:
   - Server Mode: `ADMS`
   - Enable Domain Name: `ON`
   - Server Address: `attendance.example.com`
   - Server Port: `443` with HTTPS `ON`, or `80` with HTTPS `OFF`
   - Proxy: `OFF`
3. Restart the device. Within a minute the device page shows **Online**.

Many older devices cannot use HTTPS. In that case the device must be able to reach `/iclock` over plain HTTP. Test from your computer:

```bash
curl -i "http://attendance.example.com/iclock/cdata?SN=TEST"
```

It must return `200` with the text `OK`. If it returns `301` to https, the site forces HTTPS. In CloudPanel open the site, go to **Vhost**, and replace the HTTPS redirect line (it looks like `if ($scheme != "https") { rewrite ^ https://$host$request_uri permanent; }`) with:

```nginx
set $force_https "";
if ($scheme != "https") { set $force_https "on"; }
if ($request_uri ~ "^/iclock/") { set $force_https ""; }
if ($force_https = "on") { return 301 https://$host$request_uri; }
```

Save. Normal users still get HTTPS, only the device path stays reachable over HTTP.

If a device contacts the server before you add it, its serial number appears in a yellow box on the **ZKTeco Devices** page so you can register it.

### Pull mode (UDP 4370)

1. Give the device a fixed IP, keep **Comm Key = 0** and port **4370**.
2. Make sure the server can reach that IP:
   - Server inside the office network: nothing else is needed.
   - Server in the cloud: connect the office router and the server with a VPN (WireGuard, OpenVPN or your router's site to site VPN) and use the device's VPN or LAN IP.
3. In the app go to **ZKTeco Devices > Add Device**, choose **Pull**, enter the IP and port, save, then press **Test Connection**.

If the device already holds many months of punches, run the first download from SSH instead of the button so it is not cut off by the web timeout:

```bash
cd ~/htdocs/attendance.example.com
php artisan zk:sync
```

After the first download, the scheduler keeps it up to date every 5 minutes.

### Device clock

Punch times come from the device clock. Make sure the device time zone matches the one in **Settings**, then press **Sync Device Time** on the device page.

---

## 10. Go live checklist

Work through these in order on the first day:

1. **Device connects**: Test Connection works (pull) or the device shows Online (push).
2. **Punches arrive**: punch once, open **Device Punches** and check the time and user ID.
3. **Punches are linked**: the punch shows an employee name, not "Not linked".
4. **Attendance appears**: **Daily Attendance** shows the employee (updated every 15 minutes, or press Reprocess).
5. **Email works**: apply for a test leave from an employee login and check the admin receives the email.
6. **Settings are right**: check tax slabs, leave types, salary structure, overtime rule and payroll rules in Settings for your company policy.

If 1 to 4 pass, the rest of the system runs from the same data.

---

## 11. Updating to a new version

```bash
ssh zkt@your-server-ip
cd ~/htdocs/attendance.example.com
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

---

## 12. Backups

Back up both the database and the uploaded files:

- **Database**: CloudPanel **Backups** (remote storage such as S3, Dropbox or SFTP) or a daily cron job with `clpctl db:export --databaseName=zktendance --file=/home/zkt/backups/zktendance.sql.gz` run as root.
- **Files**: `storage/app` (employee documents and company logo) and the `.env` file.

Test a restore at least once.

---

## 13. Troubleshooting

| Problem | What to check |
|---|---|
| White page or "500 Server Error" | `storage/logs/laravel.log`. Run `php artisan optimize:clear` after changing `.env`. |
| Setup page shows again after setup | `APP_URL` and the database settings in `.env`, then `php artisan optimize`. |
| Attendance not updating | Cron job from step 7 exists and uses the right PHP path. Run `php artisan attendance:process` by hand to see errors. |
| Emails not arriving | `supervisorctl status zktendance-queue`, SMTP values in `.env`, `failed_jobs` table (`php artisan queue:failed`). |
| Push device stays Offline | Serial number matches exactly, device can reach the domain, `/iclock` over HTTP returns `OK` (step 9). |
| Pull device "Could not connect" | Server can reach the device IP (VPN), Comm Key is 0, port 4370, `sockets` extension installed. |
| Punches show "Not linked" | Employee **Device User ID** must equal the user ID on the device. Link it in **Device Users & Import**; old punches link automatically. |
| Wrong punch times | Device clock or time zone. Press **Sync Device Time** and check the time zone in Settings. |
| Logo not showing | Run `php artisan storage:link`. |
