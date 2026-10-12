
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];
$message = "";
$edit_transaction = null;

// Add, update, or delete a transaction
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "";

    if ($action == "delete") {

        $id = (int) ($_POST["id"] ?? 0);

        $stmt = $conn->prepare(
            "DELETE FROM transactions WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            header("Location: transactions.php");
            exit();
        } else {
            $message = "Unable to delete transaction.";
        }

        $stmt->close();

    } elseif ($action == "add" || $action == "update") {

        $description = trim($_POST["description"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $amount = $_POST["amount"] ?? "";
        $type = $_POST["type"] ?? "";
        $date = $_POST["transaction_date"] ?? "";

        $valid_categories = [
            "Salary",
            "Groceries",
            "Food",
            "Housing",
            "Transportation",
            "Shopping",
            "Other"
        ];

        $date_check = DateTime::createFromFormat("!Y-m-d", $date);

        if (
            $description == "" ||
            $category == "" ||
            $amount === "" ||
            $date == ""
        ) {
            $message = "Please complete all fields.";

        } elseif (!in_array($category, $valid_categories)) {
            $message = "Please select a valid category.";

        } elseif (!in_array($type, ["income", "expense"])) {
            $message = "Please select a valid transaction type.";

        } elseif (!is_numeric($amount) || $amount <= 0) {
            $message = "Please enter a valid amount.";

        } elseif (!$date_check || $date_check->format("Y-m-d") != $date) {
            $message = "Please enter a valid date.";

        } elseif ($action == "add") {

            $stmt = $conn->prepare(
                "INSERT INTO transactions
                (user_id, description, category, amount, type, transaction_date)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "issdss",
                $user_id,
                $description,
                $category,
                $amount,
                $type,
                $date
            );

            if ($stmt->execute()) {
                header("Location: transactions.php");
                exit();
            } else {
                $message = "Unable to add transaction.";
            }

            $stmt->close();

        } elseif ($action == "update") {

            $id = (int) ($_POST["id"] ?? 0);

            $stmt = $conn->prepare(
                "UPDATE transactions
                 SET description = ?,
                     category = ?,
                     amount = ?,
                     type = ?,
                     transaction_date = ?
                 WHERE id = ? AND user_id = ?"
            );

            $stmt->bind_param(
                "ssdssii",
                $description,
                $category,
                $amount,
                $type,
                $date,
                $id,
                $user_id
            );

            if ($stmt->execute()) {
                header("Location: transactions.php");
                exit();
            } else {
                $message = "Unable to update transaction.";
            }

            $stmt->close();
        }
    }
}

// Load transaction when Edit is clicked
if (isset($_GET["edit"])) {

    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT id, description, category, amount, type, transaction_date
         FROM transactions
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    $edit_transaction = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$edit_transaction) {
        $message = "Transaction not found.";
    }
}

// Get all transactions for the signed-in user
$stmt = $conn->prepare(
    "SELECT id, description, category, amount, type, transaction_date
     FROM transactions
     WHERE user_id = ?
     ORDER BY transaction_date DESC, id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Transactions</title>

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
            overflow-x: auto;
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 180px;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 7px;
        }

        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button, .button {
            display: inline-block;
            padding: 10px 18px;
            background-color: #367a54;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        button:hover, .button:hover {
            background-color: #28613f;
        }

        .cancel-button {
            background-color: #777;
            margin-left: 10px;
        }

        .delete-button {
            background-color: #c0392b;
        }

        .delete-button:hover {
            background-color: #922b21;
        }

        .edit-button {
            background-color: #367a54;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px;
            border-bottom: 1px solid #eee;
        }

        .income {
            color: #367a54;
        }

        .expense {
            color: #c0392b;
        }

        .error {
            color: #c0392b;
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
        <a href="transactions.php" class="active">Transactions</a>
        <a href="budget.php">Budget</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Log Out</a>
    </div>

    <div class="main">

        <h1>Transactions</h1>
        <p>Add and manage your income and expenses.</p>

        <div class="section">

            <h2>
                <?php echo $edit_transaction ? "Edit Transaction" : "Add Transaction"; ?>
            </h2>

            <?php if ($message != "") { ?>
                <p class="error">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php } ?>

            <form action="transactions.php" method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="<?php echo $edit_transaction ? "update" : "add"; ?>"
                >

                <?php if ($edit_transaction) { ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int) $edit_transaction["id"]; ?>"
                    >
                <?php } ?>

                <div class="form-row">

                    <div class="form-group">
                        <label>Description</label>
                        <input
                            type="text"
                            name="description"
                            maxlength="150"
                            value="<?php echo htmlspecialchars($edit_transaction["description"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Category</label>

                        <select name="category" required>
                            <option value="">Select Category</option>

                            <?php
                            $categories = [
                                "Salary",
                                "Groceries",
                                "Food",
                                "Housing",
                                "Transportation",
                                "Shopping",
                                "Other"
                            ];

                            foreach ($categories as $category) {
                                $selected = (
                                    ($edit_transaction["category"] ?? "") == $category
                                ) ? "selected" : "";

                                echo '<option value="' . htmlspecialchars($category) . '" ' . $selected . '>';
                                echo htmlspecialchars($category);
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label>Amount ($)</label>
                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            max="99999999.99"
                            value="<?php echo htmlspecialchars($edit_transaction["amount"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Type</label>

                        <select name="type" required>
                            <option value="">Select Type</option>

                            <option value="income"
                                <?php
                                if (($edit_transaction["type"] ?? "") == "income") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Income
                            </option>

                            <option value="expense"
                                <?php
                                if (($edit_transaction["type"] ?? "") == "expense") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Expense
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date</label>
                        <input
                            type="date"
                            name="transaction_date"
                            value="<?php echo htmlspecialchars($edit_transaction["transaction_date"] ?? date("Y-m-d")); ?>"
                            required
                        >
                    </div>

                </div>

                <button type="submit">
                    <?php echo $edit_transaction ? "Update Transaction" : "Add Transaction"; ?>
                </button>

                <?php if ($edit_transaction) { ?>
                    <a href="transactions.php" class="button cancel-button">
                        Cancel
                    </a>
                <?php } ?>

            </form>
        </div>

        <div class="section">

            <h2>Transaction History</h2>

            <table>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>

                <?php if ($result->num_rows > 0) { ?>

                    <?php while ($row = $result->fetch_assoc()) { ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row["transaction_date"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["description"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row["category"]); ?>
                            </td>

                            <td>
                                <?php echo ucfirst($row["type"]); ?>
                            </td>

                            <td class="<?php echo $row["type"] == "income" ? "income" : "expense"; ?>">
                                <?php echo $row["type"] == "income" ? "+" : "-"; ?>
                                $<?php echo number_format((float) $row["amount"], 2); ?>
                            </td>

                            <td>
                                <div class="actions">

                                    <a
                                        href="transactions.php?edit=<?php echo (int) $row["id"]; ?>"
                                        class="button edit-button"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="transactions.php"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this transaction?');"
                                    >
                                        <input type="hidden" name="action" value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int) $row["id"]; ?>"
                                        >

                                        <button type="submit" class="delete-button">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6">No transactions yet.</td>
                    </tr>

                <?php } ?>

            </table>
        </div>

    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
