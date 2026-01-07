# QuickBooks Custom Fields PHP - Complete Setup Guide

This guide will walk you through setting up the application from scratch. Follow each step carefully.

---

## 📋 Prerequisites Checklist

Before you begin, make sure you have:

- [ ] PHP 8.1 or later installed
- [ ] Composer installed
- [ ] A QuickBooks Developer account
- [ ] A QuickBooks company (Sandbox or Production)
- [ ] Command line/terminal access

### Check Your PHP Version

```bash
php -v
```

You should see PHP 8.1.0 or higher.

### Check Composer

```bash
composer --version
```

If not installed, visit: https://getcomposer.org/download/

---

## 🚀 Step-by-Step Setup

### Step 1: Get the Application

If you haven't already, download or clone the application to your local machine.

```bash
cd /path/to/sampleapp-customfields-php-full
```

---

### Step 2: Install PHP Dependencies

Run Composer to install all required packages:

```bash
composer install
```

This will create a `vendor/` directory with all dependencies.

**Expected output**:
```
Loading composer repositories with package information
Installing dependencies...
...
Generating autoload files
```

---

### Step 3: Create Your Environment File

#### 3.1: Copy the Template

The project includes an `env.example` file with all the configuration you need:

```bash
cp env.example .env
```

This creates a new `.env` file in your project root.

#### 3.2: Verify the File Exists

```bash
ls -la .env
```

You should see something like:
```
-rw-r--r--  1 user  staff  2048 Jan 07 10:30 .env
```

---

### Step 4: Get Your QuickBooks Credentials

#### 4.1: Access QuickBooks Developer Portal

1. Open your browser and go to: **https://developer.intuit.com/**
2. Click **"Sign in"** (top right)
3. Use your Intuit/QuickBooks credentials

#### 4.2: Create or Select an App

**If you don't have an app yet**:
1. Click **"My Apps"** in the dashboard
2. Click **"Create an app"**
3. Choose **"QuickBooks Online and Payments"**
4. Fill in app name and details
5. Click **"Create app"**

**If you already have an app**:
1. Click **"My Apps"** in the dashboard
2. Select your app from the list

#### 4.3: Get Your Keys

1. In your app dashboard, click the **"Keys & OAuth"** tab
2. You'll see two sets of keys:
   - **Sandbox keys** (for testing)
   - **Production keys** (for real data)

**For initial setup, use Sandbox keys**:

```
Sandbox Keys
├── Client ID: ABmRqMvLpDZe7fjKo0p4hxYrBQmVGC6aHd5R8wV3kF
└── Client Secret: wNjRy5xMpQ7tKvL3cH8dF2sG9bX6mZ4n
```

**Important**: Click the 👁️ icon to reveal the Client Secret. Copy it immediately.

#### 4.4: Add Redirect URI

Still in the **"Keys & OAuth"** tab:

1. Scroll down to **"Redirect URIs"** section
2. Click **"Add URI"**
3. Enter: `http://localhost:3000/api/auth/callback`
4. Click **"Save"**

⚠️ **Critical**: The URI must be **exactly** as shown above (including `http://` and the port `:3000`)

---

### Step 5: Configure Your .env File

#### 5.1: Open the .env File

Use your favorite text editor:

```bash
# Using nano
nano .env

# Using vim
vim .env

# Using VSCode
code .env

# Or any text editor
```

#### 5.2: Fill In Your Credentials

Find these lines in the `.env` file:

```env
CLIENT_ID=
CLIENT_SECRET=
REDIRECT_URI=http://localhost:3000/api/auth/callback
```

Replace with your actual values:

```env
CLIENT_ID=ABmRqMvLpDZe7fjKo0p4hxYrBQmVGC6aHd5R8wV3kF
CLIENT_SECRET=wNjRy5xMpQ7tKvL3cH8dF2sG9bX6mZ4n
REDIRECT_URI=http://localhost:3000/api/auth/callback
```

#### 5.3: Verify Other Settings

Make sure these are set (they should already be correct):

