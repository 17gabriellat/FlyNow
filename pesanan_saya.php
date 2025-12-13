<?php
$page_title = 'Pesanan Saya - FLYNOW';
require_once 'layouts/header.php'; 

// --- SIMULASI DATA TIKET ---
$tiket_aktif = [
    [
        'kode_booking' => 'FLYN123XYZ',
        'rute' => 'Jakarta (CGK) → Bali (DPS)',
        'maskapai' => 'Garuda Indonesia',
        'tanggal' => '20 Des 2025',
        'waktu' => '08:00 - 10:50',
        'status' => 'LUNAS'
    ],
    [
        'kode_booking' => 'FLYN456ABC',
        'rute' => 'Surabaya (SUB) → Jakarta (CGK)',
        'maskapai' => 'AirAsia',
        'tanggal' => '10 Jan 2026',
        'waktu' => '15:00 - 16:30',
        'status' => 'LUNAS'
    ]
];
$riwayat_perjalanan = [
    [
        'kode_booking' => 'FLYN987ZYX',
        'rute' => 'Jakarta (CGK) → Singapura (SIN)',
        'maskapai' => 'Singapore Airlines',
        'tanggal' => '15 Nov 2025',
        'waktu' => '10:00 - 12:30',
        'status' => 'Selesai'
    ]
];
?>

<div class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-8">Pesanan Saya</h1>

    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-6">Tiket Aktif (Akan Datang)</h2>
        <div class="space-y-6">
            <?php if (empty($tiket_aktif)): ?>
                <p class="text-gray-600">Anda tidak memiliki tiket aktif saat ini.</p>
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
                            <a href="konfirmasi.php" class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700">Lihat E-Ticket</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h2 class="text-2xl font-semibold mb-6">Riwayat Perjalanan (Selesai)</h2>
        <div class="space-y-6">
            <?php if (empty($riwayat_perjalanan)): ?>
                <p class="text-gray-600">Anda belum memiliki riwayat perjalanan.</p>
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
                            <a href="#" class="bg-gray-400 text-white px-5 py-2 rounded-md cursor-not-allowed">Lihat Detail</a>
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