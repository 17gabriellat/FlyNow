<?php
$page_title = 'FLYNOW - Book Your Flight';
require_once 'layouts/header.php';
?>

<header class="bg-blue-600 text-white">

    <!-- ALERT ERROR -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow max-w-3xl mx-auto mt-6">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="$('#alert-error').fadeOut();"
                    class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']);
    endif; ?>

    <div class="container mx-auto px-6 py-20 text-center">

        <h1 class="text-4xl font-bold mb-4">Find Your Best Flight</h1>
        <p class="text-lg mb-8">Search and book flights easily to anywhere in the world.</p>

        <div class="bg-white text-gray-800 p-8 rounded-lg shadow-xl max-w-4xl mx-auto">

            <!-- FORM -->
            <form action="backend/search_flights.php" method="POST" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <!-- FROM -->
                    <div class="text-left relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>

                        <input type="text" id="from" name="from"
                            placeholder="Search airport..."
                            autocomplete="off"
                            class="w-full px-4 py-3 border border-gray-300 rounded-md">

                        <div id="fromOptions"
                            class="absolute z-20 w-full bg-white border border-gray-200 rounded-md shadow hidden max-h-60 overflow-y-auto">
                        </div>
                    </div>

                    <!-- TO -->
                    <div class="text-left relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>

                        <input type="text" id="to" name="to"
                            placeholder="Search airport..."
                            autocomplete="off"
                            class="w-full px-4 py-3 border border-gray-300 rounded-md">

                        <div id="toOptions"
                            class="absolute z-20 w-full bg-white border border-gray-200 rounded-md shadow hidden max-h-60 overflow-y-auto">
                        </div>
                    </div>


                    <!-- DEPARTURE -->
                    <div class="text-left">
                        <label class="block text-sm font-medium mb-1">Departure Date</label>

                        <div class="relative flex items-center border border-gray-300 rounded-md px-4 py-3">
                            <span class="text-blue-500 mr-2">📅</span>
                            <input type="date" id="departure" name="departure"
                                class="w-full outline-none"
                                value="<?= date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <!-- RETURN -->
                    <div class="text-left">
                        <div class="flex items-center mb-1">
                            <input type="checkbox" id="enableReturn" class="mr-2">
                            <label class="text-sm font-medium">Return Date</label>
                        </div>

                        <div class="relative flex items-center border border-gray-300 rounded-md px-4 py-3 bg-gray-100">
                            <span class="text-gray-400 mr-2">📅</span>
                            <input type="date" id="returnDate" name="return_date"
                                class="w-full outline-none bg-gray-100 text-gray-400"
                                disabled>
                        </div>
                    </div>

                </div>

                <!-- BUTTON -->
                <div class="lg:col-span-4 mt-4">
                    <button type="submit"
                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-blue-900 font-bold text-lg px-6 py-4 rounded-md shadow-lg">
                        SEARCH FLIGHTS
                    </button>
                </div>

            </form>

        </div>
    </div>
</header>

<!-- PROMOS -->
<div class="container mx-auto px-6 py-16">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Special Deals For You</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <img src="https://via.placeholder.com/400x250.png?text=Bali+Promo" class="w-full h-48 object-cover">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-2">20% Off to Bali</h3>
                <p class="text-gray-600">Enjoy unforgettable vacation with exclusive discounts.</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <img src="https://via.placeholder.com/400x250.png?text=Singapore+Promo" class="w-full h-48 object-cover">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-2">Singapore Cashback</h3>
                <p class="text-gray-600">Fly to Singapore and get cashback up to IDR 500k.</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <img src="https://via.placeholder.com/400x250.png?text=Japan+Promo" class="w-full h-48 object-cover">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-2">Fly to Japan</h3>
                <p class="text-gray-600">Cherry Blossom season? Why not!</p>
            </div>
        </div>

    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        let airports = [];

        // LOAD AIRPORTS FROM DB
        $.get("backend/get_airports.php", function(data) {
            airports = JSON.parse(data);
        });

        // GENERIC DROPDOWN FUNCTION
        function bindAirportSearch(inputId, dropdownId) {
            const input = $("#" + inputId);
            const dropdown = $("#" + dropdownId);

            input.on("input focus", function() {
                const keyword = input.val().toLowerCase();
                const filtered = airports.filter(a =>
                    a.name.toLowerCase().includes(keyword) ||
                    a.code.toLowerCase().includes(keyword)
                );

                dropdown.html("");

                if (filtered.length === 0) {
                    dropdown.addClass("hidden");
                    return;
                }

                filtered.forEach(a => {
                    dropdown.append(`
                    <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                         data-value="${a.code}">
                        ${a.name} (${a.code})
                    </div>
                `);
                });

                dropdown.removeClass("hidden");
            });

            // SELECT OPTION
            dropdown.on("click", "div", function() {
                const value = $(this).data("value");
                input.val(value); // set code only (CGK)
                dropdown.addClass("hidden");
            });

            // CLICK OUTSIDE = CLOSE DROPDOWN
            $(document).click(function(e) {
                if (!$(e.target).closest("#" + inputId).length &&
                    !$(e.target).closest("#" + dropdownId).length) {
                    dropdown.addClass("hidden");
                }
            });
        }

        // APPLY TO BOTH FIELDS
        bindAirportSearch("from", "fromOptions");
        bindAirportSearch("to", "toOptions");

        setTimeout(() => {
            $("#alert-error").fadeOut();
        }, 3000);

        $("#enableReturn").change(function() {
            const isChecked = $(this).is(":checked");

            $("#returnDate")
                .prop("disabled", !isChecked)
                .toggleClass("bg-gray-100 text-gray-400", !isChecked);

            if (!isChecked) $("#returnDate").val("");
        });

    });
</script>

<?php require_once 'layouts/footer.php'; ?>