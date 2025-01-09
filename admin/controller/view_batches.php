<?php
include('../includes/dbconnection.php');

// Define thresholds
define('EXPIRATION_THRESHOLD', 7); // Days before expiration to consider as "Expiring Soon"
define('MIN_THRESHOLD', 5);         // Minimum acceptable quantity

$productID = intval($_GET['productID'] ?? 0);
$html = '';

if ($productID <= 0) {
    echo "<p class='text-danger'>Invalid product ID.</p>";
    exit();
}

$sql = "SELECT InventoryID, Quantity, OrderQuantity, ExpirationDate, Date_Created
        FROM tblinventory
        WHERE ProductID = ?
        ORDER BY Date_Created DESC";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $productID);
$stmt->execute();
$result = $stmt->get_result();

$html .= '<div class="table-responsive">';
$html .= '<table class="table table-striped">';
$html .= '<thead>
            <tr>
                <th>Inventory ID</th>
                <th>Order Quantity</th>
                <th>Remaining Quantity</th>
                <th>Expiration Date</th>
                <th>Date Created</th>
                <th>Days Remaining</th>
                <th>Quantity Status</th> <!-- New Column -->
                <th>Expiration Status</th> <!-- Existing Column -->
            </tr>
          </thead>
          <tbody>';

while ($row = $result->fetch_assoc()) {

    $inventoryID       = htmlspecialchars($row['InventoryID']);
    $orderQuantity     = htmlspecialchars($row['OrderQuantity']);
    $remainingQuantity = intval($row['Quantity']); // Ensure it's an integer
    $expirationDateRaw = $row['ExpirationDate'];
    $dateCreated       = htmlspecialchars($row['Date_Created']);

    // Determine Expiration Date Display
    $expirationDateDisplay = (!empty($expirationDateRaw)) ? htmlspecialchars($expirationDateRaw) : 'N/A';

    // Initialize Days Remaining and Expiration Status
    $daysRemaining     = 'N/A';
    $expiration_status = '';

    if (!empty($expirationDateRaw)) {
        $expirationDate = new DateTime($expirationDateRaw);
        $currentDate     = new DateTime();
        $interval        = $currentDate->diff($expirationDate);
        $daysRemainingRaw = (int)$interval->format('%r%a'); // %r for sign, %a for absolute days

        if ($daysRemainingRaw < 0) {
            // Batch has already expired
            $expiration_status = '<span class="badge bg-danger">Expired</span>';
            $daysRemaining     = abs($daysRemainingRaw) . ' day(s) ago';
        } elseif ($daysRemainingRaw <= EXPIRATION_THRESHOLD) {
            // Expiring Soon
            $expiration_status = '<span class="badge bg-warning text-dark">Expiring Soon</span>';
            $daysRemaining     = $daysRemainingRaw . ' day(s) remaining';
        } else {
            // Valid
            $expiration_status = '<span class="badge bg-success">Valid</span>';
            $daysRemaining     = $daysRemainingRaw . ' day(s) remaining';
        }
    } else {
        // No Expiration
        $expiration_status = '<span class="badge bg-secondary">No Expiration</span>';
    }

    // Determine Quantity Status
    $quantity_status = '';
    if ($remainingQuantity <= 0) {
        $quantity_status = '<span class="badge bg-secondary">Out of Stock</span>';
    } elseif ($remainingQuantity < MIN_THRESHOLD) {
        $quantity_status = '<span class="badge bg-danger">Low Stock</span>';
    } else {
        $quantity_status = '<span class="badge bg-success">Available</span>';
    }

    // Optional: Add tooltips for more detailed information
    $tooltip = '';
    if (!empty($expirationDateRaw)) {
        if ($daysRemainingRaw < 0) {
            $tooltip = "Expired " . abs($daysRemainingRaw) . " day(s) ago";
        } elseif ($daysRemainingRaw <= EXPIRATION_THRESHOLD) {
            $tooltip = "Expires in " . $daysRemainingRaw . " day(s)";
        } else {
            $tooltip = "Valid for " . $daysRemainingRaw . " more day(s)";
        }
    }

    // Optional: Apply row classes based on quantity status for better visibility
    $row_class = '';
    if ($remainingQuantity <= 0) {
        $row_class = 'table-secondary'; // Grey for out of stock
    } elseif ($remainingQuantity < MIN_THRESHOLD) {
        $row_class = 'table-danger'; // Red for low stock
    } else {
        $row_class = 'table-success'; // Green for available
    }

    // Build Table Row with conditional class and tooltip
    $html .= "<tr class='{$row_class}'>
                <td>{$inventoryID}</td>
                <td>{$orderQuantity}</td>
                <td>{$remainingQuantity}</td>
                <td>{$expirationDateDisplay}</td>
                <td>{$dateCreated}</td>
                <td>{$daysRemaining}</td> <!-- Days Remaining -->
                <td>{$quantity_status}</td> <!-- Quantity Status -->
                <td>{$expiration_status}</td> <!-- Expiration Status -->
              </tr>";
}
$html .= '</tbody></table>';
$html .= '</div>'; // Close table-responsive

echo $html;
?>
