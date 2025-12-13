<?php 
require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";
require_once "../backend/admin/get_orders.php"; // File yang berisi fungsi getOrders()

// Set judul halaman dan panggil layout
$admin_page_title = 'Order Management';
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 

// --- 2. LOGIKA PAGINATION & SEARCH ---
$limit = 10; // Jumlah baris per halaman
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page < 1) $current_page = 1;

$search_query = $_GET['search_query'] ?? '';

// --- 3. AMBIL DATA DARI DATABASE ---
$order_data = getOrders($conn, $search_query, $current_page, $limit);

// *** PERBAIKAN: Handling jika getOrders gagal mengembalikan array (misal mengembalikan NULL) ***
if (!is_array($order_data)) {
    // Jika terjadi kegagalan fatal di backend, set data sebagai kosong
    $display_orders = [];
    $total_pages = 1;
    $total_rows = 0;
    // Tambahkan notifikasi error jika perlu
    $_SESSION['error'] = "Failed to load order data. Check the database connection and query in get_orders.php.";
} else {
    $display_orders = $order_data['orders'];
    $total_pages = $order_data['total_pages'];
    $total_rows = $order_data['total_rows'];
}
// **********************************************************************************************

// Fungsi helper untuk link pagination
function getPaginationLink($page, $search) {
    $link = "pesanan.php?page=" . $page;
    if (!empty($search)) {
        $link .= "&search_query=" . urlencode($search);
    }
    return $link;
}
?>

<main class="flex-1 p-10"> 
    <h1 class="text-3xl font-bold mb-8"><?php echo $admin_page_title; ?></h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <span class="font-semibold"><?= $_SESSION['error']; ?></span>
        </div>
    <?php unset($_SESSION['error']); endif; ?>
    
    <div class="mb-8">
        <form action="pesanan.php" method="GET">
            <div class="flex">
                <input type="text" name="search_query" placeholder="Search for Booking Code, Name, or Email..." 
                        class="w-full md:w-1/2 p-2 border rounded-l-md" 
                        value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-md hover:bg-blue-700">Search</button>
                <a href="pesanan.php" class="text-gray-600 ml-4 self-center hover:underline">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Detail</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($display_orders)): ?>
                    <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No order data found.</td></tr>
                <?php else: ?>
                    <?php foreach ($display_orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['booking_code']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['origin_code'] . ' - ' . $order['dest_code']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                                <div class="text-xs text-gray-400"><?php echo date('H:i', strtotime($order['created_at'])); ?> WIB</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                                Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php 
                                    $status = strtoupper($order['payment_status']);
                                    $class = ($status == 'PAID' || $status == 'LUNAS') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $class; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="detail_pesanan.php?id=<?php echo htmlspecialchars($order['id_transaction']); ?>"
                                   class="text-blue-600 hover:text-blue-900 font-semibold hover:underline">
                                    See Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-between items-center px-4 py-3 sm:px-6 bg-white rounded-lg shadow-md">
            
            <p class="text-sm text-gray-700">
                Show 
                <span class="font-medium"><?php echo min($limit, $total_rows - ($current_page - 1) * $limit); ?></span> from 
                <span class="font-medium"><?php echo $total_rows; ?></span> result
            </p>

            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                
                <a href="<?php echo getPaginationLink($current_page - 1, $search_query); ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 
                          <?php echo ($current_page <= 1) ? 'pointer-events-none opacity-50' : ''; ?>">
                    Before
                </a>
                
                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                if ($start_page > 1) { echo '<span class="relative inline-flex items-center px-4 py-2 border text-sm font-medium bg-white border-gray-300 text-gray-700">...</span>'; }
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="<?php echo getPaginationLink($i, $search_query); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                              <?php echo ($i == $current_page) 
                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' 
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages) { echo '<span class="relative inline-flex items-center px-4 py-2 border text-sm font-medium bg-white border-gray-300 text-gray-700">...</span>'; } ?>

                <a href="<?php echo getPaginationLink($current_page + 1, $search_query); ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 
                          <?php echo ($current_page >= $total_pages) ? 'pointer-events-none opacity-50' : ''; ?>">
                    Next
                </a>
            </nav>
        </div>
    <?php endif; ?>

</main>

<?php 
require_once '../layouts/admin_footer.php'; 
?>