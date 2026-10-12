
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];
$message = "";
$edit_budget = null;

$categories = [
    "Groceries",
    "Food",
    "Housing",
    "Transportation",
    "Shopping",
    "Other"
];

$current_month = date("Y-m");
$selected_month = $_GET["month"] ?? $current_month;

if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $selected_month)) {
    $selected_month = $current_month;
}

// Add, update, or delete a budget
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "";
    $category = $_POST["category"] ?? "";
    $amount = $_POST["amount"] ?? "";
    $budget_month = $_POST["budget_month"] ?? $current_month;
    $id = (int) ($_POST["id"] ?? 0);

    if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $budget_month)) {
        $message = "Invalid budget month.";

    } elseif ($action == "delete") {

        $stmt = $conn->prepare(
            "DELETE FROM budgets WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            header("Location: budget.php?month=" . $budget_month);
            exit();
        } else {
            $message = "Unable to delete budget.";
        }

        $stmt->close();

    } elseif ($action == "add" || $action == "update") {

        if (!in_array($category, $categories)) {
            $message = "Please select a valid category.";

        } elseif (!is_numeric($amount) || $amount <= 0 || $amount > 99999999.99) {
            $message = "Please enter a valid budget amount.";

        } elseif ($action == "add") {

            // Check whether a budget already exists
            $stmt = $conn->prepare(
                "SELECT id FROM budgets
                 WHERE user_id = ? AND category = ? AND budget_month = ?"
            );

            $stmt->bind_param("iss", $user_id, $category, $budget_month);
            $stmt->execute();

            $existing = $stmt->get_result();

            if ($existing->num_rows > 0) {
                $message = "A budget already exists for this category and month.";
            }

            $stmt->close();

            if ($message == "") {

                $stmt = $conn->prepare(
                    "INSERT INTO budgets
                     (user_id, category, amount, budget_month)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "isds",
                    $user_id,
                    $category,
                    $amount,
                    $budget_month
                );

                if ($stmt->execute()) {
                    header("Location: budget.php?month=" . $budget_month);
                    exit();
                } else {
                    $message = "Unable to add budget.";
                }

                $stmt->close();
            }

        } elseif ($action == "update") {

            $stmt = $conn->prepare(
                "SELECT id FROM budgets
                 WHERE user_id = ?
                 AND category = ?
                 AND budget_month = ?
                 AND id != ?"
            );

            $stmt->bind_param(
                "issi",
                $user_id,
                $category,
                $budget_month,
                $id
            );

            $stmt->execute();
            $existing = $stmt->get_result();

            if ($existing->num_rows > 0) {
                $message = "A budget already exists for this category and month.";
            }

            $stmt->close();

            if ($message == "") {

                $stmt = $conn->prepare(
                    "UPDATE budgets
                     SET category = ?, amount = ?, budget_month = ?
                     WHERE id = ? AND user_id = ?"
                );

                $stmt->bind_param(
                    "sdsii",
                    $category,
                    $amount,
                    $budget_month,
                    $id,
                    $user_id
                );

                if ($stmt->execute()) {
                    header("Location: budget.php?month=" . $budget_month);
                    exit();
                } else {
                    $message = "Unable to update budget.";
                }

                $stmt->close();
            }
        }
    }
}

// Load the selected budget for editing
if (isset($_GET["edit"])) {

    $id = (int) $_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT id, category, amount, budget_month
         FROM budgets
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    $edit_budget = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($edit_budget) {
        $selected_month = $edit_budget["budget_month"];
    } else {
        $message = "Budget not found.";
    }
}

// Get budgets and actual expenses for selected month
$stmt = $conn->prepare(
    "SELECT b.id, b.category, b.amount, b.budget_month,
        COALESCE(
            (
                SELECT SUM(t.amount)
                FROM transactions t
                WHERE t.user_id = b.user_id
                AND t.category = b.category
                AND t.type = 'expense'
                AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.budget_month
            ), 0
        ) AS spent
     FROM budgets b
     WHERE b.user_id = ? AND b.budget_month = ?
     ORDER BY b.category"
);

$stmt->bind_param("is", $user_id, $selected_month);
$stmt->execute();

$result = $stmt->get_result();

