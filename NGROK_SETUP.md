# ngrok Setup Guide - Expose Local Server for QuickBooks OAuth

## 📖 What is ngrok and Why Do You Need It?

**ngrok** creates a secure tunnel from a public URL to your local development server. This is essential for QuickBooks OAuth because:

- ✅ QuickBooks needs to redirect to a **publicly accessible URL** after authentication
- ✅ `localhost` URLs don't work for OAuth callbacks from QuickBooks
- ✅ ngrok provides a temporary public URL that tunnels to your local server

**When to use ngrok**:
- 🔧 **Local development** without a public server
- 🧪 **Testing OAuth flows** on your machine
- 👥 **Sharing your local app** with team members

**When you DON'T need ngrok**:
- ✅ Your app is deployed on a public server (Heroku, AWS, etc.)
- ✅ You have a public domain pointing to your development server

---

## 🚀 Quick Setup

### Step 1: Install ngrok

Choose your platform:

#### macOS (Homebrew)
```bash
# Install ngrok
brew install --cask ngrok

# Verify installation
ngrok version
```

#### Windows (winget)
```bash
# Install ngrok
winget install Ngrok.Ngrok

# Verify installation
ngrok version
```

#### Linux (Snap)
```bash
# Install ngrok (if Snap is available)
sudo snap install ngrok

# Verify installation
ngrok version
```

#### Alternative: Manual Download
If package managers don't work, download directly:
1. Visit: https://ngrok.com/download
2. Download for your platform
3. Extract and place the binary in your PATH
4. Verify: `ngrok version`

---

### Step 2: Get Your ngrok Auth Token

1. **Sign up** at https://dashboard.ngrok.com/signup (free account)
2. **Go to**: https://dashboard.ngrok.com/get-started/your-authtoken
3. **Copy** your authtoken (looks like: `2abc123def456ghi789jkl012mno345pqr678stu`)

---

### Step 3: Configure ngrok

```bash
# Add your authtoken to ngrok
ngrok config add-authtoken <your-token>
```

Replace `<your-token>` with your actual token:
```bash
# Example
ngrok config add-authtoken 2abc123def456ghi789jkl012mno345pqr678stu
```

You should see:
```
Authtoken saved to configuration file: /Users/yourname/.ngrok2/ngrok.yml
```

---

### Step 4: Start Your PHP Server

**Important**: Use port **5001** (or any port you prefer, just be consistent):

```bash
# Navigate to your project
cd /path/to/sampleapp-customfields-php-full

# Start PHP server on port 5001
php -S localhost:5001 -t public
```

Keep this terminal window open.

---

### Step 5: Start ngrok Tunnel

Open a **new terminal window** and run:

```bash
# Create tunnel to port 5001
ngrok http 5001
```

You'll see output like this:

```
ngrok                                                           

Session Status                online
Account                       Your Name (Plan: Free)
Version                       3.x.x
Region                        United States (us)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123def456.ngrok-free.app -> http://localhost:5001

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

**Important**: Copy the **Forwarding URL** (e.g., `https://abc123def456.ngrok-free.app`)

---

### Step 6: Update Your .env File

Edit your `.env` file:

```env
# OLD (localhost - doesn't work with QuickBooks OAuth)
REDIRECT_URI=http://localhost:5001/api/auth/callback

# NEW (ngrok URL - works with QuickBooks OAuth)
REDIRECT_URI=https://abc123def456.ngrok-free.app/api/auth/callback
```

**Replace** `abc123def456.ngrok-free.app` with your actual ngrok URL.

---

### Step 7: Update QuickBooks Developer Portal

1. Go to: https://developer.intuit.com/app/developer/myapps
2. Select your app
3. Go to: **Keys & OAuth** tab
4. Scroll to: **Redirect URIs**
5. Add your ngrok URL:
   ```
   https://abc123def456.ngrok-free.app/api/auth/callback
   ```
6. Click **Save**

---

### Step 8: Restart Your PHP Server

After updating `.env`:

```bash
# Stop the server (Ctrl+C)
# Start again
php -S localhost:5001 -t public
```

---

### Step 9: Test OAuth

1. Open your ngrok URL in browser:
   ```
   https://abc123def456.ngrok-free.app
   ```

2. Click **"Sign in with QuickBooks"**

3. Authorize the app

4. You should be redirected back to your app successfully! ✅

---

## 🎯 Complete Workflow

```
┌─────────────────────────────────────────────────────────────┐
│  Your Machine                                                │
│  ┌────────────────────┐         ┌─────────────────────┐    │
│  │ PHP Server         │         │ ngrok Tunnel        │    │
│  │ localhost:5001     │◄────────│ Port 5001           │    │
│  └────────────────────┘         └─────────────────────┘    │
└──────────────────────────────────────│──────────────────────┘
                                       │
                                       │ HTTPS Tunnel
                                       │
┌──────────────────────────────────────▼──────────────────────┐
│  Internet                                                    │
│  Public URL: https://abc123def456.ngrok-free.app           │
└──────────────────────────────────────┬──────────────────────┘
                                       │
                                       │ OAuth Redirect
                                       │
┌──────────────────────────────────────▼──────────────────────┐
│  QuickBooks OAuth Server                                     │
│  - Authenticates user                                        │
│  - Redirects to: https://abc123def456.ngrok-free.app/...   │
└──────────────────────────────────────────────────────────────┘
```

