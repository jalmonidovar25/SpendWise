
<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($fullname == "" || $email == "" || $password == "") {
        die("Please complete all fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Please enter a valid email address.");
    }

    if ($password != $confirm_password) {
        die("Passwords do not match.");
    }

    if (strlen($password) < 8) {
        die("Password must contain at least 8 characters.");
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        die("This email is already registered.");
    }

    $check->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        echo "Account created successfully! ";
        echo '<a href="index.php">Go to Sign In</a>';
    } else {
        echo "Unable to create account.";
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
    exit();
}

?>
