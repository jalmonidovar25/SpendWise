
<?php

session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email == "" || $password == "") {
        die("Please enter your email and password.");
    }

    $stmt = $conn->prepare(
        "SELECT id, fullname, password FROM users WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $fullname, $hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {

        $_SESSION["user_id"] = $id;
        $_SESSION["fullname"] = $fullname;

        header("Location: dashboard.php");
        exit();

    } else {
        echo "Invalid email or password. ";
        echo '<a href="index.php">Try Again</a>';
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: index.php");
    exit();
}

?>
