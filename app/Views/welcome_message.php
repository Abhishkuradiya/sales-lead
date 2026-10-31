<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | Lead Sales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style {csp-style-nonce}>
        :root { --ink:#17212b; --muted:#6f7b87; --line:#dfe5eb; --blue:#2563eb; --blue-dark:#1d4ed8; }
        * { box-sizing:border-box; }
        body { min-height:100svh; margin:0; display:grid; place-items:center; padding:20px 14px; color:var(--ink); background:#f4f7fb; font-family:"Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif; }
        .card { width:min(100%,400px); padding:clamp(26px,6vw,40px); background:#fff; border:1px solid #e2e8ef; border-radius:16px; box-shadow:0 12px 36px rgba(24,39,58,.07); }
        .brand { display:flex; align-items:center; gap:10px; margin-bottom:28px; font-size:18px; font-weight:750; }
        .brand-mark { display:grid; width:34px; height:34px; place-items:center; color:#fff; background:var(--blue); border-radius:10px; font-weight:800; }
        h1 { margin:0 0 8px; font-family:Poppins,ui-sans-serif,sans-serif; font-size:clamp(28px,7vw,34px); letter-spacing:-.04em; }
        .intro { margin:0 0 26px; color:var(--muted); font-size:15px; line-height:1.55; }
        .field { margin-bottom:18px; }
        label { display:block; margin-bottom:8px; font-size:14px; font-weight:650; }
        input { width:100%; height:46px; padding:0 13px; color:var(--ink); background:#fbfcfe; border:1px solid var(--line); border-radius:8px; font:inherit; font-size:15px; outline:none; }
        input:focus { background:#fff; border-color:var(--blue); box-shadow:0 0 0 4px rgba(37,99,235,.12); }
        .password { position:relative; }
        .password input { padding-right:58px; }
        .toggle { position:absolute; right:8px; bottom:7px; width:38px; height:32px; padding:0; color:var(--muted); background:transparent; border:0; border-radius:6px; cursor:pointer; font-weight:650; }
        .toggle:hover { color:var(--blue); background:#eef4ff; }
        .button { width:100%; min-height:46px; color:#fff; background:var(--blue); border:0; border-radius:8px; cursor:pointer; font:inherit; font-weight:700; }
        .button:hover { background:var(--blue-dark); }
        .button:disabled { cursor:wait; opacity:.65; }
        .message { display:none; margin:0 0 18px; padding:11px 12px; border-radius:8px; font-size:13px; line-height:1.45; }
        .message.error { display:block; color:#a33a3a; background:#fff1f1; }
        .message.info { display:block; color:#24528a; background:#edf6ff; }
        .hidden { display:none; }
        .back { display:block; margin-top:16px; color:var(--muted); font-size:13px; text-align:center; cursor:pointer; }
        @media (max-width:400px) { .card { padding:24px 20px; } }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand"><span class="brand-mark">L</span><span>Lead Sales</span></div>
        <h1 id="title">Welcome back</h1>
        <p class="intro" id="intro">Sign in to continue managing your leads and sales pipeline.</p>
        <div class="message" id="message" role="alert"></div>

        <form id="loginForm">
            <div id="credentials">
                <div class="field"><label for="email">Email address</label><input id="email" type="email" autocomplete="username" placeholder="you@example.com" required></div>
                <div class="field"><label for="password">Password</label><div class="password"><input id="password" type="password" autocomplete="current-password" placeholder="Enter your password" required><button class="toggle" id="togglePassword" type="button">Show</button></div></div>
                <button class="button" id="loginButton" type="submit">Sign in</button>
            </div>
            <div id="otpStep" class="hidden">
                <div class="field"><label for="otp">Verification code</label><input id="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="Enter 6-digit OTP"></div>
                <button class="button" id="otpButton" type="button">Verify and continue</button>
                <span class="back" id="backButton">Use different login details</span>
            </div>
        </form>
    </main>
    <script {csp-script-nonce}>
        const form = document.getElementById('loginForm');
        const message = document.getElementById('message');
        const credentials = document.getElementById('credentials');
        const otpStep = document.getElementById('otpStep');
        const title = document.getElementById('title');
        const intro = document.getElementById('intro');
        const loginButton = document.getElementById('loginButton');
        const otpButton = document.getElementById('otpButton');
        let authSettings = null;
        let otpSession = '';
        const externalApiBase = 'https://erp.supplyr.in';

        function showMessage(text, type = 'error') {
            message.textContent = text;
            message.className = 'message ' + type;
        }

        function apiMessage(data) {
            return data.message || data.error || (data.errors ? JSON.stringify(data.errors) : JSON.stringify(data));
        }

        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            this.textContent = visible ? 'Show' : 'Hide';
        });

        async function loadAuthSettings() {
            loginButton.disabled = true;
            try {
                const response = await fetch(externalApiBase + '/api/auth/public-settings', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok || data.status === 'error') throw new Error(apiMessage(data));
                authSettings = data;
                loginButton.disabled = false;
            } catch (error) {
                showMessage(error.message);
            }
        }

        loadAuthSettings();

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!authSettings) { showMessage('Authentication settings are not available yet. Please try again.'); return; }
            message.className = 'message';
            loginButton.disabled = true;
            loginButton.textContent = 'Signing in...';
            try {
                const response = await fetch(externalApiBase + '/api/auth/login', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({ email:document.getElementById('email').value.trim(), password:document.getElementById('password').value }) });
                const data = await response.json();
                if (!response.ok || data.status === 'error') throw new Error(apiMessage(data));
                if (data.status === 'otp_required' || data.otp_session) {
                    otpSession = data.otp_session || '';
                    credentials.classList.add('hidden');
                    otpStep.classList.remove('hidden');
                    title.textContent = 'Verify your login';
                    intro.textContent = 'Enter the 6-digit code sent to your registered mobile number.';
                    showMessage(data.message || 'Verification code sent successfully.', 'info');
                    document.getElementById('otp').focus();
                } else {
                    await establishSession(data);
                }
            } catch (error) { showMessage(error.message); }
            loginButton.disabled = false;
            loginButton.textContent = 'Sign in';
        });

        otpButton.addEventListener('click', async function () {
            const otp = document.getElementById('otp').value.trim();
            if (!/^\d{6}$/.test(otp)) { showMessage('Please enter the 6-digit verification code.'); return; }
            otpButton.disabled = true;
            otpButton.textContent = 'Verifying...';
            try {
                const response = await fetch(externalApiBase + '/api/auth/verify-otp', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({ otp_session:otpSession, otp_code:otp }) });
                const data = await response.json();
                if (!response.ok || data.status !== 'success') throw new Error(apiMessage(data));
                await establishSession(data);
            } catch (error) { showMessage(error.message); otpButton.disabled = false; otpButton.textContent = 'Verify and continue'; }
        });

        async function establishSession(data) {
            const profileResponse = await fetch(externalApiBase + '/api/auth/me', { headers: { 'Accept':'application/json', 'Authorization':'Bearer ' + data.token } });
            const profile = await profileResponse.json();
            if (!profileResponse.ok || profile.status !== 'success') throw new Error(apiMessage(profile));
            const sessionResponse = await fetch('/index.php/api/session', { method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({ token:data.token, user:profile.user || data.user || {} }) });
            const session = await sessionResponse.json();
            if (!sessionResponse.ok || session.status !== 'success') throw new Error(apiMessage(session));
            window.location.href = '/index.php/dashboard';
        }

        document.getElementById('backButton').addEventListener('click', function () { window.location.reload(); });
    </script>
</body>
</html>
