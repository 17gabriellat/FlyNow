<?php
// session_start();
require_once 'layouts/header.php';
$page_title = 'Search Result - FLYNOW';

// Redirect kalau tidak ada hasil
if (!isset($_SESSION['search_results'])) {
    header("Location: index.php");
    exit;
}

$data = $_SESSION['search_results'];

// Ambil data utama
$from        = $data['from'] ?? '';
$to          = $data['to'] ?? '';
$departure   = $data['departure'] ?? '';
$return_date = $data['return_date'] ?? '';

// Ambil list flights
$oneWayFlights = $data['oneway'] ?? [];
$returnFlights = $data['return'] ?? [];  // <= PENTING

// Kumpulkan airlines unik dari semua flight
$airlines = [];

foreach (array_merge($oneWayFlights, $returnFlights) as $f) {
    if (!empty($f['airline_code']) && !isset($airlines[$f['airline_code']])) {
        $airlines[$f['airline_code']] = $f['airline_name'];
    }
}

// foreach ($returnFlights as $f) {
//     if (!empty($f['airline_code']) && !array_key_exists($f['airline_code'], $airlines)) {
//         $airlines[$f['airline_code']] = $f['airline_name'];
//     }
// }
?>

<div class="container mx-auto px-6 py-8">

    <!-- HEADER SEARCH RESULT -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h1 class="text-2xl font-bold">
            Search Result: <?= htmlspecialchars($from); ?> → <?= htmlspecialchars($to); ?>
        </h1>

        <p class="text-gray-600 mt-2">
            <?= !empty($departure) ? date('l, d M Y', strtotime($departure)) : '' ?>

            <?php if (!empty($return_date)): ?>
                | Return: <?= date('l, d M Y', strtotime($return_date)) ?>
            <?php endif; ?>
        </p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">

        <!-- SIDEBAR FILTER -->
        <aside class="w-full lg:w-1/4">
            <div class="bg-white p-6 rounded-lg shadow-md h-full">
                <h3 class="text-xl font-semibold mb-4 border-b pb-2">Filter</h3>

                <div class="mb-2 font-semibold">Airlines</div>

                <!-- Scroll sendiri jika panjang -->
                <div class="max-h-64 overflow-y-auto pr-2">

                    <?php if (!empty($airlines)): ?>
                        <?php foreach ($airlines as $code => $name): ?>
                            <label class="flex items-center space-x-2 mb-2 text-sm">
                                <input type="checkbox"
                                    class="airline-filter rounded"
                                    value="<?= htmlspecialchars($code); ?>">
                                <span><?= htmlspecialchars($name); ?> (<?= htmlspecialchars($code); ?>)</span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">
                            No airline filter available.
                        </p>
                    <?php endif; ?>

                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <mainContent class="w-full lg:w-3/4 space-y-8 main-content">

            <!-- DEPARTURE -->
            <section id="departure-section">
                <h2 class="text-xl font-semibold mb-4">Departure Flights</h2>

                <?php if (empty($oneWayFlights)): ?>
                    <p class="text-gray-500 text-sm">No departure flights found.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($oneWayFlights as $flight): ?>

                            <?php $flight_type = "departure";
                            include 'components/flight_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- RETURN -->
            <?php if (!empty($return_date)): ?>
                <section>
                    <h2 class="text-xl font-semibold mb-4">Return Flights</h2>

                    <?php if (empty($returnFlights)): ?>
                        <p class="text-gray-500 text-sm">No return flights found.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($returnFlights as $flight): ?>
                                <?php $flight_type = "return";
                                include 'components/flight_card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            </mainConte>
    </div>
</div>

<!-- FILTER JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {

        $(".airline-filter").on("change", function() {
            const activeCodes = $(".airline-filter:checked")
                .map(function() {
                    return $(this).val();
                })
                .get();

            if (activeCodes.length === 0) {
                $(".flight-card").show();
            } else {
                $(".flight-card").each(function() {
                    const code = $(this).data("airline");
                    $(this).toggle(activeCodes.includes(code));
                });
            }
        });

        function showError(message) {
            const alertHtml = `
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold">${message}</span>
                <button onclick="$('#alert-error').fadeOut();" 
                        class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    `;

            $("mainContent").prepend(alertHtml);

            setTimeout(() => $("#alert-error").fadeOut(), 3000);
        }

        function showSuccess(message) {
            const alertHtml = `
        <div id="alert-success"
            class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold">${message}</span>
                <button onclick="$('#alert-success').fadeOut();" 
                        class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    `;

            $("mainContent").prepend(alertHtml);

            setTimeout(() => $("#alert-success").fadeOut(), 3000);
        }

        let selectedDeparture = null;
        let selectedReturn = null;
        let hasReturn = <?= !empty($return_date) ? 'true' : 'false'; ?>;

        $(".choose-flight").on("click", function() {
            let id = $(this).data("flight-id");
            let type = $(this).data("type");

            // === CASE 1: One-way (tanpa return date) ===
            if (!hasReturn) {
                window.location.href = "booking.php?departure_id=" + id;
                return;
            }

            // === CASE 2: Round-trip ===
            if (type === "departure") {
                selectedDeparture = id;

                showSuccess("Departure flight selected. Please choose your return flight next.");

                $('html, body').animate({
                    scrollTop: $(".main-content").offset().top
                }, 600);

                return;
            }

            if (type === "return") {

                if (!selectedDeparture) {
                    showError("Please choose a departure flight first.");
                    $('html, body').animate({
                        scrollTop: $(".main-content").offset().top
                    }, 600);
                    return;
                }

                selectedReturn = id;

                showSuccess("Both flights selected. Redirecting to booking...");

                setTimeout(() => {
                    window.location.href =
                        "booking.php?departure_id=" + selectedDeparture +
                        "&return_id=" + selectedReturn;
                }, 900);
            }
        });




    });
</script>

<?php require_once 'layouts/footer.php'; ?>