---

## 📋 Quick Reference Commands

### Setup (One-time)
```bash
# Install ngrok (choose your platform)
brew install --cask ngrok           # macOS
winget install Ngrok.Ngrok          # Windows
sudo snap install ngrok             # Linux

# Configure authtoken
ngrok config add-authtoken <your-token>
```

### Daily Development
```bash
# Terminal 1: Start PHP server
cd /path/to/sampleapp-customfields-php-full
php -S localhost:5001 -t public

# Terminal 2: Start ngrok
ngrok http 5001

# Copy ngrok URL and update .env
# Update QuickBooks portal with ngrok URL
# Restart PHP server
# Test OAuth flow
```

---

## ⚠️ Important Notes

### Free Tier Limitations

**ngrok Free Plan**:
- ✅ HTTPS tunnel
- ✅ Random subdomain
- ⚠️ **URL changes every time** you restart ngrok
- ⚠️ Limited sessions per minute

**What this means**:
- You must update `.env` and QuickBooks portal **every time** you restart ngrok
- Your ngrok URL from yesterday won't work today

**Solution for permanent URL**:
- Upgrade to ngrok paid plan ($10/month) for reserved domains
- Or deploy to a public server for production use

---

### Security Best Practices

1. **Never commit ngrok URLs** to version control
2. **Don't use ngrok in production** (use proper hosting)
3. **Rotate your authtoken** if accidentally exposed
4. **Use HTTPS** ngrok URLs (not HTTP)
5. **Monitor ngrok web interface** at http://127.0.0.1:4040

---

## 🔧 Troubleshooting

### Problem: "ERR_NGROK_3200" or session limit exceeded

**Solution**: 
- Free tier has rate limits
- Wait a few minutes
- Or upgrade to paid plan

### Problem: ngrok URL doesn't load

**Solutions**:
1. Check PHP server is running (`php -S localhost:5001 -t public`)
2. Verify ngrok is tunneling to correct port (`ngrok http 5001`)
3. Check firewall isn't blocking ngrok

### Problem: "Redirect URI mismatch" after updating

**Solutions**:
1. **Verify** ngrok URL in `.env` matches QuickBooks portal exactly
2. **Include** `/api/auth/callback` at the end
3. **Restart** PHP server after updating `.env`
4. **Clear** browser cache

### Problem: ngrok command not found

**Solutions**:
```bash
# macOS: Ensure Homebrew installed ngrok to PATH
which ngrok

# If not found, reinstall:
brew uninstall ngrok
brew install --cask ngrok

# Or add to PATH manually
export PATH=$PATH:/path/to/ngrok
```

### Problem: New ngrok URL every restart

**This is normal for free tier!**

**Solutions**:
1. **Accept it**: Update `.env` and QuickBooks portal each time
2. **Paid plan**: Get reserved domain for $10/month
3. **Production**: Deploy to public server

---

## 🎓 Advanced Usage

### Custom Subdomain (Paid Plan Only)

```bash
# Reserve subdomain in ngrok dashboard first
ngrok http 5001 --subdomain=myapp-dev
```

Your URL will always be: `https://myapp-dev.ngrok.app`

### ngrok Web Interface

While ngrok is running, open: **http://127.0.0.1:4040**

Features:
- 🔍 View all HTTP requests
- 📊 Request/response details
- 🔄 Replay requests
- 📈 Traffic statistics

### Configuration File

Edit `~/.ngrok2/ngrok.yml` for advanced settings:

```yaml
version: "2"
authtoken: your_token_here
tunnels:
  php-app:
    proto: http
    addr: 5001
    inspect: true
```

Then run: `ngrok start php-app`

---

## 📚 Additional Resources

### Official Documentation
- ngrok Downloads: https://ngrok.com/download
- ngrok Documentation: https://ngrok.com/docs
- ngrok Dashboard: https://dashboard.ngrok.com/

### QuickBooks Integration
- OAuth 2.0 Guide: https://developer.intuit.com/app/developer/qbo/docs/develop/authentication-and-authorization/oauth-2.0
- Redirect URIs: Must be publicly accessible HTTPS URLs

---

## ✅ Verification Checklist

Setup Complete When:
- [ ] ngrok installed and version shows correctly
- [ ] Authtoken configured (`ngrok config add-authtoken`)
- [ ] PHP server running on port 5001
- [ ] ngrok tunnel running (`ngrok http 5001`)
- [ ] ngrok URL copied from terminal
- [ ] `.env` updated with ngrok URL
- [ ] QuickBooks portal updated with ngrok URL
- [ ] PHP server restarted
- [ ] Can access app via ngrok URL in browser
- [ ] OAuth "Sign in with QuickBooks" works
- [ ] Successfully redirected back after authorization

---

## 🎯 Summary

**ngrok enables QuickBooks OAuth for local development by:**
1. Creating a public HTTPS URL
2. Tunneling traffic to your localhost
3. Allowing QuickBooks to redirect back to your app

**Remember**:
- ✅ Free tier URL changes each restart
- ✅ Update `.env` and QuickBooks portal each time
- ✅ Don't use in production (deploy to public server)
- ✅ Monitor traffic at http://127.0.0.1:4040

**For Production**:
- Deploy to Heroku, AWS, DigitalOcean, or similar
- Use a proper domain with SSL certificate
- No need for ngrok in production!

---

**Happy Developing!** 🚀