```env
ENVIRONMENT=production
APP_FOUNDATIONS_GRAPHQL_URL=https://qb.api.intuit.com/graphql
QBO_BASE_URL=https://quickbooks.api.intuit.com
```

#### 5.4: Save and Close

- **nano**: Press `Ctrl+X`, then `Y`, then `Enter`
- **vim**: Press `Esc`, type `:wq`, press `Enter`
- **Other editors**: File → Save

---

### Step 6: Start the Server

#### 6.1: Run the PHP Development Server

```bash
php -S localhost:3000 -t public
```

#### 6.2: Confirm It's Running

You should see:

```
PHP 8.1.x Development Server (http://localhost:3000) started
```

**Keep this terminal window open** - this is your server running.

---

### Step 7: Test the Application

#### 7.1: Open in Browser

Open your web browser and navigate to:

```
http://localhost:3000
```

#### 7.2: You Should See

- ✅ Page title: "QBO Custom Fields PHP Sample"
- ✅ Section 1: "Connect to QuickBooks"
- ✅ Button: "Sign in with QuickBooks"

#### 7.3: Test Authentication

1. Click **"Sign in with QuickBooks"**
2. You'll be redirected to QuickBooks OAuth page
3. **Sign in** with your QuickBooks account (use sandbox credentials)
4. **Select a company** from the list
5. Click **"Authorize"** to grant permissions
6. You'll be redirected back to `http://localhost:3000`

✅ **Success!** If you return to the app and can see the interface, your setup is complete!

---

## 🎯 Next Steps: Using the Application

### Create Your First Custom Field

1. In **Section 2: Custom fields**, enter:
   - **Label**: `Cost of Fuel`
   - **Data type**: `NUMBER`
2. Click **"Create custom field"**
3. In the response, find and copy the `legacyIDV2` value (e.g., `"1"`)

### Create an Invoice with Custom Field

1. Get a Customer ID and Item ID from your QuickBooks company
2. In **Section 6: Cost of fuel invoice**, enter:
   - **DefinitionId**: The `legacyIDV2` value from above
   - **Customer id**: Your customer ID
   - **Item id**: Your item ID
   - **Fuel cost**: `50.00`
3. Click **"Create invoice with Cost of Fuel"**

### Verify in QuickBooks

1. Log in to your QuickBooks company
2. Go to **Sales** → **Invoices**
3. Open the invoice you just created
4. You should see the "Cost of Fuel" custom field with value `50.00`

---

## 🔍 Verification Checklist

Use this checklist to verify your setup:

### Files
- [ ] `composer.json` exists in project root
- [ ] `vendor/` directory exists (from `composer install`)
- [ ] `.env` file exists in project root
- [ ] `.env` file contains your CLIENT_ID and CLIENT_SECRET

### Configuration
- [ ] CLIENT_ID is filled in (not empty)
- [ ] CLIENT_SECRET is filled in (not empty)
- [ ] REDIRECT_URI is `http://localhost:3000/api/auth/callback`
- [ ] ENVIRONMENT is `production`

### QuickBooks Portal
- [ ] App created in QuickBooks Developer Portal
- [ ] Sandbox keys copied to .env
- [ ] Redirect URI `http://localhost:3000/api/auth/callback` added
- [ ] Redirect URI saved in portal

### Server
- [ ] PHP server running on port 3000
- [ ] Can access http://localhost:3000 in browser
- [ ] "Sign in with QuickBooks" button visible

### Authentication
- [ ] Clicking "Sign in" redirects to QuickBooks
- [ ] Can successfully authorize the app
- [ ] Redirected back to app after authorization
- [ ] No error messages displayed

---

## 🚨 Common Setup Problems

### Problem: `composer: command not found`

**Solution**: Install Composer from https://getcomposer.org/download/

```bash
# macOS/Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Or use Homebrew (macOS)
brew install composer
```

### Problem: `composer install` fails with PHP version error

**Solution**: Update PHP to 8.1 or later

```bash
# Check current version
php -v

# macOS (using Homebrew)
brew install php@8.1

# Ubuntu/Debian
sudo apt-get install php8.1
```

### Problem: `.env` file not found

**Solution**:

