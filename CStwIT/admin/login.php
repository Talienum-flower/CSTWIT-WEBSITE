<?php
// Check if admin is already logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['admin_id'])) {
    header("Location: /CStwIT/admin/dashboard.php");
    exit();
}

// Include header (minimal header without navigation for login page)
?>

<!-- Add this in your header.php or add it here if you don't want to modify header.php -->
<link rel="stylesheet" href="assets/css/login-style.css">
<style>
/* Admin Login CSS - Perfect & Responsive */

/* CSS Variables for Admin Theme */
:root {
    --primary-color: #800000;        /* Deep Maroon */
    --primary-hover: #a00000;        /* Lighter Maroon */
    --primary-light: rgba(128, 0, 0, 0.1);
    --secondary-color: #FF9200;      /* Orange */
    --secondary-hover: #ffb84d;      /* Lighter Orange */
    --secondary-light: rgba(255, 146, 0, 0.1);
    --dark-color: #000000;           /* Black */
    --dark-light: #1a1a1a;          /* Very Dark Gray */
    --dark-medium: #333333;          /* Dark Gray */
    --bg-color: #ffffff;
    --bg-secondary: #f8f9fa;
    --text-color: #2d3748;
    --text-secondary: #718096;
    --text-muted: #a0aec0;
    --text-light: #ffffff;
    --border-color: #e2e8f0;
    --border-hover: #cbd5e0;
    --border-radius: 12px;
    --border-radius-lg: 16px;
    --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --shadow-xl: 0 35px 60px -12px rgba(0, 0, 0, 0.35);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-fast: all 0.15s ease;
    --admin-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--dark-color) 50%, var(--primary-color) 100%);
    --accent-gradient: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
}

/* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background: var(--admin-gradient);
    min-height: 100vh;
    font-weight: 400;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

/* Animated Background Pattern */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 80%, rgba(255, 146, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(128, 0, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(255, 146, 0, 0.05) 0%, transparent 50%);
    animation: adminFloat 20s ease-in-out infinite;
    pointer-events: none;
    z-index: -1;
}

@keyframes adminFloat {
    0%, 100% { 
        transform: translate(0, 0) rotate(0deg);
        opacity: 1;
    }
    33% { 
        transform: translate(30px, -30px) rotate(120deg);
        opacity: 0.8;
    }
    66% { 
        transform: translate(-20px, 20px) rotate(240deg);
        opacity: 0.9;
    }
}

/* Login Container */
.login-container {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* Login Info Section */
.login-info {
    flex: 1;
    background: var(--admin-gradient);
    color: var(--text-light);
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    position: relative;
    overflow: hidden;
}

.login-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="adminGrid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,146,0,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23adminGrid)"/></svg>');
    opacity: 0.3;
}

.login-info h1 {
    font-size: clamp(36px, 5vw, 48px);
    font-weight: 700;
    margin: 0 0 24px;
    letter-spacing: -0.02em;
    line-height: 1.1;
    position: relative;
    z-index: 1;
    background: linear-gradient(135deg, var(--text-light) 0%, var(--secondary-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.login-info p {
    font-size: clamp(16px, 2.5vw, 20px);
    line-height: 1.7;
    margin: 0;
    opacity: 0.95;
    font-weight: 400;
    position: relative;
    z-index: 1;
    max-width: 400px;
}

/* Login Form Section */
.login-form {
    flex: 1;
    background: var(--bg-color);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    position: relative;
}

.login-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent 0%, rgba(255, 146, 0, 0.02) 50%, transparent 100%);
    pointer-events: none;
}

/* Login Form Container */
.login-form-container {
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 1;
    animation: slideUp 0.8s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Admin Icon Section */
.admin-icon {
    text-align: center;
    margin-bottom: 40px;
    position: relative;
}

.admin-icon img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--secondary-color);
    box-shadow: 0 8px 32px rgba(255, 146, 0, 0.3);
    transition: var(--transition);
    position: relative;
}

.admin-icon img:hover {
    transform: scale(1.05) rotate(5deg);
    box-shadow: 0 12px 48px rgba(255, 146, 0, 0.4);
}

.admin-icon::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100px;
    height: 100px;
    background: var(--accent-gradient);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.1;
    animation: pulse 3s ease-in-out infinite;
    z-index: -1;
}

@keyframes pulse {
    0%, 100% { 
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.1;
    }
    50% { 
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0.05;
    }
}

.admin-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-color);
    margin: 16px 0 0;
    letter-spacing: 0.5px;
}

/* Form Styles */
#login-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-group {
    position: relative;
}

