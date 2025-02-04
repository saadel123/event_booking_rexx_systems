<?php
require 'database.php';

try {
    $jsonData = file_get_contents('data/Code_Challenge_Events_.json');
    $bookings = json_decode($jsonData, true);

    if (!$bookings) {
        die("Error");
    }

    $existingEmployees = [];
    foreach ($pdo->query("SELECT id, email FROM employees") as $row) {
        $existingEmployees[$row['email']] = $row['id'];
    }

    $existingEvents = [];
    foreach ($pdo->query("SELECT id, name FROM events") as $row) {
        $existingEvents[$row['name']] = $row['id'];
    }

    $employeeStmt = $pdo->prepare("INSERT INTO employees (name, email) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $eventStmt = $pdo->prepare("INSERT INTO events (name) VALUES (?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $bookingStmt = $pdo->prepare("INSERT INTO bookings (employee_id, event_id, participation_fee, event_date, version) VALUES (?, ?, ?, ?, ?)");

    $checkBookingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE employee_id = ? AND event_id = ? AND event_date = ?");

    $pdo->beginTransaction();

    foreach ($bookings as $booking) {
        $employeeEmail = $booking['employee_mail'];
        $eventName = $booking['event_name'];

        // Hier überprüfe ich, ob die Bookings, Events, Employees bereits existiert um wiederholene Einträge zu vermeiden

        if (!isset($existingEmployees[$employeeEmail])) {
            $employeeStmt->execute([$booking['employee_name'], $employeeEmail]);
            $existingEmployees[$employeeEmail] = $pdo->lastInsertId();
        }
        $employeeId = $existingEmployees[$employeeEmail];

        if (!isset($existingEvents[$eventName])) {
            $eventStmt->execute([$eventName]);
            $existingEvents[$eventName] = $pdo->lastInsertId();
        }
        $eventId = $existingEvents[$eventName];

        $checkBookingStmt->execute([$employeeId, $eventId, $booking['event_date']]);
        $bookingExists = $checkBookingStmt->fetchColumn();

        if ($bookingExists == 0) {
            $bookingStmt->execute([
                $employeeId,
                $eventId,
                $booking['participation_fee'],
                $booking['event_date'],
                $booking['version'] ?? null
            ]);
        }
    }

    $pdo->commit();

    echo "Daten wurden erfolgreich importiert!";
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
?>