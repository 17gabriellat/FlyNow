<?php 
require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";

// Set judul halaman dan panggil layout
$admin_page_title = 'Order Details';
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 

$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$order_data = null;
$flight_data = null;
$passengers_data = [];
$error_message = '';

if ($transaction_id > 0) {
    // --- 1. Ambil Data Transaksi Dasar ---
    $sql_transaction = "
        SELECT 
            t.booking_code, t.total_price, t.payment_status, t.total_passengers, t.created_at,
            u.name AS customer_name, 
            u.email AS customer_email,
            f.id_flight, 
            f.flight_code
        FROM transactions t
        JOIN users u ON u.id_user = t.user_id
        JOIN flights f ON f.id_flight = t.departure_flight_id  /* PASTIKAN BARIS INI ADA */
        WHERE t.id_transaction = ?
    ";

    $stmt_transaction = $conn->prepare($sql_transaction);
    $stmt_transaction->bind_param("i", $transaction_id);
    $stmt_transaction->execute();
    $order_data = $stmt_transaction->get_result()->fetch_assoc();
    $stmt_transaction->close();

    if ($order_data) {
        // --- 2. Ambil Data Penerbangan (Rincian Rute) ---
        $sql_flight = "
            SELECT 
                f.flight_code, f.departure_time, f.arrival_time, f.price, 
                oa.city AS origin_city, oa.airport_name AS origin_airport, oa.airport_code AS origin_code, /* DIKOREKSI */
                da.city AS dest_city, da.airport_name AS dest_airport, da.airport_code AS dest_code   /* DIKOREKSI */
            FROM flights f
            JOIN airports oa ON oa.id_airport = f.origin_airport
            JOIN airports da ON da.id_airport = f.destination_airport
            WHERE f.id_flight = ?
        ";
        
        $stmt_flight = $conn->prepare($sql_flight);
        $stmt_flight->bind_param("i", $order_data['id_flight']);
        $stmt_flight->execute();
        $flight_data = $stmt_flight->get_result()->fetch_assoc();
        $stmt_flight->close();

        // --- 3. Ambil Data Penumpang ---
        // Menggunakan tabel 'transaction_passengers'
        $sql_passengers = "
            SELECT 
                p.passenger_type, p.title, p.full_name, p.nik, p.mother_name, p.created_at
            FROM transaction_passengers p 
            WHERE p.transaction_id = ?
        ";
        
        $stmt_passengers = $conn->prepare($sql_passengers);
        $stmt_passengers->bind_param("i", $transaction_id);
        $stmt_passengers->execute();
        $result_passengers = $stmt_passengers->get_result();
        
        while ($row = $result_passengers->fetch_assoc()) {
            $passengers_data[] = $row;
        }
        $stmt_passengers->close();

    } else {
        $error_message = 'Data pesanan tidak ditemukan.';
    }

} else {
    $error_message = 'ID Transaksi tidak valid.';
}
?>

<main class="flex-1 p-10">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?php echo $admin_page_title; ?> (ID: <?php echo $transaction_id; ?>)</h1>
        <a href="pesanan.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
            &larr; Back to Order Management
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php else: ?>
        
        <div class="bg-white p-8 rounded-lg shadow-xl space-y-8">

            <div class="border-b pb-4">
                <h2 class="text-2xl font-bold text-blue-700 mb-4">Transaction Summary</h2>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 font-medium">Code Booking</p>
                        <p class="text-lg font-extrabold text-blue-600"><?php echo htmlspecialchars($order_data['booking_code']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Order Date</p>
                        <p class="text-lg"><?php echo date('d M Y, H:i', strtotime($order_data['created_at'])); ?> WIB</p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Payment Status</p>
                        <?php 
                            $status = strtoupper($order_data['payment_status']);
                            $class = ($status == 'PAID' || $status == 'LUNAS') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                        ?>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php echo $class; ?>">
                            <?php echo htmlspecialchars($status); ?>
                        </span>
                    </div>
                    <div class="col-span-3 pt-4 border-t">
                        <p class="text-gray-500 font-medium">Total payment</p>
                        <p class="text-3xl font-extrabold text-green-600">
                            Rp <?php echo number_format($order_data['total_price'], 0, ',', '.'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-700 mb-4">Flight Details</h2>
                <?php if ($flight_data): ?>
                    <div class="p-4 border rounded-lg bg-gray-50">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-lg font-bold text-gray-800">
                                    <?php echo htmlspecialchars($flight_data['origin_code']); ?> &rarr; <?php echo htmlspecialchars($flight_data['dest_code']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($flight_data['origin_city']); ?> (<?php echo htmlspecialchars($flight_data['origin_airport']); ?>)
                                    to
                                    <?php echo htmlspecialchars($flight_data['dest_city']); ?> (<?php echo htmlspecialchars($flight_data['dest_airport']); ?>)
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($flight_data['flight_code']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($flight_data['departure_time']); ?> - <?php echo htmlspecialchars($flight_data['arrival_time']); ?></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-sm font-medium text-gray-500">Unit Ticket Price</p>
                            <p class="text-md font-semibold">
                                Rp <?php echo number_format($flight_data['price'], 0, ',', '.'); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-700 mb-4">Customer Details</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 font-medium">Customer Name</p>
                        <p class="text-md font-semibold"><?php echo htmlspecialchars($order_data['customer_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Email</p>
                        <p class="text-md font-semibold"><?php echo htmlspecialchars($order_data['customer_email']); ?></p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-gray-700 mb-4">Passenger List (<?php echo $order_data['total_passengers']; ?> person)</h2>
                
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIK</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mother's name</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($passengers_data)): ?>
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Passenger data not found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($passengers_data as $p): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($p['title'] . '. ' . $p['full_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars(ucfirst($p['passenger_type'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($p['nik']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($p['mother_name'] ?: '-'); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    <?php endif; ?>

</main>

<?php 
require_once '../layouts/admin_footer.php'; 
?>