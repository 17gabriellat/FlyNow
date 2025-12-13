<?php
require_once 'layouts/header.php';
require_once 'backend/db.php';

$page_title = "Booking Details - FLYNOW";

/* ============================================================
   1. Redirect if not logged in
============================================================ */
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Please login before booking a flight.";
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

/* ============================================================
   2. Get flight ID from URL
============================================================ */
$departure_id = $_GET['departure_id'] ?? null;
$return_id    = $_GET['return_id'] ?? null;

if (!$departure_id) {
    $_SESSION['error'] = "Invalid access.";
    header("Location: index.php");
    exit;
}

/* ============================================================
   3. Fetch Departure Flight
============================================================ */
function getFlight($id, $conn)
{
    $stmt = $conn->prepare("
        SELECT f.*, a.airline_name, a.airline_code,
               o.airport_code AS origin_code,
               d.airport_code AS dest_code
        FROM flights f
        JOIN airlines a ON f.airline_id = a.id_airline
        JOIN airports o ON f.origin_airport = o.id_airport
        JOIN airports d ON f.destination_airport = d.id_airport
        WHERE f.id_flight = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$departure = getFlight($departure_id, $conn);
$return_flight = $return_id ? getFlight($return_id, $conn) : null;

/* ============================================================
   4. Prices
============================================================ */
$departurePrice = $departure['price'];
$returnPrice = $return_flight ? $return_flight['price'] : 0;
?>

<!-- ALERT MODAL -->
<div class="container mx-auto mt-6">
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow cursor-pointer">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="$('#alert-error').fadeOut();" class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']);
    endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div id="alert-success"
            class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow cursor-pointer">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                <button onclick="$('#alert-success').fadeOut();" class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']);
    endif; ?>
</div>


<div class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-6">Booking Details</h1>

    <form action="backend/checkout_process.php" method="POST" id="bookingForm"
        class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <input type="hidden" name="departure_id" value="<?= $departure_id ?>">
        <?php if ($return_id): ?>
            <input type="hidden" name="return_id" value="<?= $return_id ?>">
        <?php endif; ?>

        <!-- ======================================================
             LEFT CONTENT
        ======================================================= -->
        <div class="lg:col-span-2 space-y-6">

            <!-- CONTACT DETAIL -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Booking Contact</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">Full Name</label>
                        <input type="text" class="mt-1 w-full p-2 border rounded-md bg-gray-100"
                            value="<?= $user['full_name'] ?>" readonly>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Email</label>
                        <input type="email" class="mt-1 w-full p-2 border rounded-md bg-gray-100"
                            value="<?= $user['email'] ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- PASSENGER DETAILS -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Passenger Details</h2>

                    <button type="button" id="addPassengerBtn"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md shadow hover:bg-blue-700">
                        + Add Passenger
                    </button>
                </div>

                <div id="passengerList" class="space-y-4"></div>
            </div>

            <!-- PAYMENT METHOD -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Payment Method</h2>

                <label class="flex items-center p-3 border rounded-md mb-2">
                    <input type="radio" name="payment_method" value="va_bca" class="mr-2">
                    Virtual Account BCA
                </label>

                <label class="flex items-center p-3 border rounded-md mb-2">
                    <input type="radio" name="payment_method" value="credit_card" class="mr-2">
                    Credit Card
                </label>

                <div id="creditCardInput" class="hidden mt-3">
                    <label class="text-sm font-medium">Credit Card Number</label>
                    <input type="text" name="credit_card_number"
                        class="mt-1 p-2 border rounded-md w-full"
                        placeholder="XXXX XXXX XXXX XXXX">
                </div>

            </div>

        </div>

        <!-- ======================================================
             RIGHT SUMMARY
        ======================================================= -->
        <aside class="lg:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">

                <h2 class="text-xl font-semibold mb-4">Order Summary</h2>

                <h3 class="font-semibold">Departure</h3>
                <p><?= $departure['airline_name'] ?> - <?= $departure['flight_code'] ?></p>

                <?php if ($return_flight): ?>
                    <h3 class="font-semibold mt-3">Return</h3>
                    <p><?= $return_flight['airline_name'] ?> - <?= $return_flight['flight_code'] ?></p>
                <?php endif; ?>


                <div class="border-t mt-4 pt-4 space-y-2">
                    <div class="flex justify-between">
                        <span>Ticket Price</span>
                        <span id="ticketBasePrice">Rp 0</span>
                    </div>

                    <div class="flex justify-between text-lg font-bold mt-2 pt-2 border-t">
                        <span>Total</span>
                        <span id="totalPrice" class="text-orange-600">Rp 0</span>
                    </div>
                </div>

                <!-- VA NUMBER -->
                <div id="vaBox" class="hidden mt-4 p-4 rounded-lg"
                    style="background:#e8f2ff; border:1px solid #c5ddff;">

                    <div class="text-sm font-semibold" style="color:#1a56db;">
                        Virtual Account Number
                    </div>

                    <div id="vaNumber"
                        class="text-2xl font-bold tracking-wide mt-1"
                        style="color:#1a56db;">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold text-lg px-6 py-3 rounded-md shadow-lg hover:bg-blue-700 mt-6">
                    PAY NOW
                </button>

            </div>
        </aside>

    </form>
</div>

<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        setTimeout(() => {
            $("#alert-error, #alert-success").fadeOut();
        }, 3000);

        let passengerCount = 0;
        const departurePrice = <?= $departurePrice ?>;
        const returnPrice = <?= $returnPrice ?>;
        const baseFlightPrice = departurePrice + returnPrice;

        function formatRp(num) {
            return "Rp " + num.toLocaleString("id-ID");
        }

        function updateTotal() {
            const total = baseFlightPrice * passengerCount;
            $("#ticketBasePrice").text(formatRp(baseFlightPrice));
            $("#totalPrice").text(formatRp(total));
        }

        /* ========================================================
           ADD PASSENGER (default adult fully loaded)
        ======================================================== */
        $("#addPassengerBtn").click(function() {
            passengerCount++;

            const html = `
        <div class="p-4 border rounded-md bg-gray-50 passenger-card">

            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold passenger-number">Passenger ${passengerCount}</h3>
                <button type="button" class="text-red-600 removePassenger">✖</button>
            </div>

            <label class="font-medium">Passenger Type</label>
            <select name="passenger_type[]" class="p-2 border w-full mt-1 passengerType">
                <option value="adult" selected>Adult</option>
                <option value="child">Child</option>
            </select>

            <div class="mt-3 passengerFields">
                <!-- Default adult fields -->
                <label class="block mt-2">Title</label>
                <select name="title[]" class="p-2 border w-full">
                    <option value="Mr">Mr.</option>
                    <option value="Mrs">Mrs.</option>
                </select>

                <label class="block mt-2">Full Name</label>
                <input type="text" name="full_name[]" class="p-2 border w-full">

                <label class="block mt-2">NIK</label>
                <input type="text" name="nik[]" class="p-2 border w-full">
            </div>

        </div>
    `;

            $("#passengerList").append(html);
            reindexPassengers(); // <-- FIX numbering
            updateTotal();
        });


        function reindexPassengers() {
            let i = 1;
            $("#passengerList .passenger-card").each(function() {
                $(this).find(".passenger-number").text("Passenger " + i);
                i++;
            });
        }


        /* ========================================================
           REMOVE PASSENGER
        ======================================================== */
        $("#passengerList").on("click", ".removePassenger", function() {
            $(this).closest(".passenger-card").remove();
            passengerCount--;
            reindexPassengers(); // <-- FIX numbering
            updateTotal();
        });


        /* ========================================================
           CHANGE PASSENGER TYPE
        ======================================================== */
        $("#passengerList").on("change", ".passengerType", function() {
            const type = $(this).val();
            const container = $(this).closest(".passenger-card").find(".passengerFields");

            if (type === "adult") {
                container.html(`
                <label class="block mt-2">Title</label>
                <select name="title[]" class="p-2 border w-full">
                    <option value="Mr">Mr.</option>
                    <option value="Mrs">Mrs.</option>
                </select>

                <label class="block mt-2">Full Name</label>
                <input type="text" name="full_name[]" class="p-2 border w-full">

                <label class="block mt-2">NIK</label>
                <input type="text" name="nik[]" class="p-2 border w-full">
            `);
            } else {
                container.html(`
                <label class="block mt-2">Title</label>
                <select name="title[]" class="p-2 border w-full">
                    <option value="Master">Master (Boy)</option>
                    <option value="Miss">Miss (Girl)</option>
                </select>

                <label class="block mt-2">Full Name</label>
                <input type="text" name="full_name[]" class="p-2 border w-full">

                <label class="block mt-2">NIK</label>
                <input type="text" name="nik[]" class="p-2 border w-full">

                <label class="block mt-2">Mother's Name</label>
                <input type="text" name="mother_name[]" class="p-2 border w-full">
            `);
            }
        });

        /* ========================================================
           PAYMENT METHOD
        ======================================================== */
        $("input[name='payment_method']").change(function() {
            const method = $(this).val();

            $("#creditCardInput").toggleClass("hidden", method !== "credit_card");

            if (method === "va_bca") {
                const va = "3901" + Math.floor(10000000 + Math.random() * 90000000);
                $("#vaNumber").text(va);
                $("#vaBox").removeClass("hidden");
            } else {
                $("#vaBox").addClass("hidden");
            }
        });


    });
</script>

<?php require_once 'layouts/footer.php'; ?>