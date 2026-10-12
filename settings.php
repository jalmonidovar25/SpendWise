
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];
$message = "";
$message_type = "";

// Get the current user's information
$stmt = $conn->prepare(
    "SELECT fullname, email, password FROM users WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Update account information or change password
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "";

    if ($action == "update_profile") {

        $fullname = trim($_POST["fullname"] ?? "");
        $email = trim($_POST["email"] ?? "");

        if ($fullname == "" || $email == "") {
            $message = "Please complete all fields.";
            $message_type = "error";

        } elseif (strlen($fullname) > 100 || strlen($email) > 150) {
            $message = "Name or email is too long.";
            $message_type = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $message_type = "error";

        } else {

            // Check whether another account uses this email
            $stmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ? AND id != ?"
            );

            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();

            $existing = $stmt->get_result();

            if ($existing->num_rows > 0) {
                $message = "This email address is already registered.";
                $message_type = "error";
            }

            $stmt->close();

            if ($message == "") {

                $stmt = $conn->prepare(
                    "UPDATE users SET fullname = ?, email = ? WHERE id = ?"
                );

                $stmt->bind_param("ssi", $fullname, $email, $user_id);

                if ($stmt->execute()) {

                    $_SESSION["fullname"] = $fullname;

                    $user["fullname"] = $fullname;
                    $user["email"] = $email;

                    $message = "Profile updated successfully!";
                    $message_type = "success";

                } else {
                    $message = "Unable to update your profile.";
                    $message_type = "error";
                }

                $stmt->close();
            }
        }

    } elseif ($action == "change_password") {

        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if ($current_password == "" || $new_password == "" || $confirm_password == "") {
            $message = "Please complete all password fields.";
            $message_type = "error";

        } elseif (!password_verify($current_password, $user["password"])) {
            $message = "Your current password is incorrect.";
            $message_type = "error";

        } elseif (strlen($new_password) < 8) {
            $message = "Your new password must contain at least 8 characters.";
            $message_type = "error";

        } elseif ($new_password != $confirm_password) {
            $message = "New passwords do not match.";
            $message_type = "error";

        } else {

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                $message = "Password changed successfully!";
                $message_type = "success";
            } else {
                $message = "Unable to change your password.";
                $message_type = "error";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Settings</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f0f7f2;
            color: #333;
        }

        .sidebar {
            width: 220px;
            height: 100vh;
            background-color: #367a54;
            color: white;
            position: fixed;
            padding: 25px 15px;
            box-sizing: border-box;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            padding: 13px 15px;
            margin-bottom: 10px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #28613f;
        }

        .main {
            margin-left: 220px;
            padding: 35px;
        }

        h1 {
            color: #367a54;
        }

        .section {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 25px;
            max-width: 650px;
        }

        .section h2 {
            margin-top: 0;
            color: #367a54;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }

        button {
            padding: 12px 22px;
            background-color: #367a54;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
            background-color: #28613f;
        }

        .message {
            max-width: 650px;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            box-sizing: border-box;
        }

        .success {
            background-color: #e0f2e5;
            color: #28613f;
        }

        .error {
            background-color: #fde8e8;
            color: #c0392b;
        }

        .description {
            color: #666;
            margin-bottom: 25px;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>🌿 SpendWise</h2>

        <a href="dashboard.php">Home</a>
        <a href="transactions.php">Transactions</a>
        <a href="budget.php">Budget</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php" class="active">Settings</a>
        <a href="logout.php">Log Out</a>
    </div>

    <div class="main">

        <h1>Account Settings</h1>
        <p>Manage your personal information and account security.</p>

        <?php if ($message != "") { ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <div class="section">

            <h2>Personal Information</h2>
            <p class="description">
                Update your full name and email address.
            </p>

            <form action="settings.php" method="POST">

                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label>Full Name</label>

                    <input
                        type="text"
                        name="fullname"
                        maxlength="100"
                        value="<?php echo htmlspecialchars($user["fullname"]); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        maxlength="150"
                        value="<?php echo htmlspecialchars($user["email"]); ?>"
                        required
                    >
                </div>

                <button type="submit">Save Changes</button>

            </form>
        </div>

        <div class="section">

            <h2>Change Password</h2>
            <p class="description">
                Enter your current password to create a new password.
            </p>

            <form action="settings.php" method="POST">

                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label>Current Password</label>

                    <input
                        type="password"
                        name="current_password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>New Password</label>

                    <input
                        type="password"
                        name="new_password"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>

                    <input
                        type="password"
                        name="confirm_password"
                        minlength="8"
                        required
                    >
                </div>

                <button type="submit">Change Password</button>

            </form>
        </div>

    </div>

</body>
</html>

<?php
$conn->close();
?>