.form-group input {
    width: 100%;
    padding: 18px 24px;
    font-size: 16px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: var(--transition);
    font-family: inherit;
    outline: none;
    position: relative;
}

.form-group input::placeholder {
    color: var(--text-muted);
    opacity: 1;
    transition: var(--transition-fast);
}

.form-group input:hover {
    border-color: var(--border-hover);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.form-group input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-light), 0 4px 20px rgba(128, 0, 0, 0.15);
    transform: translateY(-2px);
}

.form-group input:focus::placeholder {
    opacity: 0.4;
    transform: translateY(-2px);
}

/* Login Button */
.button {
    width: 100%;
    padding: 18px 24px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-light);
    background: var(--accent-gradient);
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    font-family: inherit;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 8px;
}

.button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.button:hover::before {
    left: 100%;
}

.button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(255, 146, 0, 0.4);
    background: linear-gradient(135deg, var(--secondary-hover) 0%, var(--primary-hover) 100%);
}

.button:active {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(255, 146, 0, 0.3);
}

.button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Loading State for Button */
.button.loading {
    pointer-events: none;
    position: relative;
}

.button.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid var(--text-light);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error Message */
.login-error {
    color: #e53e3e;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    padding: 12px 16px;
    background-color: rgba(229, 62, 62, 0.1);
    border: 1px solid rgba(229, 62, 62, 0.2);
    border-radius: var(--border-radius);
    margin-top: 16px;
    display: none;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Forgot Password Section */
.forgot-password {
    text-align: center;
    margin-top: 32px;
}

.forgot-link {
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    padding: 8px 16px;
    border-radius: var(--border-radius);
    display: inline-block;
}

.forgot-link:hover {
    color: var(--secondary-color);
    background-color: var(--secondary-light);
    text-decoration: none;
    transform: translateY(-1px);
}

/* Modal Styles */
.modal-background {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: var(--transition);
    pointer-events: none;
}

.modal-background.active {
    opacity: 1;
    pointer-events: all;
}

.modal-content {
    background-color: var(--bg-color);
    border-radius: var(--border-radius-lg);
    max-width: 500px;
    width: 90%;
    padding: 40px;
    box-shadow: var(--shadow-xl);
    transform: translateY(-30px) scale(0.9);
    transition: var(--transition);
    position: relative;
    border: 1px solid var(--border-color);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-background.active .modal-content {
    transform: translateY(0) scale(1);
}

/* Modal Close Button */
.close-modal {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 28px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-secondary);
    transition: var(--transition);
    padding: 8px;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.close-modal:hover {
    color: var(--primary-color);
    background-color: var(--primary-light);
    transform: rotate(90deg);
}

/* Modal Title */
.modal-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--text-color);
    margin: 0 0 32px;
    text-align: center;
    letter-spacing: -0.01em;
}

/* Modal Steps */
.modal-step {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-step .form-group {
    margin-bottom: 20px;
}

.modal-step label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 8px;
}

.modal-step input {
    width: 100%;
    padding: 16px 20px;
    font-size: 16px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    background-color: var(--bg-color);
    color: var(--text-color);
    transition: var(--transition);
    font-family: inherit;
    outline: none;
}

.modal-step input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-light);
}

/* Modal Button */
.modal-button {
    width: 100%;
    padding: 16px 24px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-light);
    background: var(--accent-gradient);
    border: none;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    font-family: inherit;
    margin-top: 16px;
}

.modal-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 146, 0, 0.3);
}

