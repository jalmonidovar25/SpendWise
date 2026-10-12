
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Login</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f7f2;
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: white;
            max-width: 420px;
            margin: 70px auto;
            padding: 35px;
            border-radius: 12px;
            text-align: center;
        }

        h1 {
            color: #367a54;
            margin-bottom: 10px;
        }

        p {
            color: #666;
        }

        .tabs {
            display: flex;
            margin-top: 30px;
            margin-bottom: 25px;
        }

        .tabs button {
            width: 50%;
            padding: 12px;
            border: none;
            background-color: #e8f2eb;
            color: #367a54;
            cursor: pointer;
            font-size: 15px;
        }

        .tabs button.active {
            background-color: #367a54;
            color: white;
        }

        .form-group {
            text-align: left;
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #444;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }

        a {
            color: #367a54;
            text-decoration: none;
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            background-color: #367a54;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #28613f;
        }

        #signup-form {
            display: none;
        }

        .footer {
            margin-top: 25px;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>🌿 SpendWise</h1>

        <p>Track today. Build a brighter tomorrow.</p>

        <div class="tabs">
            <button id="signin-tab" class="active" onclick="showSignIn()">Sign In</button>
            <button id="signup-tab" onclick="showSignUp()">Sign Up</button>
        </div>

        <!-- SIGN IN FORM -->
        <form id="signin-form" action="login.php" method="POST">

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="options">
                <label>
                    <input type="checkbox" name="remember">
                    Remember me
                </label>

                <a href="#">Forgot password?</a>
            </div>

            <button type="submit" class="submit-btn">Sign In</button>

        </form>

        <!-- SIGN UP FORM -->
        <form id="signup-form" action="register.php" method="POST">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>

        </form>

        <div class="footer">
            Manage your income, expenses, and budget in one place.
        </div>

    </div>

    <script>
        function showSignIn() {
            document.getElementById("signin-form").style.display = "block";
            document.getElementById("signup-form").style.display = "none";

            document.getElementById("signin-tab").classList.add("active");
            document.getElementById("signup-tab").classList.remove("active");
        }

        function showSignUp() {
            document.getElementById("signin-form").style.display = "none";
            document.getElementById("signup-form").style.display = "block";

            document.getElementById("signin-tab").classList.remove("active");
            document.getElementById("signup-tab").classList.add("active");
        }
    </script>

</body>
</html>
