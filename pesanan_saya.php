<?php
$page_title = 'My Orders - FLYNOW';
require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/layouts/header.php'; 

if (!isset($conn)) {
    die('Koneksi database tidak tersedia');
}

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    echo "Harus login untuk melihat pesanan.";
    exit;
}

$user_id = (int) $_SESSION['id_user'];

$tiket_aktif = [];
$riwayat_perjalanan = [];

// ================================
// TIKET AKTIF
// ================================
$sql_aktif = "
    SELECT 
        t.id_transaction,
        t.booking_code,
        f.flight_code,
        f.departure_date,
        f.departure_time,
        oa.city AS origin_city,
        da.city AS dest_city
    FROM transactions t
    JOIN flights f ON f.id_flight = t.departure_flight_id
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE t.user_id = ?
      AND t.payment_status = 'PAID'
      AND (f.departure_date >= NOW() OR (f.departure_date = CURDATE() AND f.departure_time > CURTIME()))
    ORDER BY f.departure_time ASC
";

$stmt = $conn->prepare($sql_aktif);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tiket_aktif[] = [
        'maskapai' => 'FlyNow',
        'rute'     => $row['origin_city'] . ' → ' . $row['dest_city'],
        'tanggal'  => date('d M Y', strtotime($row['departure_date'])),
        'waktu'    => date('H:i', strtotime($row['departure_time'])),
        'status'   => 'Upcoming',
        'id'       => $row['id_transaction']
    ];
}
$stmt->close();

// ================================
// RIWAYAT PERJALANAN
// ================================
$sql_riwayat = "
    SELECT 
        t.id_transaction,
        t.booking_code,
        f.flight_code,
        f.departure_date,
        f.departure_time,
        oa.city AS origin_city,
        da.city AS dest_city
    FROM transactions t
    JOIN flights f ON f.id_flight = t.departure_flight_id
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE t.user_id = ?
      AND t.payment_status = 'PAID'
      AND (f.departure_date >= NOW() OR (f.departure_date = CURDATE() AND f.departure_time > CURTIME()))
    ORDER BY f.departure_time DESC
";

$stmt = $conn->prepare($sql_riwayat);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $riwayat_perjalanan[] = [
        'maskapai' => 'FlyNow',
        'rute'     => $row['origin_city'] . ' → ' . $row['dest_city'],
        'tanggal'  => date('d M Y', strtotime($row['departure_date'])),
        'waktu'    => date('H:i', strtotime($row['departure_time'])),
        'status'   => 'Selesai',
        'id'       => $row['id_transaction']
    ];
}
$stmt->close();
?>

<div class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-8">My Orders</h1>

    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Active Tickets (Upcoming)</h2>
        <div class="space-y-6">
            <?php if (empty($tiket_aktif)): ?>
                <p class="text-gray-600">You currently have no active tickets.</p>
            <?php else: ?>
                <?php foreach ($tiket_aktif as $tiket): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col md:flex-row justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-500"><?php echo $tiket['maskapai']; ?></div>
                            <div class="text-xl font-bold"><?php echo $tiket['rute']; ?></div>
                            <div class="text-gray-700"><?php echo $tiket['tanggal']; ?> | <?php echo $tiket['waktu']; ?></div>
                            <div class="text-sm font-semibold text-green-600"><?php echo $tiket['status']; ?></div>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <a href="success_payment.php?id=<?= $tiket['id']; ?>" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700">
                                View E-Ticket
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-6">Travel History (Completed)</h2>
        <div class="space-y-6">
            <?php if (empty($riwayat_perjalanan)): ?>
                <p class="text-gray-600">You have no travel history yet.</p>
            <?php else: ?>
                <?php foreach ($riwayat_perjalanan as $tiket): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col md:flex-row justify-between items-center opacity-70">
                        <div>
                            <div class="text-sm text-gray-500"><?php echo $tiket['maskapai']; ?></div>
                            <div class="text-xl font-bold"><?php echo $tiket['rute']; ?></div>
                            <div class="text-gray-700"><?php echo $tiket['tanggal']; ?> | <?php echo $tiket['waktu']; ?></div>
                            <div class="text-sm font-semibold text-gray-600"><?php echo $tiket['status']; ?></div>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <a href="#" class="bg-gray-400 text-white px-5 py-2 rounded-md cursor-not-allowed">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>


<?php
require_once 'layouts/footer.php'; 
?>