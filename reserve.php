```php
<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$customer_name = trim($_POST["customer_name"] ?? "");
$room_id = $_POST["room_id"] ?? "";
$check_in = $_POST["check_in"] ?? "";
$check_out = $_POST["check_out"] ?? "";

if (
    empty($customer_name) ||
    empty($room_id) ||
    empty($check_in) ||
    empty($check_out)
) {
    header("Location: index.php?error=Please complete all fields.");
    exit;
}

if ($check_out <= $check_in) {
    header("Location: index.php?error=Check-out date must be after check-in date.");
    exit;
}

try {

    // ==========================================
    // START TRANSACTION
    // ==========================================
    $pdo->beginTransaction();

    // ==========================================
    // OPERATION 1:
    // Check selected room
    // ==========================================
    $stmt = $pdo->prepare("
        SELECT *
        FROM rooms
        WHERE room_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$room_id]);

    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        throw new Exception("The selected room does not exist.");
    }

    // ==========================================
    // CHECK ROOM STATUS
    // ==========================================
    if ($room["status"] !== "Available") {

        $roomNumber = $room["room_number"];

        throw new Exception(
            "Room $roomNumber is already reserved. Please select another available room."
        );
    }

    // ==========================================
    // OPERATION 2:
    // Insert reservation
    // ==========================================
    $stmt = $pdo->prepare("
        INSERT INTO reservations
        (
            customer_name,
            room_id,
            check_in,
            check_out
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $customer_name,
        $room_id,
        $check_in,
        $check_out
    ]);

    // ==========================================
    // OPERATION 3:
    // Update room status
    // ==========================================
    $stmt = $pdo->prepare("
        UPDATE rooms
        SET status = 'Reserved'
        WHERE room_id = ?
    ");

    $stmt->execute([$room_id]);

    // ==========================================
    // OPERATION 4:
    // SUCCESS AUDIT LOG
    // ==========================================
    $stmt = $pdo->prepare("
        INSERT INTO audit_log
        (
            action,
            status,
            message
        )
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        "Hotel Reservation",
        "SUCCESS",
        "Room " . $room["room_number"] .
        " successfully reserved by " . $customer_name
    ]);

    // ==========================================
    // COMMIT
    // ==========================================
    $pdo->commit();

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>Reservation Successful</title>

        <link rel="stylesheet"
              href="style.css">
    </head>

    <body>

    <div class="container">

        <div class="message success">

            <div class="icon">✓</div>

            <h1>Reservation Successful!</h1>

            <p>
                Your hotel reservation has been successfully completed.
            </p>

            <div class="details">

                <p>
                    <strong>Customer:</strong>
                    <?php echo htmlspecialchars($customer_name); ?>
                </p>

                <p>
                    <strong>Room:</strong>
                    <?php echo htmlspecialchars($room["room_number"]); ?>
                </p>

                <p>
                    <strong>Room Type:</strong>
                    <?php echo htmlspecialchars($room["room_type"]); ?>
                </p>

                <p>
                    <strong>Check-in:</strong>
                    <?php echo htmlspecialchars($check_in); ?>
                </p>

                <p>
                    <strong>Check-out:</strong>
                    <?php echo htmlspecialchars($check_out); ?>
                </p>

            </div>

            <div class="transaction success-transaction">

                <strong>Transaction Status</strong>

                <br>

                COMMIT

                <p>
                    All reservation operations were completed successfully.
                </p>

            </div>

            <a href="index.php"
               class="back-button">

                Back to Reservation

            </a>

        </div>

    </div>

    </body>

    </html>

    <?php

} catch (Exception $e) {

    // ==========================================
    // ROLLBACK
    // ==========================================
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // ==========================================
    // SAVE FAILED TRANSACTION TO AUDIT LOG
    // ==========================================
    try {

        $stmt = $pdo->prepare("
            INSERT INTO audit_log
            (
                action,
                status,
                message
            )
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            "Hotel Reservation",
            "FAILED",
            $e->getMessage()
        ]);

    } catch (Exception $logError) {

        // Prevent audit-log error from hiding
        // the original reservation error.

    }

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1.0">

        <title>Reservation Failed</title>

        <link rel="stylesheet"
              href="style.css">

    </head>

    <body>

    <div class="container">

        <div class="message failed">

            <div class="icon">✕</div>

            <h1>Reservation Failed</h1>

            <div class="error-description">

                <strong>Reason:</strong>

                <p>
                    <?php
                    echo htmlspecialchars($e->getMessage());
                    ?>
                </p>

            </div>

            <div class="transaction failed-transaction">

                <strong>Transaction Status</strong>

                <br>

                ROLLBACK

                <p>
                    The reservation was not saved because the
                    transaction failed.
                </p>

            </div>

            <div class="notice">

                Please select another available room
                and try again.

            </div>

            <a href="index.php"
               class="back-button">

                Select Another Room

            </a>

        </div>

    </div>

    </body>

    </html>

    <?php
}

?>