$total_budget = 0;
$total_spent = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Budget</title>

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

        .over-budget {
            color: #c0392b;
            font-weight: bold;
        }

        .within-budget {
            color: #367a54;
        }

        .error {
            color: #c0392b;
        }

        .summary {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .summary-card {
            flex: 1;
            min-width: 160px;
            background-color: #f0f7f2;
            padding: 20px;
            border-radius: 8px;
        }

        .summary-card h3 {
            margin-top: 0;
            font-size: 15px;
        }

        .summary-card p {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 0;
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
        <a href="budget.php" class="active">Budget</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Log Out</a>
    </div>

    <div class="main">

        <h1>Budget Management</h1>
        <p>Set monthly budgets and track your spending.</p>

        <div class="section">

            <h2>Select Month</h2>

            <form action="budget.php" method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <input
                            type="month"
                            name="month"
                            value="<?php echo htmlspecialchars($selected_month); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit">View Budget</button>
                    </div>
                </div>
            </form>

        </div>

        <div class="section">

            <h2>
                <?php echo $edit_budget ? "Edit Budget" : "Add Budget"; ?>
            </h2>

            <?php if ($message != "") { ?>
                <p class="error">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php } ?>

            <form action="budget.php" method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="<?php echo $edit_budget ? "update" : "add"; ?>"
                >

                <?php if ($edit_budget) { ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo (int) $edit_budget["id"]; ?>"
                    >
                <?php } ?>

                <div class="form-row">

                    <div class="form-group">
                        <label>Category</label>

                        <select name="category" required>
                            <option value="">Select Category</option>

                            <?php foreach ($categories as $category) { ?>

                                <option
                                    value="<?php echo htmlspecialchars($category); ?>"
                                    <?php
                                    if (($edit_budget["category"] ?? "") == $category) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($category); ?>
                                </option>

                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Monthly Budget ($)</label>

                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            max="99999999.99"
                            value="<?php echo htmlspecialchars($edit_budget["amount"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Budget Month</label>

                        <input
                            type="month"
                            name="budget_month"
                            value="<?php echo htmlspecialchars($edit_budget["budget_month"] ?? $selected_month); ?>"
                            required
                        >
                    </div>

                </div>

                <button type="submit">
                    <?php echo $edit_budget ? "Update Budget" : "Add Budget"; ?>
                </button>

                <?php if ($edit_budget) { ?>
                    <a href="budget.php?month=<?php echo urlencode($selected_month); ?>"
                       class="button cancel-button">
                        Cancel
                    </a>
                <?php } ?>

            </form>
        </div>

        <div class="section">

            <h2>Monthly Budget Overview</h2>

            <table>
                <tr>
                    <th>Category</th>
                    <th>Budget</th>
                    <th>Spent</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>

                <?php if ($result->num_rows > 0) { ?>

                    <?php while ($row = $result->fetch_assoc()) { ?>

                        <?php
                        $budget_amount = (float) $row["amount"];
                        $spent = (float) $row["spent"];
                        $remaining = $budget_amount - $spent;

                        $total_budget += $budget_amount;
                        $total_spent += $spent;
                        ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row["category"]); ?>
                            </td>

                            <td>
                                $<?php echo number_format($budget_amount, 2); ?>
                            </td>

                            <td>
                                $<?php echo number_format($spent, 2); ?>
                            </td>

                            <td class="<?php echo $remaining < 0 ? "over-budget" : "within-budget"; ?>">
                                $<?php echo number_format($remaining, 2); ?>
                            </td>

                            <td>
                                <?php echo $remaining < 0 ? "Over Budget" : "Within Budget"; ?>
                            </td>

                            <td>
                                <div class="actions">

                                    <a
                                        href="budget.php?edit=<?php echo (int) $row["id"]; ?>"
                                        class="button"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="budget.php"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this budget?');"
                                    >
                                        <input type="hidden" name="action" value="delete">

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int) $row["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="budget_month"
                                            value="<?php echo htmlspecialchars($selected_month); ?>"
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
                        <td colspan="6">
                            No budgets for this month.
                        </td>
                    </tr>

                <?php } ?>

            </table>
        </div>

        <div class="section">

            <h2>Budget Summary</h2>

            <div class="summary">

                <div class="summary-card">
                    <h3>Total Budget</h3>
                    <p>$<?php echo number_format($total_budget, 2); ?></p>
                </div>

                <div class="summary-card">
                    <h3>Total Spent</h3>
                    <p>$<?php echo number_format($total_spent, 2); ?></p>
                </div>

                <div class="summary-card">
                    <h3>Remaining Budget</h3>
                    <p>
                        $<?php echo number_format($total_budget - $total_spent, 2); ?>
                    </p>
                </div>

            </div>

        </div>

    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
