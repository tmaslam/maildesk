# MailDesk

A Gmail-style shared inbox for multiple brands (1Dollar Digitizing, Aplus Digitizing, Digitizing Zone).

**The one rule this portal enforces:** agents never see a customer's email address â€” not in the inbox,
not in the thread, not even inside the message body (addresses are auto-redacted). Replies are always
sent from the same brand Gmail the customer wrote to, so every brand keeps its own identity.

Everything used here is **free**: Laravel, MySQL (XAMPP) and the Gmail API (no billing needed).

---

## 1. Requirements (already done on this machine)

- XAMPP (Apache + MySQL) with PHP 8.2
- Database `email_portal` (created)
- Migrations + seed data (run)

Default login after seeding:

| | |
|---|---|
| URL | **`http://mailportal.localhost`** (works in Chrome/Edge/Firefox with no extra setup) |
| Fallback URL | `http://localhost/email-setup/public` |
| Username | `admin` (select **Super Admin** on the login screen) |
| Password | `admin123` |

Team members log in with their own username + the **Team** option.

**Demo data:** the inbox ships with 8 dummy conversations so you can preview the design.
Once your real Gmail accounts are connected, remove them with:

```
C:\xampp\php\php.exe artisan tinker --execute="App\Models\EmailThread::where('gmail_thread_id','like','demo-%')->delete();"
```

An Apache VirtualHost for `mailportal.localhost` is configured in
`C:\xampp\apache\conf\extra\httpd-vhosts.conf`. Optional: to also use
`http://mailportal.local`, run Notepad **as administrator**, open
`C:\Windows\System32\drivers\etc\hosts` and add this line:

```
127.0.0.1    mailportal.local
```

> Change this password after first login (Team page â†’ add a new admin with your own email, then remove the default one).

Start **Apache** and **MySQL** from the XAMPP Control Panel before opening the portal.

## 2. Google setup (one time, free â€” no credit card)

1. Go to <https://console.cloud.google.com> and create a project (e.g. "Mail Portal").
2. **APIs & Services â†’ Library** â†’ search **Gmail API** â†’ Enable.
3. **APIs & Services â†’ OAuth consent screen**:
   - User type: **External** â†’ Create.
   - Fill app name ("Mail Portal") and your email. Save.
   - Under **Test users**, add all three brand Gmail addresses
     (the accounts for 1dollardigitizing, aplusdigitizing, digitizingzone).
     *Test mode is fine forever for internal use â€” no verification needed.*
4. **APIs & Services â†’ Credentials â†’ Create credentials â†’ OAuth client ID**:
   - Application type: **Web application**
   - Authorized redirect URI: `http://localhost/email-setup/public/oauth/callback`
   - Create â†’ copy the **Client ID** and **Client secret**.
5. Open `.env` in this folder and fill in:

   ```
   GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxxxxxx
   ```

## 3. Connect the brand mailboxes

1. Log in to the portal as admin â†’ **Mailboxes** (sidebar).
2. The three brands are already listed. For each one, click **Connect Gmail**
   and sign in with that brand's Gmail account. (Google shows an "unverified app"
   warning in test mode â€” click *Continue*; it is your own app.)
3. Add each brand's **reply signature** (brand name + website only â€” no personal info).
4. Back on the inbox, press **Refresh**. The last 30 days of mail imports automatically,
   and the portal auto-checks for new mail every minute while open.

## 4. Add your team

**Team** (sidebar) â†’ add members with role **Agent**.
Agents see customer names and messages only; every email address is hidden/redacted for them.
Admins see everything.

## 5. How replying works

Open a conversation â†’ type in the reply box â†’ **Send**.
The portal sends through the Gmail API **from the same brand account the customer wrote to**,
keeps proper threading (In-Reply-To headers), and appends that brand's signature.

## Notes

- If a page shows a database error, MySQL isn't running â€” start it in XAMPP Control Panel.
- Deleting a brand in Mailboxes also deletes its stored conversations (Gmail itself is untouched).
- To move this to a live server later, update `APP_URL` and `GOOGLE_REDIRECT_URI` in `.env`
  and add the new redirect URI in Google Cloud Console.


