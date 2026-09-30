# Deploying Opsyra (test) with cPanel Git deployment

Two addresses on BrainStormers' cPanel server, one repository, one pipeline:

| Address | What it serves | Document root |
|---|---|---|
| `opsyra.brainstormertech.com` | Opsyra Central — website, pricing, sign-up, super admin | `/home/<user>/opsyra/central/current/public` |
| `us.opsyra.brainstormertech.com` | The Opsyra app (Vue) and its API — the US deployment | `/home/<user>/opsyra/us/current/public` |

Later countries (`uk.`, `om.`, `pk.`) are the same code with different
settings — same repository, one more folder and subdomain each.

## How a change reaches the server

```
push to GitHub (main/master)
  └─ GitHub Actions: all tests (real PostgreSQL) ── fail → stops here
       └─ build the bundle (deploy/build-bundle.sh): PHP packages, Vue app,
          Central pages — nothing is built on the server
            ├─ publish it as the `deploy` branch on GitHub
            └─ push it to the cPanel repository  ─→  cPanel runs .cpanel.yml
                                                      └─ deploy.sh, per app:
                                                         new release folder
                                                         → preflight (safety)
                                                         → migrate database
                                                         → preflight --tables
                                                         → caches
                                                         → switch `current` (atomic)
```

A failed check stops the deploy **before** the live site changes. Each
deploy is kept in `~/opsyra/<app>/releases/`; rolling back is one command
(below).

---

## One-time setup

### 1. GitHub

1. Create a **private** repository, e.g. `brainstormertech/opsyra`.
2. From this PC (the code is currently only here):
   ```bash
   git remote add origin git@github.com:brainstormertech/opsyra.git
   git push -u origin master
   ```
3. The first push runs the tests (Actions tab). Deploying needs the cPanel
   side first — until its secrets are set, the pipeline builds and publishes
   the `deploy` branch but skips the server.

### 2. DNS (at the domain registrar)

Two **A records** pointing at the server's IP:

| Host | Type | Value |
|---|---|---|
| `opsyra` | A | `<server IP>` |
| `us.opsyra` | A | `<server IP>` |

### 3. cPanel — PHP, PostgreSQL, email

1. **PHP 8.2 or newer** (8.3 recommended) with the extensions `pdo_pgsql`,
   `pgsql`, `bcmath`, `gd`, `intl`, `mbstring` (cPanel → *MultiPHP Manager* /
   *Select PHP Version*). Note the command-line PHP path for step 6 — e.g.
   `/opt/cpanel/ea-php83/root/usr/bin/php`.
2. **PostgreSQL 12 or newer** (cPanel → *PostgreSQL Databases*; if missing,
   it's enabled in WHM). Create:
   - databases `opsyra_us` and `opsyra_central` (cPanel adds your prefix);
   - one user `opsyra`, added to **both** databases with all privileges.

   cPanel's database users are ordinary users — exactly what Opsyra needs.
   **Never** use the `postgres` superuser: it silently switches off the
   separation between companies. The deploy's preflight check refuses to
   run if it detects this.
3. **Email** goes through **Google Workspace's SMTP relay**: free with the
   existing Workspace, and brainstormertech.com's mail is on Google anyway
   (a cPanel mailbox would land in spam; see `docs/status.md`). Emails are
   real, so test only with addresses you own.
   - Google Admin → Apps → Google Workspace → Gmail → Routing → *SMTP
     relay service*: allowed senders "Only addresses in my domains"; "Only
     accept mail from the specified IP addresses" = the server's IP (`/32`);
     no SMTP authentication; "Require TLS encryption".
   - WHM → Security Center → **SMTP Restrictions must be Disabled**. When
     it's on, connections to Google on 25/465/587 are silently handed to
     the server's own Exim instead ("certificate verify failed",
     certificate `CN = server1…`).
   - DNS (Namecheap): SPF `v=spf1 include:_spf.google.com ~all`; Google
     DKIM (`google._domainkey`, generated in Gmail → *Authenticate email*);
     `_dmarc` `v=DMARC1; p=none`.
   - Test from the server:
     `php artisan tinker --execute="Mail::raw('test', fn (\$m) => \$m->to('you@example.com')->subject('test'));"`

### 4. cPanel — the two subdomains

cPanel → *Domains* → *Create A New Domain*:

- `opsyra.brainstormertech.com` → document root
  `opsyra/central/current/public` (uncheck "share document root")
- `us.opsyra.brainstormertech.com` → document root
  `opsyra/us/current/public`

