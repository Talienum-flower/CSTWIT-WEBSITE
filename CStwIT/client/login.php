<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CStwIT - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #800000;
            --secondary-color: #FF9200;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #6c757d;
            --dark-gray: #343a40;
            --border-color: #e9ecef;
            --shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
            --shadow-hover: 0 8px 35px rgba(0, 0, 0, 0.25);
            --gradient: linear-gradient(135deg, var(--primary-color) 0%, #a00000 100%);
            --orange-gradient: linear-gradient(135deg, var(--secondary-color) 0%, #ff8c00 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.6;
        }

        .auth-container {
            width: 100%;
            max-width: 1000px;
            margin: 20px;
            display: flex;
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            min-height: 500px;
            max-height: 85vh;
            transition: all 0.3s ease;
        }

        .auth-container:hover {
            box-shadow: var(--shadow-hover);
        }

        .auth-left {
            flex: 1;
            background: var(--gradient);
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .auth-left h1 {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(45deg, var(--white), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
        }

        .auth-left p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            max-width: 350px;
            position: relative;
            z-index: 1;
        }

        .auth-right {
            flex: 1;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--white);
            position: relative;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            margin: 0 auto 15px;
            background: var(--orange-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(255, 146, 0, 0.3);
            transition: all 0.3s ease;
        }

        .logo-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 146, 0, 0.4);
        }

        .auth-header h2 {
            color: var(--dark-gray);
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
        }

        .auth-form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--dark-gray);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(255, 146, 0, 0.1);
            transform: translateY(-2px);
        }

        .form-group input::placeholder {
            color: var(--gray);
            font-weight: 400;
        }

        .form-group .email-note {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
            font-size: 1rem;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }

        .auth-button {
            width: 100%;
            padding: 14px;
            background: var(--orange-gradient);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 146, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 146, 0, 0.4);
        }

        .auth-button:active {
            transform: translateY(-1px);
        }

        .auth-links {
            text-align: center;
            margin: 20px 0;
        }

        .forgot-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .auth-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
            margin: 20px 0;
            position: relative;
        }

        .auth-divider::after {
            content: 'OR';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            padding: 0 15px;
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .create-account-button {
            display: block;
            width: 100%;
            padding: 13px;
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .create-account-button:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(128, 0, 0, 0.3);
        }

        /* Modal Styles */
        .modal-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .modal-background.active {
            opacity: 1;
        }

        .register-modal,
        .forgot-modal {
            background: var(--white);
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-hover);
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .modal-background.active .register-modal,
        .modal-background.active .forgot-modal {
            transform: scale(1);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            color: var(--primary-color);
            background: rgba(128, 0, 0, 0.1);
            transform: rotate(90deg);
        }

        /* Error and Success Messages */
        .error-message,
        .success-message {
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 3px solid #dc3545;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 3px solid #28a745;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                margin: 10px;
                border-radius: 14px;
                min-height: auto;
                max-height: 95vh;
            }

            .auth-left {
                padding: 30px 25px;
                text-align: center;
                align-items: center;
            }

            .auth-left h1 {
                font-size: 2.2rem;
            }

            .auth-left p {
                font-size: 1rem;
                text-align: center;
            }

            .auth-right {
                padding: 30px 25px;
            }

            .auth-header {
                margin-bottom: 25px;
            }

            .auth-header h2 {
                font-size: 1.4rem;
            }

            .logo-icon {
                width: 50px;
                height: 50px;
            }

            .register-modal,
            .forgot-modal {
                padding: 25px 20px;
                margin: 15px;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                margin: 5px;
            }

            .auth-left {
                padding: 25px 20px;
            }

            .auth-left h1 {
                font-size: 1.8rem;
            }

            .auth-right {
                padding: 25px 20px;
            }

            .form-group input {
                padding: 12px 14px;
            }

            .auth-button {
                padding: 12px;
                font-size: 0.95rem;
            }

            .register-modal,
            .forgot-modal {
                padding: 20px 15px;
                margin: 10px;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 18px;
            height: 18px;
            border: 2px solid transparent;
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Enhanced Hover Effects */
        .form-group {
            transition: all 0.3s ease;
        }

        .form-group:hover input {
            border-color: rgba(255, 146, 0, 0.5);
        }

        /* Social Media Icons Effect */
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: 25px;
            right: 25px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.1; }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <h1>CStwIT</h1>
            <p>Join CStwIT – Never miss a moment. Share your thoughts, one tweet at a time.</p>
        </div>
        
        <div class="auth-right">
            <div class="auth-header">
                <img src="../assets/images/logo.jpg" alt="CStwIT Logo" class="logo-icon">
                <h2>Welcome to CStwIT</h2>
            </div>
            <form action="../api/login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group password-wrapper">
                    <div class="password-container">
                        <input type="password" name="password" id="login-password" placeholder="Password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('login-password', this)"></i>
                    </div>
                </div>
                <button type="submit" class="auth-button">Log in</button>
                <div class="auth-links">
                    <a href="#" class="forgot-link" id="open-forgot-modal">Forgot Password?</a>
                </div>
                <div class="auth-divider"></div>
                <a href="#" class="create-account-button" id="open-register-modal">Create new account</a>
            </form>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal-background" id="register-modal-bg">
        <div class="register-modal">
            <button class="modal-close" id="close-register-modal">×</button>
            <div class="auth-header">
                <img src="../assets/images/logo.jpg" alt="CStwIT Logo" class="logo-icon">
                <h2>Create new account</h2>
            </div>
            <form action="/CStwIT/api/register.php" method="POST" class="auth-form" id="register-form">
                <div class="form-group">
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" id="register-email" placeholder="Email" required>
                    <p class="email-note">Must be a Gmail address (@gmail.com)</p>
                </div>
                <div class="form-group password-wrapper">
                    <div class="password-container">
                        <input type="password" name="password" id="register-password" placeholder="Password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('register-password', this)"></i>
                    </div>
                </div>
                <button type="submit" class="auth-button">Create new account</button>
                <div class="auth-links">
                    <p>Already have account? <a href="#" class="login-link" style="color: var(--secondary-color); text-decoration: none; font-weight: 500;">Log in</a></p>
                </div>
                <p class="error-message" id="register-error"></p>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal-background" id="forgot-modal-bg">
        <div class="forgot-modal">
            <button class="modal-close" id="close-forgot-modal">×</button>
            <div class="auth-header">
                <img src="../assets/images/logo.jpg" alt="CStwIT Logo" class="logo-icon">
                <h2>Reset Password</h2>
            </div>
            <div id="email-step">
                <form id="forgot-email-form" class="auth-form">
                    <div class="form-group">
                        <input type="email" name="email" id="forgot-email" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="auth-button">Verify Email</button>
                    <p class="error-message" id="email-error"></p>
                    <p class="success-message" id="email-success"></p>
                </form>
            </div>
            <div id="password-step" style="display: none;">
                <form id="reset-password-form" class="auth-form">
                    <div class="form-group password-wrapper">
                        <div class="password-container">
                            <input type="password" name="new_password" id="new-password" placeholder="New Password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('new-password', this)"></i>
                        </div>
                    </div>
                    <div class="form-group password-wrapper">
                        <div class="password-container">
                            <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm Password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm-password', this)"></i>
                        </div>
                    </div>
                    <button type="submit" class="auth-button">Reset Password</button>
                    <p class="error-message" id="password-error"></p>
                    <p class="success-message" id="password-success"></p>
                </form>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(inputId, icon) {
        const passwordInput = document.getElementById(inputId);
        if (passwordInput) {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Register Modal Elements
        const openRegisterModalButton = document.getElementById('open-register-modal');
        const closeRegisterModalButton = document.getElementById('close-register-modal');
        const registerModalBackground = document.getElementById('register-modal-bg');

        // Forgot Password Modal Elements
        const openForgotModalButton = document.getElementById('open-forgot-modal');
        const closeForgotModalButton = document.getElementById('close-forgot-modal');
        const forgotModalBackground = document.getElementById('forgot-modal-bg');

        // Login Form Elements
        const loginForm = document.querySelector('.auth-form[action="../api/login.php"]');
        const loginError = document.createElement('p');
        loginError.className = 'error-message';
        loginError.id = 'login-error';
        if (loginForm) {
            loginForm.appendChild(loginError);
        }

        // Add loading state function
        function setLoadingState(button, isLoading) {
            if (button) {
                if (isLoading) {
                    button.classList.add('loading');
                    button.disabled = true;
                } else {
                    button.classList.remove('loading');
                    button.disabled = false;
                }
            }
        }

        // Handle Login Form Submission
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('.auth-button');
                setLoadingState(submitBtn, true);
                
                const formData = new FormData(loginForm);
                formData.delete('is_admin');

                fetch('../api/login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    setLoadingState(submitBtn, false);
                    const errorMessage = document.getElementById('login-error');
                    if (errorMessage) {
                        if (data.success) {
                            errorMessage.style.display = 'none';
                            window.location.href = data.redirect;
                        } else {
                            errorMessage.textContent = data.message || 'Invalid username or password.';
                            errorMessage.style.display = 'block';
                        }
                    }
                })
                .catch(error => {
                    setLoadingState(submitBtn, false);
                    const errorMessage = document.getElementById('login-error');
                    if (errorMessage) {
                        errorMessage.textContent = 'An error occurred. Please try again later.';
                        errorMessage.style.display = 'block';
                    }
                });
            });
        }

        // Handle Register Form Submission with Gmail and username validation
        const registerForm = document.querySelector('.auth-form[action="/CStwIT/api/register.php"]');
        const registerError = document.getElementById('register-error');
        if (registerForm && registerError) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('.auth-button');
                const emailInput = document.getElementById('register-email');
                const usernameInput = this.querySelector('input[name="username"]');

                // Client-side Gmail validation
                if (emailInput && !emailInput.value.endsWith('@gmail.com')) {
                    registerError.textContent = 'Only Gmail addresses (@gmail.com) are allowed.';
                    registerError.style.display = 'block';
                    return;
                }

                setLoadingState(submitBtn, true);
                
                const formData = new FormData(this);

                fetch('/CStwIT/api/register.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    setLoadingState(submitBtn, false);
                    if (data.success) {
                        registerError.style.display = 'none';
                        window.location.href = data.redirect;
                    } else {
                        if (data.message === 'Email already exists.') {
                            registerError.textContent = `The email "${data.email}" is already registered. Please use a different email.`;
                        } else if (data.message === 'Username already exists.') {
                            registerError.textContent = `The username "${data.username}" is already taken. Please choose a different username.`;
                        } else if (data.message === 'Only Gmail addresses (@gmail.com) are allowed.') {
                            registerError.textContent = 'Please use a Gmail address (@gmail.com).';
                        } else {
                            registerError.textContent = data.message;
                        }
                        registerError.style.display = 'block';
                    }
                })
                .catch(error => {
                    setLoadingState(submitBtn, false);
                    registerError.textContent = 'An error occurred. Please try again later.';
                    registerError.style.display = 'block';
                });
            });
        }

        // Open Register Modal
        if (openRegisterModalButton && registerModalBackground) {
            openRegisterModalButton.addEventListener('click', function(e) {
                e.preventDefault();
                if (registerError) {
                    registerError.style.display = 'none';
                }
                if (registerForm) {
                    registerForm.reset();
                }
                registerModalBackground.style.display = 'flex';
                setTimeout(() => {
                    registerModalBackground.classList.add('active');
                }, 10);
            });
        }

        // Close Register Modal
        if (closeRegisterModalButton && registerModalBackground) {
            closeRegisterModalButton.addEventListener('click', function() {
                registerModalBackground.classList.remove('active');
                if (registerError) {
                    registerError.style.display = 'none';
                }
                setTimeout(() => {
                    registerModalBackground.style.display = 'none';
                }, 300);
            });
        }

        // Close Register Modal on Background Click
        if (registerModalBackground) {
            registerModalBackground.addEventListener('click', function(e) {
                if (e.target === registerModalBackground) {
                    registerModalBackground.classList.remove('active');
                    if (registerError) {
                        registerError.style.display = 'none';
                    }
                    setTimeout(() => {
                        registerModalBackground.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Open Forgot Password Modal
        if (openForgotModalButton && forgotModalBackground) {
            openForgotModalButton.addEventListener('click', function(e) {
                e.preventDefault();
                forgotModalBackground.style.display = 'flex';
                setTimeout(() => {
                    forgotModalBackground.classList.add('active');
                }, 10);
            });
        }

        // Close Forgot Password Modal
        if (closeForgotModalButton && forgotModalBackground) {
            closeForgotModalButton.addEventListener('click', function() {
                forgotModalBackground.classList.remove('active');
                setTimeout(() => {
                    forgotModalBackground.style.display = 'none';
                    resetForgotModal();
                }, 300);
            });
        }

        // Close Forgot Password Modal on Background Click
        if (forgotModalBackground) {
            forgotModalBackground.addEventListener('click', function(e) {
                if (e.target === forgotModalBackground) {
                    forgotModalBackground.classList.remove('active');
                    setTimeout(() => {
                        forgotModalBackground.style.display = 'none';
                        resetForgotModal();
                    }, 300);
                }
            });
        }

        function resetForgotModal() {
            const forgotEmailForm = document.getElementById('forgot-email-form');
            const resetPasswordForm = document.getElementById('reset-password-form');
            const emailStep = document.getElementById('email-step');
            const passwordStep = document.getElementById('password-step');
            const emailError = document.getElementById('email-error');
            const emailSuccess = document.getElementById('email-success');
            const passwordError = document.getElementById('password-error');
            const passwordSuccess = document.getElementById('password-success');

            if (forgotEmailForm) forgotEmailForm.reset();
            if (resetPasswordForm) resetPasswordForm.reset();
            if (emailStep) emailStep.style.display = 'block';
            if (passwordStep) passwordStep.style.display = 'none';
            if (emailError) emailError.style.display = 'none';
            if (emailSuccess) emailSuccess.style.display = 'none';
            if (passwordError) passwordError.style.display = 'none';
            if (passwordSuccess) passwordSuccess.style.display = 'none';
        }

        // Handle Email Verification
        const forgotEmailForm = document.getElementById('forgot-email-form');
        if (forgotEmailForm) {
            forgotEmailForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('.auth-button');
                setLoadingState(submitBtn, true);
                
                const email = document.getElementById('forgot-email')?.value;
                const emailError = document.getElementById('email-error');
                const emailSuccess = document.getElementById('email-success');

                if (!email || !emailError || !emailSuccess) return;

                fetch('/CStwIT/api/check_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    setLoadingState(submitBtn, false);
                    if (data.success) {
                        emailError.style.display = 'none';
                        emailSuccess.textContent = 'Email verified! Please enter your new password.';
                        emailSuccess.style.display = 'block';
                        document.getElementById('email-step').style.display = 'none';
                        document.getElementById('password-step').style.display = 'block';
                    } else {
                        emailSuccess.style.display = 'none';
                        emailError.textContent = 'Email not found. Please try again.';
                        emailError.style.display = 'block';
                    }
                })
                .catch(error => {
                    setLoadingState(submitBtn, false);
                    emailSuccess.style.display = 'none';
                    emailError.textContent = 'An error occurred. Please try again later.';
                    emailError.style.display = 'block';
                });
            });
        }

        // Handle Password Reset
        const resetPasswordForm = document.getElementById('reset-password-form');
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('.auth-button');
                setLoadingState(submitBtn, true);
                
                const email = document.getElementById('forgot-email')?.value;
                const newPassword = document.getElementById('new-password')?.value;
                const confirmPassword = document.getElementById('confirm-password')?.value;
                const passwordError = document.getElementById('password-error');
                const passwordSuccess = document.getElementById('password-success');

                if (!email || !newPassword || !confirmPassword || !passwordError || !passwordSuccess) return;

                if (newPassword !== confirmPassword) {
                    setLoadingState(submitBtn, false);
                    passwordSuccess.style.display = 'none';
                    passwordError.textContent = 'Passwords do not match.';
                    passwordError.style.display = 'block';
                    return;
                }

                fetch('/CStwIT/api/reset_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email) + '&new_password=' + encodeURIComponent(newPassword)
                })
                .then(response => response.json())
                .then(data => {
                    setLoadingState(submitBtn, false);
                    if (data.success) {
                        passwordError.style.display = 'none';
                        passwordSuccess.textContent = 'Password reset successfully! You can now log in.';
                        passwordSuccess.style.display = 'block';
                        setTimeout(() => {
                            forgotModalBackground.classList.remove('active');
                            setTimeout(() => {
                                forgotModalBackground.style.display = 'none';
                                resetForgotModal();
                            }, 300);
                        }, 2000);
                    } else {
                        passwordSuccess.style.display = 'none';
                        passwordError.textContent = data.message || 'An error occurred. Please try again.';
                        passwordError.style.display = 'block';
                    }
                })
                .catch(error => {
                    setLoadingState(submitBtn, false);
                    passwordSuccess.style.display = 'none';
                    passwordError.textContent = 'An error occurred. Please try again later.';
                    passwordError.style.display = 'block';
                });
            });
        }
    });
</script>
</body>
</html>