```bash
# Make sure you're in the project root
pwd

# Should show: /path/to/sampleapp-customfields-php-full

# Copy the example file
cp env.example .env

# Verify it exists
ls -la .env
```

### Problem: "Address already in use" when starting server

**Solution**: Port 3000 is already in use. Either:

1. **Stop the other service using port 3000**, or
2. **Use a different port**:

```bash
php -S localhost:3001 -t public
```

Then update your REDIRECT_URI to `http://localhost:3001/api/auth/callback`

### Problem: "Invalid client" error

**Solution**:

1. **Check for typos** in CLIENT_ID and CLIENT_SECRET
2. **Ensure no extra spaces** before or after the values
3. **Verify you're using Sandbox keys** for sandbox testing
4. **Check keys match** in QuickBooks Developer Portal

### Problem: "Redirect URI mismatch"

**Solution**:

1. **In .env file**: `REDIRECT_URI=http://localhost:3000/api/auth/callback`
2. **In QuickBooks portal**: Must have `http://localhost:3000/api/auth/callback` in the Redirect URIs list
3. **Must be exact match** (including http:// and port)
4. **Restart PHP server** after making changes

### Problem: Can't connect to QuickBooks Sandbox

**Solution**:

1. Make sure you have a **Sandbox company** created
2. Go to https://developer.intuit.com/app/developer/sandbox
3. Click **"Add company"** if you don't have one
4. Use **Sandbox credentials** (not production)

---

## 🎓 Understanding Your Setup

### File Structure

```
sampleapp-customfields-php-full/
├── .env                    ← Your secrets (not in git)
├── env.example             ← Template with documentation
├── composer.json           ← PHP dependencies
├── vendor/                 ← Installed packages (not in git)
├── public/
│   └── index.php          ← Application entry point
└── src/                   ← Application code
```

### What Happens When You Sign In?

```
1. You click "Sign in with QuickBooks"
   ↓
2. App redirects to QuickBooks with CLIENT_ID
   ↓
3. You authorize the app
   ↓
4. QuickBooks redirects back to REDIRECT_URI
   ↓
5. App receives authorization code
   ↓
6. App exchanges code for access token
   ↓
7. Access token stored in memory
   ↓
8. You can now make API calls
```

### Environment Variables Explained

| Variable | Purpose | Required |
|----------|---------|----------|
| `CLIENT_ID` | Identifies your app to QuickBooks | ✅ Yes |
| `CLIENT_SECRET` | Authenticates your app | ✅ Yes |
| `REDIRECT_URI` | Where QuickBooks sends users back | ✅ Yes |
| `ENVIRONMENT` | API environment setting | ✅ Yes |
| `APP_FOUNDATIONS_GRAPHQL_URL` | GraphQL endpoint | Optional |
| `QBO_BASE_URL` | REST API endpoint | Optional |

---

## 📚 Additional Resources

### Official Documentation
- QuickBooks Developer: https://developer.intuit.com/
- OAuth 2.0 Guide: https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
- Custom Fields: https://developer.intuit.com/app/developer/qbo/docs/workflows/create-custom-fields/get-started

### Project Documentation
- [`README.md`](./README.md) - Main documentation
- [`QUICK_START.md`](./QUICK_START.md) - Quick reference
- [`IMPLEMENTATION_SUMMARY.md`](./IMPLEMENTATION_SUMMARY.md) - Technical details

---

## ✅ Setup Complete!

If you've followed all steps and verified the checklist items, your application is now ready to use!

**Next Steps**:
1. Explore the web interface at http://localhost:3000
2. Read the [`QUICK_START.md`](./QUICK_START.md) for usage examples
3. Try creating custom fields and applying them to entities

**Need Help?**
- Check the Troubleshooting section in [`README.md`](./README.md)
- Review [`SYSTEM_TEST_RESULTS.md`](./SYSTEM_TEST_RESULTS.md) for expected behavior

---

**Setup Date**: _______________  
**Completed By**: _______________  
**Environment**: ☐ Sandbox  ☐ Production  
**Status**: ☐ Complete  ☐ In Progress  ☐ Issues
