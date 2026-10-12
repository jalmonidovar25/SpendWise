
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];

$current_month = date("Y-m");
$selected_month = $_GET["month"] ?? $current_month;

if (!preg_match("/^\d{4}-(0[1-9]|1[0-2])$/", $selected_month)) {
    $selected_month = $current_month;
}

$start_date = $selected_month . "-01";
$end_date = date("Y-m-d", strtotime($start_date . " +1 month"));

// Get income and expenses for selected month
$stmt = $conn->prepare(
    "SELECT type, amount
     FROM transactions
     WHERE user_id = ?
     AND transaction_date >= ?
     AND transaction_date < ?"
);

$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$total_income = 0;
$total_expenses = 0;

while ($row = $result->fetch_assoc()) {
    if ($row["type"] == "income") {
        $total_income += (float) $row["amount"];
    } else {
        $total_expenses += (float) $row["amount"];
    }
}

$stmt->close();

$net_balance = $total_income - $total_expenses;

// Get expenses by category
$stmt = $conn->prepare(
    "SELECT category, SUM(amount) AS total
     FROM transactions
     WHERE user_id = ?
     AND type = 'expense'
     AND transaction_date >= ?
     AND transaction_date < ?
     GROUP BY category
     ORDER BY total DESC"
);

$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();

$category_result = $stmt->get_result();
$categories = [];

while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

$stmt->close();

// Get monthly income and expenses for last 6 months
$monthly_reports = [];

for ($i = 5; $i >= 0; $i--) {
    $month = date("Y-m", strtotime("first day of this month -$i months"));
    $month_start = $month . "-01";
    $month_end = date("Y-m-d", strtotime($month_start . " +1 month"));

    $stmt = $conn->prepare(
        "SELECT type, amount
         FROM transactions
         WHERE user_id = ?
         AND transaction_date >= ?
         AND transaction_date < ?"
    );

    $stmt->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt->execute();
    $monthly_result = $stmt->get_result();

    $income = 0;
    $expenses = 0;

    while ($row = $monthly_result->fetch_assoc()) {
        if ($row["type"] == "income") {
            $income += (float) $row["amount"];
        } else {
            $expenses += (float) $row["amount"];
        }
    }

    $monthly_reports[] = [
        "month" => date("M Y", strtotime($month_start)),
        "income" => $income,
        "expenses" => $expenses
    ];

    $stmt->close();
}

$max_category = 0;

foreach ($categories as $category) {
    if ($category["total"] > $max_category) {
        $max_category = $category["total"];
    }
}

$max_monthly = 0;

foreach ($monthly_reports as $report) {
    if ($report["income"] > $max_monthly) {
        $max_monthly = $report["income"];
    }

    if ($report["expenses"] > $max_monthly) {
        $max_monthly = $report["expenses"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpendWise - Reports</title>

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
        }

        .cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .card {
            flex: 1;
            min-width: 170px;
            padding: 20px;
            background-color: #f0f7f2;
            border-radius: 10px;
        }

        .card h3 {
            margin-top: 0;
            font-size: 15px;
            color: #666;
        }

        .card p {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 0;
        }

        .green {
            color: #367a54;
        }

        .red {
            color: #c0392b;
        }

        .bar-row {
            margin: 18px 0;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 7px;
        }

        .bar-background {
            width: 100%;
            background-color: #e5e5e5;
            height: 20px;
            border-radius: 5px;
            overflow: hidden;
        }

        .bar {
            height: 100%;
            border-radius: 5px;
        }

        .bar-expense {
            background-color: #c0392b;
        }

        .bar-income {
            background-color: #367a54;
        }

        .month-report {
            margin-bottom: 28px;
        }

        .month-report h3 {
            margin-bottom: 10px;
        }

        .month-report p {
            margin: 6px 0;
            font-size: 14px;
        }

        input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            padding: 10px 18px;
            background-color: #367a54;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background-color: #28613f;
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
        <a href="reports.php" class="active">Reports</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Log Out</a>
    </div>

    <div class="main">

        <h1>Financial Reports</h1>
        <p>View your income and spending summary.</p>

        <div class="section">
            <h2>Select Month</h2>

            <form action="reports.php" method="GET">
                <input
                    type="month"
                    name="month"
                    value="<?php echo htmlspecialchars($selected_month); ?>"
                    required
                >

                <button type="submit">View Report</button>
            </form>
        </div>

        <div class="section">
            <h2>
                Financial Summary -
                <?php echo date("F Y", strtotime($start_date)); ?>
            </h2>

            <div class="cards">

                <div class="card">
                    <h3>Total Income</h3>
                    <p class="green">
                        $<?php echo number_format($total_income, 2); ?>
                    </p>
                </div>

                <div class="card">
                    <h3>Total Expenses</h3>
                    <p class="red">
                        $<?php echo number_format($total_expenses, 2); ?>
                    </p>
                </div>

                <div class="card">
                    <h3>Net Balance</h3>
                    <p class="<?php echo $net_balance < 0 ? "red" : "green"; ?>">
                        $<?php echo number_format($net_balance, 2); ?>
                    </p>
                </div>

            </div>
        </div>

        <div class="section">
            <h2>Expenses by Category</h2>

            <?php if (count($categories) > 0) { ?>

                <?php foreach ($categories as $category) { ?>

                    <?php
                    $percentage = 0;

                    if ($max_category > 0) {
                        $percentage = ($category["total"] / $max_category) * 100;
                    }
                    ?>

                    <div class="bar-row">

                        <div class="bar-label">
                            <span>
                                <?php echo htmlspecialchars($category["category"]); ?>
                            </span>

                            <span>
                                $<?php echo number_format((float) $category["total"], 2); ?>
                            </span>
                        </div>

                        <div class="bar-background">
                            <div
                                class="bar bar-expense"
                                style="width: <?php echo $percentage; ?>%;"
                            ></div>
                        </div>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <p>No expenses recorded for this month.</p>

            <?php } ?>

        </div>

        <div class="section">
            <h2>Income vs Expenses - Last 6 Months</h2>

            <?php foreach ($monthly_reports as $report) { ?>

                <?php
                $income_percentage = 0;
                $expense_percentage = 0;

                if ($max_monthly > 0) {
                    $income_percentage = ($report["income"] / $max_monthly) * 100;
                    $expense_percentage = ($report["expenses"] / $max_monthly) * 100;
                }
                ?>

                <div class="month-report">

                    <h3>
                        <?php echo htmlspecialchars($report["month"]); ?>
                    </h3>

                    <p>
                        Income: $<?php echo number_format($report["income"], 2); ?>
                    </p>

                    <div class="bar-background">
                        <div
                            class="bar bar-income"
                            style="width: <?php echo $income_percentage; ?>%;"
                        ></div>
                    </div>

                    <p>
                        Expenses: $<?php echo number_format($report["expenses"], 2); ?>
                    </p>

                    <div class="bar-background">
                        <div
                            class="bar bar-expense"
                            style="width: <?php echo $expense_percentage; ?>%;"
                        ></div>
                    </div>

                </div>

            <?php } ?>

        </div>

    </div>

</body>
</html>

<?php
$conn->close();
?>
