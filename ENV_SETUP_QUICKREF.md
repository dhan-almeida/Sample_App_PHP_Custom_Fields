# Environment Setup - Quick Reference Card

## ⚡ Quick Setup (3 Minutes)

```bash
# 1. Copy environment template
cp env.example .env

# 2. Edit with your credentials
nano .env  # or use any text editor

# 3. Start server
php -S localhost:3000 -t public

# 4. Open browser
open http://localhost:3000
```

---

## 🔑 Required Credentials

Get from: https://developer.intuit.com/app/developer/myapps

```env
CLIENT_ID=your_client_id_here
CLIENT_SECRET=your_client_secret_here
REDIRECT_URI=http://localhost:3000/api/auth/callback
```

---

## 📍 Where to Find Things

### In QuickBooks Developer Portal
```
Dashboard → My Apps → [Your App] → Keys & OAuth
├── Sandbox Keys (for testing)
│   ├── Client ID ─────────┐
│   └── Client Secret ─────┤
└── Production Keys         │
    ├── Client ID           ├─→ Copy to .env file
    └── Client Secret ──────┘

Redirect URIs Section
└── Add: http://localhost:3000/api/auth/callback
```

### In Your Project
```
sampleapp-customfields-php-full/
├── env.example     ← Template (read this)
├── .env            ← Your file (create this)
└── public/
    └── index.php   ← Server entry point
```

---

## ✅ Setup Checklist

- [ ] Composer installed (`composer --version`)
- [ ] PHP 8.1+ installed (`php -v`)
- [ ] `env.example` copied to `.env`
- [ ] CLIENT_ID added to `.env`
- [ ] CLIENT_SECRET added to `.env`
- [ ] Redirect URI added in QuickBooks portal
- [ ] Server started (`php -S localhost:3000 -t public`)
- [ ] Browser opened (`http://localhost:3000`)
- [ ] "Sign in with QuickBooks" clicked
- [ ] Authorization successful

---

## 🚨 Common Fixes

| Problem | Fix |
|---------|-----|
| "Invalid client" | Check CLIENT_ID and CLIENT_SECRET for typos |
| "Redirect URI mismatch" | Must be `http://localhost:3000/api/auth/callback` exactly |
| "Command not found" | Install Composer: https://getcomposer.org/ |
| "Address already in use" | Port 3000 taken, use: `php -S localhost:3001 -t public` |
| Variables not loading | Restart PHP server after editing `.env` |

---

## 📝 Example .env File

```env
# QuickBooks OAuth 2.0 Credentials
CLIENT_ID=ABmRqMvLpDZe7fjKo0p4hxYrBQmVGC6aHd5R8wV3kF
CLIENT_SECRET=wNjRy5xMpQ7tKvL3cH8dF2sG9bX6mZ4n
REDIRECT_URI=http://localhost:3000/api/auth/callback

# Environment
ENVIRONMENT=production

# API Endpoints (defaults)
APP_FOUNDATIONS_GRAPHQL_URL=https://qb.api.intuit.com/graphql
QBO_BASE_URL=https://quickbooks.api.intuit.com
```

---

## 🎯 Sandbox vs Production

### Testing with Sandbox
```
✅ Use: Sandbox Keys
✅ Data: Fake/Test data only
✅ Safe: Can't affect real data
✅ URL: Same as production
```

### Using Production
```
⚠️ Use: Production Keys
⚠️ Data: Real business data
⚠️ Risk: Changes affect real company
⚠️ URL: Same as sandbox
```

---

## 🔐 Security Reminders

- ✅ `.env` is in `.gitignore` (won't be committed)
- ✅ Never share your `CLIENT_SECRET`
- ✅ Never commit `.env` to version control
- ✅ Use environment variables in production
- ✅ Rotate secrets regularly

---

## 📞 Need More Help?

- **Full Setup Guide**: [`SETUP_GUIDE.md`](./SETUP_GUIDE.md)
- **Troubleshooting**: [`README.md`](./README.md#-troubleshooting)
- **QuickBooks Help**: https://help.developer.intuit.com/

---

**Print this page** and keep it handy while setting up! 📄
