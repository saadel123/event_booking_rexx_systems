<?php
require 'database.php';

$employee_name = $_GET['employee_name'] ?? '';
$event_id = $_GET['event_id'] ?? '';
$event_date = $_GET['event_date'] ?? '';

// Ich habe den Pagination-Code mit Limit hinzugefügt falls es in der Zukunft große Datenmengen gibt
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$eventQuery = "SELECT id, name FROM events";
$eventStmt = $pdo->query($eventQuery);
$events = $eventStmt->fetchAll();

$query = "SELECT b.event_date, b.participation_fee, e.name AS event_name, emp.name AS employee_name
          FROM bookings b
          JOIN events e ON b.event_id = e.id
          JOIN employees emp ON b.employee_id = emp.id";

$params = [];

if (!empty($employee_name)) {
    $query .= " AND emp.name LIKE ?";
    $params[] = "%$employee_name%";
}

if (!empty($event_id)) {
    $query .= " AND e.id = ?";
    $params[] = $event_id;
}

if (!empty($event_date)) {
    $query .= " AND b.event_date = ?";
    $params[] = $event_date;
}

$query .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$total_fee_stmt = $pdo->prepare("SELECT SUM(participation_fee) FROM bookings WHERE 1=1");
$total_fee_stmt->execute();
$total_fee = $total_fee_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veranstaltungsbuchungen</title>
    <style>
        table {
            width: 80%;
        }
        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>Veranstaltungsbuchungen</h2>
    <form method="GET">
        <!-- Ich habe htmlspecialchars aus Sicherheitsgründen verwendet. -->
        <input type="text" name="employee_name" placeholder="Mitarbeitername"
            value="<?= htmlspecialchars($employee_name) ?>">
        <select name="event_id">
            <option value="">-- Veranstaltungsname wählen --</option>
            <?php foreach ($events as $event): ?>
                <option value="<?= $event['id'] ?>" <?= ($event_id == $event['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($event['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="event_date" value="<?= htmlspecialchars($event_date) ?>">
        <button type="submit">Filtern</button>
    </form>

    <table>
        <tr>
            <th>Mitarbeitername</th>
            <th>Veranstaltungsname</th>
            <th>Teilnahmegebühr</th>
            <th>Veranstaltungsdatum</th>
        </tr>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?= htmlspecialchars($booking['employee_name']) ?></td>
                <td><?= htmlspecialchars($booking['event_name']) ?></td>
                <td><?= $booking['participation_fee'] ?> €</td>
                <td><?= htmlspecialchars($booking['event_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="2"><strong>Gesamte Teilnahmegebühr:</strong></td>
            <td colspan="2"><strong><?= $total_fee ?> €</strong></td>
        </tr>
    </table>

    <div>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&lt;<?= $page - 1 ?></a>
        <?php endif; ?>

        <strong><?= $page ?></strong>

        <?php if (count($bookings) == $limit): ?>
            <a href="?page=<?= $page + 1 ?>"><?= $page + 1 ?>&gt;</a>
        <?php endif; ?>
    </div>


</body>

</html>