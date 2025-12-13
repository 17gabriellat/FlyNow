<div class="flight-card flightCard bg-white rounded-lg shadow-md overflow-hidden flex flex-col md:flex-row items-center"
    data-airline="<?= $flight['airline_code'] ?>">

    <div class="p-4">
        <div class="font-bold text-lg"><?= $flight['airline_name'] ?> (<?= $flight['airline_code'] ?>)</div>
        <div class="text-sm text-gray-500"><?= $flight['flight_code'] ?></div>
    </div>

    <div class="flex-1 p-4 text-center md:text-left">
        <div class="flex items-center justify-center md:justify-start space-x-4">
            <div>
                <div class="text-2xl font-bold"><?= date("H:i", strtotime($flight['departure_time'])) ?></div>
                <div class="text-sm text-gray-600"><?= $flight['origin_code'] ?></div>
            </div>

            <div class="text-gray-500">
                <div>→</div>
                <div class="text-xs"><?= $flight['travel_duration'] ?> min</div>
            </div>

            <div>
                <div class="text-2xl font-bold"><?= date("H:i", strtotime($flight['arrival_time'])) ?></div>
                <div class="text-sm text-gray-600"><?= $flight['destination_code'] ?></div>
            </div>
        </div>
    </div>

    <div class="w-full md:w-auto p-4 bg-gray-50 md:bg-transparent text-center md:text-right">
        <div class="text-2xl font-bold text-orange-600">
            Rp <?= number_format($flight['price'], 0, ',', '.') ?>
        </div>
        <div class="text-sm text-gray-600 mb-2">/seat</div>

        <button class="choose-flight bg-blue-600 text-white px-6 py-2 rounded-md"
            data-flight-id="<?= $flight['id_flight'] ?>"
            data-type="<?= $flight_type ?>">
            Choose
        </button>
    </div>

</div>