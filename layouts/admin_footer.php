<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Ambil data PHP yang sudah di-encode menjadi JSON
    // Variabel ini di-output dari dashboard.php
    const labels = <?php echo $labels_json; ?>;
    const salesData = <?php echo $sales_data_json; ?>;

    const ctx = document.getElementById('salesChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'bar', // Menggunakan Grafik Batang (Bar Chart)
        data: {
            labels: labels, // Label bulan (e.g., "Des 2024")
            datasets: [{
                label: 'Total Penjualan (Rp)',
                data: salesData, // Data penjualan per bulan
                backgroundColor: 'rgba(52, 211, 153, 0.8)', // Warna hijau muda
                borderColor: 'rgba(16, 185, 129, 1)', 
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, 
            scales: {
                x: {
                    // Penyesuaian agar label tidak miring jika terlalu banyak
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45 
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Penjualan (Rp)'
                    },
                    // Mengatur format angka pada sumbu Y ke format Rupiah
                    ticks: {
                        callback: function(value, index, values) {
                            if (parseInt(value) >= 1000) {
                                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            } else {
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            // Memformat nilai tooltip menjadi Rupiah
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>