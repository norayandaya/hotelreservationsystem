<?php
require_once "db.php";

$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation System</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>🏨 Hotel Reservation System</h1>
    <p class="subtitle">Reserve your hotel room</p>

    <?php if (isset($_GET['error'])): ?>

        <div class="notification failed">
            <h3>❌ Reservation Failed</h3>
            <p>
                <?= htmlspecialchars($_GET['error']); ?>
            </p>
        </div>

    <?php endif; ?>


    <div class="reservation-box">

        <h2>Make a Reservation</h2>

        <form action="reserve.php" method="POST">

            <!-- Customer Name -->
            <label for="customer_name">
                Customer Name
            </label>

            <input
                type="text"
                id="customer_name"
                name="customer_name"
                placeholder="Enter customer name"
                required
            >


            <!-- Room -->
            <label for="room_id">
                Select Room
            </label>

            <select
                id="room_id"
                name="room_id"
                required
            >

                <option value="">
                    -- Select a Room --
                </option>

                <?php foreach ($rooms as $room): ?>

                    <option value="<?= $room['room_id']; ?>">

                        Room <?= htmlspecialchars($room['room_number']); ?>

                        -
                        <?= htmlspecialchars($room['room_type']); ?>

                        -
                        ₱<?= number_format($room['price'], 2); ?>

                        -
                        <?= htmlspecialchars($room['status']); ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- Check-in -->
            <label for="check_in">
                Check-in Date
            </label>

            <input
                type="date"
                id="check_in"
                name="check_in"
                required
            >


            <!-- Check-out -->
            <label for="check_out">
                Check-out Date
            </label>

            <input
                type="date"
                id="check_out"
                name="check_out"
                required
            >


            <button type="submit">
                Submit Reservation
            </button>

        </form>

    </div>


    <!-- Room Availability -->

    <div class="rooms">

        <h2>Room Availability</h2>

        <table>

            <tr>
                <th>Room Number</th>
                <th>Room Type</th>
                <th>Price</th>
                <th>Status</th>
            </tr>

            <?php foreach ($rooms as $room): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($room['room_number']); ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($room['room_type']); ?>
                    </td>

                    <td>
                        ₱<?= number_format($room['price'], 2); ?>
                    </td>

                    <td>

                        <?php if ($room['status'] === 'Available'): ?>

                            <span class="available">
                                Available
                            </span>

                        <?php else: ?>

                            <span class="reserved">
                                Reserved
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

    </div>

</div>

</body>
</html>