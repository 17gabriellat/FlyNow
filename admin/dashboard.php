<?php 
require_once "../backend/db.php";
require_once "../backend/akses_admin.php";
require_once "../backend/admin/get_stats.php";

// Set judul halaman
$admin_page_title = 'Dashboard';

// Panggil header, sidebar, dan footer
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 

// --- DATA STATISTIK HARIAN ---
$total_penjualan_hari_ini = getTotalSalesToday($conn);
$tiket_terjual_hari_ini = getTotalTicketsSoldToday($conn);
$total_pengguna = countTotalUsers($conn);

// --- DATA UNTUK GRAFIK 12 BULAN TERAKHIR ---
$data_grafik_penjualan = getSalesLast12Months($conn); 

// Ekstrak label bulan dan data penjualan untuk Chart.js
$labels = array_column($data_grafik_penjualan, 'label');
$sales_data = array_column($data_grafik_penjualan, 'penjualan');

// Konversi ke format JSON untuk digunakan di JavaScript
$labels_json = json_encode($labels);
$sales_data_json = json_encode($sales_data);

?>

<main class="p-4 w-full transition-all duration-300">

    <h1 class="text-2xl sm:text-3xl font-bold mb-6 sm:mb-8">
        <?= $admin_page_title; ?>
    </h1>

    <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 mb-8">
        <div class="bg-white p-6 rounded-lg shadow hover:shadow-xl transition">
            <h3 class="text-gray-600 font-semibold text-sm sm:text-base">Today's Sales</h3>
            <p class="text-2xl sm:text-3xl font-bold text-blue-600 mt-1">
                Rp <?= number_format($total_penjualan_hari_ini, 0, ',', '.'); ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow hover:shadow-xl transition">
            <h3 class="text-gray-600 font-semibold text-sm sm:text-base">Tickets Sold Today</h3>
            <p class="text-2xl sm:text-3xl font-bold text-green-600 mt-1">
                <?= $tiket_terjual_hari_ini; ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow hover:shadow-xl transition">
            <h3 class="text-gray-600 font-semibold text-sm sm:text-base">Total User</h3>
            <p class="text-2xl sm:text-3xl font-bold text-purple-600 mt-1">
                <?= $total_pengguna; ?>
            </p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg sm:text-xl font-semibold mb-4">
            Last 12 Months Sales Chart
        </h3>

        <div class="relative w-full h-[320px] sm:h-[380px] md:h-[420px] lg:h-[480px]">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

</main>


<?php 
require_once '../layouts/admin_footer.php'; 
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        const labels = <?php echo $labels_json; ?>;
        const salesData = <?php echo $sales_data_json; ?>;

        const salesChart = new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Penjualan (Rp)',
                    data: salesData,
                    backgroundColor: 'rgba(59, 130, 246, 0.6)', 
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    barThickness: 'flex', 
                    maxBarThickness: 50 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, 
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>