
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];
$fullname = $_SESSION["fullname"];
$firstname = explode(" ", $fullname)[0];

// Get total income and expenses
$stmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expenses
     FROM transactions
     WHERE user_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_income = (float) $summary["total_income"];
$total_expenses = (float) $summary["total_expenses"];
$total_balance = $total_income - $total_expenses;

// Get the 5 most recent transactions
$stmt = $conn->prepare(
    "SELECT description, category, amount, type, transaction_date
     FROM transactions
     WHERE user_id = ?
     ORDER BY transaction_date DESC, id DESC
     LIMIT 5"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Dashboard</title>

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

        .cards {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .card {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            flex: 1;
            min-width: 160px;
        }

        .card h3 {
            margin-top: 0;
            color: #666;
            font-size: 16px;
        }

        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #367a54;
        }

        .expenses-amount {
            color: #c0392b;
        }

        .transactions {
            background-color: white;
            padding: 25px;
            margin-top: 30px;
            border-radius: 12px;
            overflow-x: auto;
        }

        .transactions h2 {
            color: #367a54;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #eee;
        }

        th {
            color: #666;
        }

        .income {
            color: #367a54;
        }

        .expense {
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

        <a href="dashboard.php" class="active">Home</a>
        <a href="transactions.php">Transactions</a>
        <a href="budget.php">Budget</a>
        <a href="reports.php">Reports</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Log Out</a>
    </div>

    <div class="main">

        <h1>Hello, <?php echo htmlspecialchars($firstname); ?>!</h1>
        <p>Here is your financial overview.</p>

        <div class="cards">

            <div class="card">
                <h3>Total Balance</h3>
                <div class="amount">
                    $<?php echo number_format($total_balance, 2); ?>
                </div>
            </div>

            <div class="card">
                <h3>Income</h3>
                <div class="amount">
                    $<?php echo number_format($total_income, 2); ?>
                </div>
            </div>

            <div class="card">
                <h3>Expenses</h3>
                <div class="amount expenses-amount">
                    $<?php echo number_format($total_expenses, 2); ?>
                </div>
            </div>

            <div class="card">
                <h3>Savings</h3>
                <div class="amount">$0.00</div>
            </div>

        </div>

        <div class="transactions">
            <h2>Recent Transactions</h2>

            <table>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Amount</th>
                </tr>

                <?php if ($transactions->num_rows > 0) { ?>

                    <?php while ($row = $transactions->fetch_assoc()) { ?>
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

                            <td class="<?php echo $row["type"] == "income" ? "income" : "expense"; ?>">
                                <?php echo $row["type"] == "income" ? "+" : "-"; ?>
                                $<?php echo number_format((float) $row["amount"], 2); ?>
                            </td>
                        </tr>
                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4">
                            No transactions yet.
                            <a href="transactions.php">Add your first transaction</a>.
                        </td>
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
