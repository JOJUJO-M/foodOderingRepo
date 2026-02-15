<?php session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header("Location: dashboard_" . $_SESSION['role'] . ".php");
    exit;
}
elseif (isset($_SESSION['user_id'])) {
    // Session is invalid (missing role), clear it
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misosi Kiganjani - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="glass-panel fade-in">
            <a href="index.php" class="brand" style="justify-content: center; text-decoration: none;">
                🍽️ Misosi Kiganjani
            </a>

            <div id="login-form">
                <h2>Welcome Back</h2>
                <p class="mb-4">Enter your details to access your account</p>

                <form onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="login-email" class="form-control" required placeholder="mimi@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="login-password" class="form-control" required placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>

                    <p class="mt-4" style="font-size: 0.9rem;">
                        Don't have an account? <a href="javascript:void(0)" onclick="toggleAuth()"
                            style="color: var(--primary); font-weight: 600;">Sign Up</a>
                    </p>
                </form>
            </div>

            <div id="register-form" style="display: none;">
                <h2>Create Account</h2>
                <p class="mb-4">Get started with Misosi Kiganjani</p>

                <form onsubmit="handleRegister(event)">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="reg-name" class="form-control" required placeholder="Kijazi">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="reg-email" class="form-control" required placeholder="wewe@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="reg-password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select id="reg-role" class="form-control">
                            <option value="customer">Customer</option>


                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Create Account</button>

                    <p class="mt-4" style="font-size: 0.9rem;">
                        Already have an account? <a href="javascript:void(0)" onclick="toggleAuth()"
                            style="color: var(--primary); font-weight: 600;">Sign In</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function toggleAuth() {
            const login = document.getElementById('login-form');
            const reg = document.getElementById('register-form');
            if (login.style.display === 'none') {
                login.style.display = 'block';
                reg.style.display = 'none';
            } else {
                login.style.display = 'none';
                reg.style.display = 'block';
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            try {
                const res = await apiCall('api/auth.php?action=login', 'POST', { email, password });
                if (res.success) {
                    window.location.href = res.redirect;
                }
            } catch (err) {
                // Error handled in apiCall via alert
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const role = document.getElementById('reg-role').value;

            try {
                const res = await apiCall('api/auth.php?action=register', 'POST', { name, email, password, role });
                if (res.success) {
                    alert('Registration successful! Please login.');
                    toggleAuth();
                }
            } catch (err) { }
        }
    </script>
</body>

</html>