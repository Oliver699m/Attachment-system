<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle logged out message
if (isset($_GET['logged_out'])) {
    $success = "Logged out successfully. Please sign in.";
}

if (($_POST['action'] ?? '') === 'signup') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = $_POST['department'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($department) || strlen($password) < 6) {
        $error = "All fields are required. Password must be at least 6 characters.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, department, role) VALUES (?, ?, ?, ?, 'intern')");
            if ($stmt->execute([$username, $email, $hashed_password, $department])) {
                $success = "Account created successfully. Please sign in.";
            } else {
                $error = "Signup failed. Username or email may already exist.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

if (($_POST['action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['role'] = $user['role'];  // Set role for protection
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTrack Asset Tracking System- Device Requisition System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="site_footer.css">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
                'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
                sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Container */
        .container {
            min-height: 0;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(152, 196, 152, 0.88) 0%, rgba(191, 205, 219, 0.88) 100%); /* Green to grey-blue */
        }

        /* Card */
        .card {
            width: 100%;
            max-width: 450px;
            background: #f4f4f4; /* Grey background to match theme */
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 2rem;
        }

        /* Icon */
        .icon-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 1.5rem auto;
            width: 100%;
        }

        .icon-circle {
            width: 4rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .icon {
            width: 4rem;
            height: 4rem;
            object-fit: contain;
        }

        /* Title */
        .title {
            font-size: 1.875rem;
            font-weight: bold;
            text-align: center;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        /* Messages */
        .message {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }

        .error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .success {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #d1d5db; /* Grey */
            color: #374151;
        }

        .tab-button:hover {
            background-color: #c4c9d0;
        }

        .tab-button.active {
            background-color: #4CAF50; /* Green */
            color: white;
        }

        /* Form */
        .form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1a1a1a;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 0.75rem;
            width: 1.25rem;
            height: 1.25rem;
            color: #6b7280;
            pointer-events: none;
        }

        .input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #d1d5db; /* Grey border */
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #1a1a1a;
            transition: all 0.2s ease;
        }

        .input:focus {
            outline: none;
            border-color: #4CAF50; /* Green focus */
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .input::placeholder {
            color: #9ca3af;
        }

        /* Hidden fields for signup */
        .signup-only {
            display: none;
        }

        /* Submit Button */
        .submit-button {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #4CAF50; /* Green */
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
        }

        .submit-button:hover {
            background-color: #45a049; /* Darker green */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .submit-button:active {
            transform: scale(0.98);
        }

        /* Responsive */
        @media (max-width: 640px) {
            .card {
                padding: 1.5rem;
            }
            
            .title {
                font-size: 1.5rem;
            }
        }
    </style>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <div class="login-layout">
        <div class="login-visual">
            <img class="login-photo" src="images/background2.jpg.jpg" alt="iTrack vehicle telematics camera and road monitoring">
        </div>
        <section class="card auth-panel">
            <!-- Main Icon -->
            <div class="icon-wrapper">
                <div class="icon-circle">
                    <img src="images/logo.png.png" alt="iTrack location logo" class="icon">
                </div>
            </div>

            <!-- Title and Subtitle -->
            <h1 class="title">iTrack Asset Tracking System</h1>
            <p class="subtitle">Manage fleet tracking devices, requests, and stock in one place.</p>

            <!-- PHP Messages -->
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-button active" id="signInTab" onclick="switchTab('signin')">
                    Sign In
                </button>
                <button class="tab-button" id="signUpTab" onclick="switchTab('signup')">
                    Sign Up
                </button>
            </div>

            <!-- Form -->
            <form id="authForm" class="form" method="POST" onsubmit="handleSubmit(event)">
                <input type="hidden" name="action" id="formAction" value="login">

                <!-- Common Fields (Always Visible) -->
                <div class="form-group">
                    <label class="label">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="input" placeholder="Enter username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="input" placeholder="Enter password" required>
                    </div>
                </div>

                <!-- Signup-Only Fields -->
                <div class="form-group signup-only">
                    <label class="label">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="input" placeholder="Enter email">
                    </div>
                </div>

                <div class="form-group signup-only">
                    <label class="label">Department</label>
                    <div class="input-wrapper">
                        <i class="fas fa-building input-icon"></i>
                        <select id="department" name="department" class="input">
                            <option value="">Select Department</option>
                            <option value="IT Support">IT Support</option>
                            <option value="Field">Field</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Accounts">Accounts</option>
                            <option value="Management">Management</option>
                        </select>
                    </div>
                </div>

                <button type="submit" id="submitButton" class="submit-button">Sign In</button>
            </form>
        </section>
    </div>
    <?php include 'site_footer.php'; ?>

    <script>
        // Track current mode
        let isSignIn = true;

        // Switch between Sign In and Sign Up tabs
        function switchTab(mode) {
            isSignIn = mode === 'signin';
            
            // Update tab buttons
            const signInTab = document.getElementById('signInTab');
            const signUpTab = document.getElementById('signUpTab');
            const submitButton = document.getElementById('submitButton');
            const formAction = document.getElementById('formAction');
            const signupFields = document.querySelectorAll('.signup-only');
            
            if (isSignIn) {
                signInTab.classList.add('active');
                signUpTab.classList.remove('active');
                submitButton.textContent = 'Sign In';
                formAction.value = 'login';
                // Hide signup fields
                signupFields.forEach(field => field.style.display = 'none');
                // Clear signup fields
                document.getElementById('email').value = '';
                document.getElementById('department').value = '';
            } else {
                signInTab.classList.remove('active');
                signUpTab.classList.add('active');
                submitButton.textContent = 'Sign Up';
                formAction.value = 'signup';
                // Show signup fields
                signupFields.forEach(field => field.style.display = 'block');
            }
            
            // Clear common fields
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            
            // Clear any messages (optional, since PHP handles)
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => msg.remove());
        }

        // Handle form submission (prevents default only if invalid; lets PHP handle)
        function handleSubmit(event) {
            // Basic client-side validation
            const password = document.getElementById('password').value;
            if (!isSignIn && (password.length < 6)) {
                event.preventDefault();
                alert('Password must be at least 6 characters for signup.');
                return;
            }
            // For login/signup, let form submit to PHP
        }

        // Enter key support
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('authForm');
            form.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>