<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');
if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

function fetchSalesData($con, $interval) {
    if ($interval == '1 DAY') {
        $query = "SELECT 
                    DATE_FORMAT(tbltransaction.Transaction_Date, '%Y-%m-%d') as date, 
                    SUM(tblservices.Cost * tbltransaction.Qty) AS serviceTotal,
                    SUM(tblproducts.price * tbltransaction.Qty) AS productTotal,
                    (SUM(tblservices.Cost * tbltransaction.Qty) + SUM(tblproducts.price * tbltransaction.Qty)) AS totalSales
                    FROM tbltransaction
                    LEFT JOIN tblservices ON tblservices.service_id = tbltransaction.service_id
                    LEFT JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID
                    WHERE tbltransaction.Transaction_Date >= CURDATE() 
                    GROUP BY date
                    ORDER BY date ASC";
    } elseif ($interval == '1 MONTH') {
        
        $query = "SELECT 
                    DATE_FORMAT(tbltransaction.Transaction_Date, '%Y-%m') as month, 
                    SUM(tblservices.Cost * tbltransaction.Qty) AS serviceTotal,
                    SUM(tblproducts.price * tbltransaction.Qty) AS productTotal,
                    (SUM(tblservices.Cost * tbltransaction.Qty) + SUM(tblproducts.price * tbltransaction.Qty)) AS totalSales
                    FROM tbltransaction
                    LEFT JOIN tblservices ON tblservices.service_id = tbltransaction.service_id
                    LEFT JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID
                    WHERE tbltransaction.Transaction_Date >= DATE_FORMAT(CURDATE(), '%Y-01-01') 
                    GROUP BY month
                    ORDER BY month ASC";
    } else {
       
        $query = "SELECT 
                    DATE_FORMAT(tbltransaction.Transaction_Date, '%Y') as year, 
                    SUM(tblservices.Cost * tbltransaction.Qty) AS serviceTotal,
                    SUM(tblproducts.price * tbltransaction.Qty) AS productTotal,
                    (SUM(tblservices.Cost * tbltransaction.Qty) + SUM(tblproducts.price * tbltransaction.Qty)) AS totalSales
                    FROM tbltransaction
                    LEFT JOIN tblservices ON tblservices.service_id = tbltransaction.service_id
                    LEFT JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID
                    WHERE tbltransaction.Transaction_Date >= '2024-01-01' -- Adjust to any desired start year
                    GROUP BY year
                    ORDER BY year ASC";
    }
    
    $result = $con->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

$dailySales = fetchSalesData($con, '1 DAY');
$monthlySales = fetchSalesData($con, '1 MONTH');
$yearlySales = fetchSalesData($con, '1 YEAR');
?>

<!DOCTYPE HTML>
<head>
    <title>ALVSC || Sales Report</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
    h3.title1 {
        font-size: 2em;
        color: #000000;
        margin-bottom: 0.8em;
    }
</style>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <h3 class="title1">Sales Report</h3>

                    <button class="btn btn-primary" onclick="showReport('daily')">Daily Sales</button>
                    <button class="btn btn-primary" onclick="showReport('monthly')">Monthly Sales</button>
                    <button class="btn btn-primary" onclick="showReport('yearly')">Yearly Sales</button>

                    <div class="chart-container" style="width: 80%; margin: 0 auto;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const dailySales = <?php echo json_encode($dailySales); ?>;
        const monthlySales = <?php echo json_encode($monthlySales); ?>;
        const yearlySales = <?php echo json_encode($yearlySales); ?>;

        function formatSalesData(salesData, reportType) {
            const dates = salesData.map(sale => {
                if (reportType === 'monthly') {
                    return sale.month; 
                } else if (reportType === 'yearly') {
                    return sale.year; 
                }
                return sale.date; 
            });

            const serviceSales = salesData.map(sale => sale.serviceTotal);
            const productSales = salesData.map(sale => sale.productTotal);
            const totalSales = salesData.map(sale => sale.totalSales);

            return { dates, serviceSales, productSales, totalSales };
        }

        let ctx = document.getElementById('salesChart').getContext('2d');
        let salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [], 
                datasets: [
                    {
                        label: 'Service Sales',
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        data: [] 
                    },
                    {
                        label: 'Product Sales',
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        data: [] 
                    },
                    {
                        label: 'Total Sales',
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        data: [] 
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales Amount'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date' 
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        function showReport(reportType) {
            let salesData;

            if (reportType === 'daily') {
                salesData = formatSalesData(dailySales, reportType);
            } else if (reportType === 'monthly') {
                salesData = formatSalesData(monthlySales, reportType);
            } else if (reportType === 'yearly') {
                salesData = formatSalesData(yearlySales, reportType);
            }

            salesChart.data.labels = salesData.dates;
            salesChart.data.datasets[0].data = salesData.serviceSales;
            salesChart.data.datasets[1].data = salesData.productSales;
            salesChart.data.datasets[2].data = salesData.totalSales;
            salesChart.options.scales.x.title.text = reportType === 'monthly' ? 'Month' : (reportType === 'yearly' ? 'Year' : 'Date');
            salesChart.update();
        }

        showReport('daily');
    </script>
</body>
</html>