cPanel creates those folders; the first deploy replaces `current` with a
link (it moves cPanel's placeholder aside). SSL certificates come from
AutoSSL once DNS resolves — check cPanel → *SSL/TLS Status*.

### 5. Settings files

Create the two settings files the deploy links into every release (cPanel
→ *File Manager*, or Terminal):

- `~/opsyra/us/shared/.env` — from `deploy/us.env.example`
- `~/opsyra/central/shared/.env` — from `deploy/central.env.example`

Fill in every `<…>`. Generate the values in cPanel → *Terminal*:

```bash
# An APP_KEY for each app (different for each; keep them — changing the US
# one makes stored bank details unreadable):
php -r 'echo "base64:".base64_encode(random_bytes(32)), PHP_EOL;'

# The shared secret: the SAME value goes in the US OPSYRA_CENTRAL_SECRET
# and Central's OPSYRA_US_SECRET:
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Protect them: `chmod 600 ~/opsyra/*/shared/.env`.

Optional `~/.opsyra-deploy.env` if the command-line `php` isn't 8.2+:

```bash
PHP_BIN=/opt/cpanel/ea-php83/root/usr/bin/php
```

### 6. Cron jobs (cPanel → *Cron Jobs*, "Once per minute")

Replace `php` with your PHP path if needed.

```
* * * * *  cd ~/opsyra/us/current && php artisan schedule:run >> /dev/null 2>&1
* * * * *  cd ~/opsyra/us/current && php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
* * * * *  cd ~/opsyra/central/current && php artisan schedule:run >> /dev/null 2>&1
* * * * *  cd ~/opsyra/central/current && php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
```

The scheduler runs attendance close-out (hourly), visit generation and
staff activation (daily) and the nightly demo rebuild (03:00 UTC). The
queue worker runs pay runs — they start within a minute.

### 7. cPanel Git repository (where the pipeline pushes)

1. cPanel → *Git™ Version Control* → *Create*. **Turn off "Clone a
   Repository"** (a cPanel-managed repository is what makes a push deploy
   automatically). Path: `/home/<user>/repositories/opsyra-deploy`,
   name `opsyra-deploy`. Copy its **Clone URL**
   (`ssh://<user>@<host>:<port>/home/<user>/repositories/opsyra-deploy`).
2. In cPanel → *Terminal*, point it at the branch the pipeline pushes:
   ```bash
   git -C ~/repositories/opsyra-deploy symbolic-ref HEAD refs/heads/deploy
   ```
3. A key for GitHub to push with: cPanel → *SSH Access* → *Manage SSH Keys*
   → *Generate a New Key* (no passphrase) → **Authorize** it → view/download
   the **private** key.
4. The server's host key, from this PC:
   `ssh-keyscan -p <port> <host>`.
5. GitHub → repository → *Settings* → *Secrets and variables* → *Actions* →
   new repository secrets:

   | Secret | Value |
   |---|---|
   | `CPANEL_GIT_URL` | the Clone URL from step 1 |
   | `CPANEL_SSH_KEY` | the private key from step 3 |
   | `CPANEL_KNOWN_HOSTS` | the output of step 4 |

**Without letting GitHub push to the server** (the alternative): create the
cPanel repository by *cloning* the GitHub repository's `deploy` branch
(needs a GitHub deploy key), then after each green pipeline click *Manage →
Pull or Deploy → Update from Remote* and *Deploy HEAD Commit*.

---

## First deploy

1. Push to GitHub (or re-run the last workflow: Actions → CI/CD → *Run
   workflow*). Wait for green.
2. The deploy log: cPanel → *Git Version Control* → *Manage* → *Pull or
   Deploy* (last deployment), or `~/.cpanel/logs/` in Terminal.
3. One-off commands in cPanel → *Terminal*:
   ```bash
   cd ~/opsyra/central/current && php artisan central:admin you@example.com --name="Your Name"   # the super admin login (prints a password — store it safely; run again to reset it)
   cd ~/opsyra/us/current && php artisan demo:reset           # the public demo company (then rebuilt nightly)
   ```
4. Check:
   - `https://opsyra.brainstormertech.com` — the website; *Try the demo*.
   - `https://us.opsyra.brainstormertech.com` — the sign-in page.
   - `cd ~/opsyra/us/current && php artisan opsyra:preflight --tables` — all ✓.
   - Sign up for a free trial on the website with an address you own; the
     welcome email should arrive.

## Every deploy after that

Push to `main`/`master`. That's it.

## Rolling back

```bash
ls -1t ~/opsyra/us/releases/            # newest first
ln -sfn ~/opsyra/us/releases/<previous> ~/opsyra/us/current
cd ~/opsyra/us/current && php artisan queue:restart
```

(Database migrations are not undone by this. Opsyra's migrations add
things rather than remove them, so an earlier release keeps working.)

## Good to know

- **Maps** use OpenStreetMap's free tiles — fine for testing; a busy live
  site should set `VITE_MAP_TILE_URL` (a GitHub Actions *variable*) to a
  paid tile provider.
- **No Redis needed**: sessions, cache and the queue use the database.
- **Demo company**: public on purpose, rebuilt nightly; its sign-in is on
  the website. Its password reset never sends email.
- **Logs**: `~/opsyra/<app>/shared/storage/logs/`.