/* Error and Success Messages in Modal */
.error-message,
.success-message {
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    padding: 12px 16px;
    border-radius: var(--border-radius);
    margin: 16px 0;
    display: none;
    animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.error-message {
    color: #e53e3e;
    background-color: rgba(229, 62, 62, 0.1);
    border: 1px solid rgba(229, 62, 62, 0.2);
}

.success-message {
    color: #38a169;
    background-color: rgba(56, 161, 105, 0.1);
    border: 1px solid rgba(56, 161, 105, 0.2);
}

/* Focus States for Accessibility */
.button:focus-visible,
.modal-button:focus-visible,
.forgot-link:focus-visible {
    outline: 2px solid var(--secondary-color);
    outline-offset: 2px;
}

/* Responsive Design */

/* Large Desktop (1200px+) */
@media (min-width: 1200px) {
    .login-info {
        padding: 80px 60px;
    }
    
    .login-form {
        padding: 60px;
    }
    
    .login-form-container {
        max-width: 450px;
    }
    
    .admin-icon img {
        width: 90px;
        height: 90px;
    }
}

/* Desktop & Tablet (769px - 1199px) */
@media (min-width: 769px) and (max-width: 1199px) {
    .login-info {
        padding: 50px 40px;
    }
    
    .login-form {
        padding: 40px;
    }
    
    .login-form-container {
        max-width: 380px;
    }
    
    .admin-icon img {
        width: 70px;
        height: 70px;
    }
}

/* Tablet Portrait & Mobile Landscape (481px - 768px) */
@media (max-width: 768px) {
    .login-container {
        flex-direction: column;
        min-height: 100vh;
    }
    
    .login-info {
        flex: none;
        padding: 40px 30px 30px;
        text-align: center;
        align-items: center;
        min-height: auto;
    }
    
    .login-info p {
        max-width: 100%;
    }
    
    .login-form {
        flex: 1;
        padding: 30px;
        min-height: auto;
    }
    
    .login-form-container {
        max-width: 400px;
    }
    
    .admin-icon {
        margin-bottom: 32px;
    }
    
    .admin-icon img {
        width: 64px;
        height: 64px;
    }
    
    .form-group input,
    .button {
        padding: 16px 20px;
        font-size: 16px;
    }
    
    .modal-content {
        padding: 32px 24px;
        width: 95%;
    }
}

/* Mobile Portrait (320px - 480px) */
@media (max-width: 480px) {
    .login-info {
        padding: 30px 20px 20px;
    }
    
    .login-form {
        padding: 20px;
    }
    
    .login-form-container {
        max-width: 100%;
    }
    
    .admin-icon {
        margin-bottom: 28px;
    }
    
    .admin-icon img {
        width: 56px;
        height: 56px;
    }
    
    .admin-text {
        font-size: 16px;
    }
    
    #login-form {
        gap: 20px;
    }
    
    .form-group input,
    .button {
        padding: 14px 18px;
        font-size: 16px;
    }
    
    .modal-content {
        padding: 24px 20px;
        width: 95%;
        margin: 20px;
    }
    
    .modal-title {
        font-size: 20px;
        margin-bottom: 24px;
    }
    
    .close-modal {
        width: 36px;
        height: 36px;
        font-size: 24px;
    }
}

/* Very Small Screens (below 375px) */
@media (max-width: 374px) {
    .login-info {
        padding: 25px 15px 15px;
    }
    
    .login-form {
        padding: 15px;
    }
    
    .admin-icon img {
        width: 48px;
        height: 48px;
    }
    
    .admin-text {
        font-size: 14px;
    }
    
    .form-group input,
    .button {
        padding: 12px 16px;
        font-size: 15px;
    }
    
    .modal-content {
        padding: 20px 16px;
        margin: 10px;
    }
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
    :root {
        --border-color: #000;
        --text-secondary: #000;
        --shadow: 0 0 0 2px #000;
    }
}

/* Reduce Motion for Accessibility */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    body::before {
        animation: none;
    }
    
    .admin-icon::before {
        animation: none;
    }
}

/* Print Styles */
@media print {
    .login-container {
        background: white;
    }
    
    .login-info {
        background: #f0f0f0;
        color: #000;
    }
    
    .modal-background {
        display: none !important;
    }
}
</style>

