<?php
$page_title = 'My Orders - FLYNOW';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/layouts/header.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['id_user'];

// ================================
// PAGINATION
// ================================
$limit = 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ================================
// COUNT TOTAL DATA
// ================================
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM transactions t
    JOIN flights f ON f.id_flight = t.departure_flight_id
    WHERE t.user_id = ?
      AND t.payment_status = 'PAID'
";

$stmt = $conn->prepare($sqlCount);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total / $limit);

// ================================
// FETCH DATA
// ================================
$sql = "
    SELECT
        t.id_transaction,
        t.booking_code,
        f.flight_code,
        f.departure_date,
        f.departure_time,
        f.arrival_date,
        f.arrival_time,
        oa.city AS origin_city,
        da.city AS dest_city,

        CASE
            WHEN CONCAT(f.departure_date) > NOW()
                THEN 'Upcoming'
            WHEN NOW() BETWEEN
                CONCAT(f.departure_date,' ',f.departure_time)
                AND CONCAT(f.arrival_date,' ',f.arrival_time)
                THEN 'Ongoing'
            ELSE 'Completed'
        END AS flight_status

    FROM transactions t
    JOIN flights f ON f.id_flight = t.departure_flight_id
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport

    WHERE t.user_id = ?
      AND t.payment_status = 'PAID'

    ORDER BY f.departure_date ASC, f.departure_time ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mx-auto px-6 py-8 ">

    <h1 class="text-3xl font-bold mb-8">My Orders</h1>

    <?php if ($result->num_rows === 0): ?>
        <p class="text-gray-600">You have no orders yet.</p>
    <?php endif; ?>

    <div class="space-y-6">

        <?php while ($row = $result->fetch_assoc()): ?>

            <?php
                // Badge color
                switch ($row['flight_status']) {
                    case 'Upcoming':
                        $badge = 'bg-blue-100 text-blue-700';
                        break;
                    case 'Ongoing':
                        $badge = 'bg-yellow-100 text-yellow-700';
                        break;
                    default:
                        $badge = 'bg-green-100 text-green-700';
                }
            ?>

            <div class="bg-white rounded-lg shadow-md p-6 flex flex-col md:flex-row justify-between items-center
                shadow-lg hover:shadow-2xl
            transition-all duration-300 ease-in-out
            transform hover:-translate-y-1 hover:scale-80
            ">

                <div>
                    <div class="text-sm text-gray-500">FLYNOW</div>

                    <div class="text-xl font-bold">
                        <?= htmlspecialchars($row['origin_city']) ?>
                        →
                        <?= htmlspecialchars($row['dest_city']) ?>
                    </div>

                    <div class="text-gray-700">
                        <?= date('d M Y', strtotime($row['departure_date'])) ?>
                        |
                        <?= date('H:i', strtotime($row['departure_time'])) ?>
                    </div>

                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold <?= $badge ?>">
                        <?= $row['flight_status'] ?>
                    </span>
                </div>

                <div class="mt-4 md:mt-0">
                    <a href="success_payment.php?id=<?= $row['id_transaction'] ?>"
                       class="bg-gradient-to-t from-blue-500 to-blue-300 
                            text-white px-5 py-2 rounded-md 
                            shadow-lg hover:shadow-2xl
                            transition-all duration-300 ease-in-out
                            transform hover:-translate-y-1 hover:scale-105">
                        View E-Ticket
                    </a>
                </div>

            </div>

        <?php endwhile; ?>

    </div>

    <!-- PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-10 gap-2">

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="px-4 py-2 rounded-md text-sm
                   <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

        </div>
    <?php endif; ?>

</div>

<?php
$stmt->close();
require_once 'layouts/footer.php';
?>
