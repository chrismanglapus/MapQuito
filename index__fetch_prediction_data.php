<?php
include('main/connection.php');
header('Content-Type: application/json');

if (isset($_GET['barangay'], $_GET['year'], $_GET['week'])) {
    $barangay = $_GET['barangay'];
    $year = intval($_GET['year']);
    $selectedWeek = intval($_GET['week']);

    // Get 5 historical weeks before selectedWeek AND the selectedWeek itself
    $histWeeks = [];
    for ($i = 5; $i >= 0; $i--) {
        $w = $selectedWeek - $i;
        if ($w >= 1) {
            $histWeeks[] = $w;
        }
    }

    $histCount = count($histWeeks);
    $placeholders = implode(',', array_fill(0, $histCount, '?'));

    $sql = "
        SELECT morbidity_week, cases
        FROM morbidity_data
        WHERE barangay_name = ?
          AND year = ?
          AND morbidity_week IN ($placeholders)
        ORDER BY FIELD(morbidity_week, $placeholders)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare failed: ' . $conn->error
        ]);
        exit;
    }

    $typeString = 's' . str_repeat('i', 1 + 2 * $histCount);
    $params = array_merge([$barangay, $year], $histWeeks, $histWeeks);
    $stmt->bind_param($typeString, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $historical = [];
    while ($row = $result->fetch_assoc()) {
        $historical[] = [
            'morbidity_week' => intval($row['morbidity_week']),
            'cases' => intval($row['cases'])
        ];
    }
    $stmt->close();

    // Fallback predictions (in case model fails)
    $predictions = [];

    // Use Verhulst-Pearl Model
    $cases = array_column($historical, 'cases');
    $weeks = range(1, count($cases));

    if (count($cases) >= 2 && max($cases) !== min($cases)) {
        // Model parameters
        $K = max($cases) * 1.2; // carrying capacity
        $P0 = $cases[0];
        $Pn = end($cases);

        // Calculate growth rate 'r'
        $t = count($cases) - 1;
        $denominator = $P0 * ($K - $Pn);
        if ($denominator > 0 && $Pn != 0) {
            $numerator = $Pn * ($K - $P0);
            $r = (1 / $t) * log($numerator / $denominator);

            // Predict next 5 weeks using Verhulst-Pearl
            for ($i = 1; $i <= 5; $i++) {
                $week = $selectedWeek + $i;
                $yearForWeek = $year;
                
                if ($week > 52) {
                    $week -= 52;
                    $yearForWeek += 1;
                }
            
                $pred = $K / (1 + (($K - $P0) / $P0) * exp(-$r * ($t + $i)));
                $predVal = max(0, round($pred));
            
                // Fetch actual if available
                $q2 = "SELECT cases FROM morbidity_data WHERE barangay_name = ? AND year = ? AND morbidity_week = ?";
                $s2 = $conn->prepare($q2);
                $s2->bind_param('sii', $barangay, $yearForWeek, $week);
                $s2->execute();
                $r2 = $s2->get_result();
                $actual = $r2->num_rows ? intval($r2->fetch_assoc()['cases']) : null;
                $s2->close();
            
                $predictions[] = [
                    'morbidity_week' => $week,
                    'year' => $yearForWeek,
                    'predicted_cases' => $predVal,
                    'actual_cases' => $actual
                ];
            }
            
        }
    }

    // If model failed or variation was insufficient, fill with 0s
    if (count($predictions) === 0) {
        for ($i = 1; $i <= 5; $i++) {
            $week = $selectedWeek + $i;

            $q2 = "SELECT cases FROM morbidity_data WHERE barangay_name = ? AND year = ? AND morbidity_week = ?";
            $s2 = $conn->prepare($q2);
            $s2->bind_param('sii', $barangay, $year, $week);
            $s2->execute();
            $r2 = $s2->get_result();
            $actual = $r2->num_rows ? intval($r2->fetch_assoc()['cases']) : null;
            $s2->close();

            $predictions[] = [
                'morbidity_week' => $week,
                'predicted_cases' => 0,
                'actual_cases' => $actual
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'historical' => $historical,
        'predictions' => $predictions
    ]);
    $conn->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing barangay, year, or week parameter'
    ]);
}