<div class="login-container">
    <div class="login-info">
        <h1>CStwIT</h1>
        <p>Log in now and stay in control of your system and administrative tools.</p>
    </div>
    
    <div class="login-form">
        <div class="login-form-container">
            <div class="admin-icon">
               <img src="../assets/images/logoadmin.jpg " alt="Admin Icon">
                <p class="admin-text">CStwIT Admin</p>
            </div>
            
            <form id="login-form" method="POST" action="../api/admin/admin_login.php">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Admin Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="button">Login</button>
                <div id="login-error" class="login-error"></div>
            </form>
            
            <div class="forgot-password">
                <a href="#" id="forgot-password-link" class="forgot-link">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-background" id="forgot-modal-background">
    <div class="modal-content">
        <span class="close-modal" id="close-forgot-modal">×</span>
        <h3 class="modal-title">Reset Password</h3>
        
        <div id="email-step" class="modal-step">
            <form id="forgot-email-form">
                <div class="form-group">
                    <label for="forgot-email">Email Address</label>
                    <input type="email" id="forgot-email" placeholder="Enter your email" required>
                </div>
                <div id="email-error" class="error-message"></div>
                <div id="email-success" class="success-message"></div>
                <button type="submit" class="modal-button">Verify Email</button>
            </form>
        </div>
        
        <div id="password-step" class="modal-step" style="display: none;">
            <form id="reset-password-form">
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" placeholder="Confirm new password" required>
                </div>
                <div id="password-error" class="error-message"></div>
                <div id="password-success" class="success-message"></div>
                <button type="submit" class="modal-button">Reset Password</button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Handling Login and Modals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements for Login Form
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');

    // Elements for Forgot Password Modal
    const forgotPasswordLink = document.getElementById('forgot-password-link');
    const forgotModalBackground = document.getElementById('forgot-modal-background');
    const closeForgotModalButton = document.getElementById('close-forgot-modal');

    // Handle Login Form Submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(loginForm);
        
        fetch('../api/admin/admin_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to the appropriate page on successful login
                window.location.href = data.redirect;
            } else {
                // Show inline error message
                loginError.textContent = data.message;
                loginError.style.display = 'block';
            }
        })
        .catch(error => {
            // Show inline error message for network or server errors
            loginError.textContent = 'An error occurred. Please try again later.';
            loginError.style.display = 'block';
        });
    });

    // Clear error message when user starts typing
    loginForm.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            loginError.style.display = 'none';
        });
    });

    // Open Forgot Password Modal
    forgotPasswordLink.addEventListener('click', function(e) {
        e.preventDefault();
        forgotModalBackground.style.display = 'flex';
        setTimeout(() => {
            forgotModalBackground.classList.add('active');
        }, 10);
    });

    // Close Forgot Password Modal
    closeForgotModalButton.addEventListener('click', function() {
        forgotModalBackground.classList.remove('active');
        setTimeout(() => {
            forgotModalBackground.style.display = 'none';
            // Reset forms and messages
            document.getElementById('forgot-email-form').reset();
            document.getElementById('reset-password-form').reset();
            document.getElementById('email-step').style.display = 'block';
            document.getElementById('password-step').style.display = 'none';
            document.getElementById('email-error').style.display = 'none';
            document.getElementById('email-success').style.display = 'none';
            document.getElementById('password-error').style.display = 'none';
            document.getElementById('password-success').style.display = 'none';
        }, 300);
    });

    // Close Forgot Password Modal on Background Click
    forgotModalBackground.addEventListener('click', function(e) {
        if (e.target === forgotModalBackground) {
            forgotModalBackground.classList.remove('active');
            setTimeout(() => {
                forgotModalBackground.style.display = 'none';
                // Reset forms and messages
                document.getElementById('forgot-email-form').reset();
                document.getElementById('reset-password-form').reset();
                document.getElementById('email-step').style.display = 'block';
                document.getElementById('password-step').style.display = 'none';
                document.getElementById('email-error').style.display = 'none';
                document.getElementById('email-success').style.display = 'none';
                document.getElementById('password-error').style.display = 'none';
                document.getElementById('password-success').style.display = 'none';
            }, 300);
        }
    });

    // Handle Email Verification for Forgot Password
    document.getElementById('forgot-email-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('forgot-email').value;
        const emailError = document.getElementById('email-error');
        const emailSuccess = document.getElementById('email-success');

        // Validate email ends with @gmail.com
        if (!email.toLowerCase().endsWith('@gmail.com')) {
            emailSuccess.style.display = 'none';
            emailError.textContent = 'Please use a Gmail address (e.g., example@gmail.com).';
            emailError.style.display = 'block';
            return;
        }

        fetch('/CStwIT/api/check_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
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
            emailSuccess.style.display = 'none';
            emailError.textContent = 'An error occurred. Please try again later.';
            emailError.style.display = 'block';
        });
    });

    // Handle Password Reset
    document.getElementById('reset-password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('forgot-email').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        const passwordError = document.getElementById('password-error');
        const passwordSuccess = document.getElementById('password-success');

        if (newPassword !== confirmPassword) {
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
            if (data.success) {
                passwordError.style.display = 'none';
                passwordSuccess.textContent = 'Password reset successfully! You can now log in.';
                passwordSuccess.style.display = 'block';
                setTimeout(() => {
                    forgotModalBackground.classList.remove('active');
                    setTimeout(() => {
                        forgotModalBackground.style.display = 'none';
                        document.getElementById('forgot-email-form').reset();
                        document.getElementById('reset-password-form').reset();
                        document.getElementById('email-step').style.display = 'block';
                        document.getElementById('password-step').style.display = 'none';
                        passwordSuccess.style.display = 'none';
                    }, 300);
                }, 2000);
            } else {
                passwordSuccess.style.display = 'none';
                passwordError.textContent = data.message || 'An error occurred. Please try again.';
                passwordError.style.display = 'block';
            }
        })
        .catch(error => {
            passwordSuccess.style.display = 'none';
            passwordError.textContent = 'An error occurred. Please try again later.';
            passwordError.style.display = 'block';
        });
    });
});
</script>

<?php
?>