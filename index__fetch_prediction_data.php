<?php
include('main/connection.php');
header('Content-Type: application/json');

if (isset($_GET['barangay'], $_GET['year'], $_GET['week'])) {
    $barangay = $_GET['barangay'];
    $year = intval($_GET['year']);
    $selectedWeek = intval($_GET['week']);

    // Get the latest week with data
    $qLatest = "SELECT MAX(morbidity_week) as latest_week FROM morbidity_data WHERE barangay_name = ? AND year = ?";
    $stLatest = $conn->prepare($qLatest);
    $stLatest->bind_param('si', $barangay, $year);
    $stLatest->execute();
    $resLatest = $stLatest->get_result();
    $rowLatest = $resLatest->fetch_assoc();
    $referenceWeek = intval($rowLatest['latest_week']);
    $stLatest->close();

    $startWeek = max(1, $selectedWeek - 5);

    $qHist = "SELECT morbidity_week, cases, year
              FROM morbidity_data
              WHERE barangay_name = ? 
              AND year = ? 
              AND morbidity_week BETWEEN ? AND ? 
              ORDER BY morbidity_week ASC";

    $historical = [];

    // Current year data
    $histStmt = $conn->prepare($qHist);
    $histStmt->bind_param('siii', $barangay, $year, $startWeek, $selectedWeek);
    $histStmt->execute();
    $result = $histStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $historical[] = [
            'morbidity_week' => intval($row['morbidity_week']),
            'cases' => intval($row['cases']),
            'year' => intval($row['year'])
        ];
    }
    $histStmt->close();

    // Previous year data
    $previousYear = $year - 1;
    $histStmt = $conn->prepare($qHist);
    $histStmt->bind_param('siii', $barangay, $previousYear, $startWeek, $selectedWeek);
    $histStmt->execute();
    $result = $histStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $historical[] = [
            'morbidity_week' => intval($row['morbidity_week']),
            'cases' => intval($row['cases']),
            'year' => intval($row['year'])
        ];
    }
    $histStmt->close();

    $predictions = [];

    if (count($historical) >= 2) {
        $cases = array_column($historical, 'cases');
        $nonZeroCases = array_filter($cases, fn($c) => $c > 0);

        $P0 = reset($nonZeroCases);
        $Pn = end($nonZeroCases);
        $t = count($cases) - 1;
        $K = max($cases) * 1.2;

        // Dynamic threshold calculation (Moving Average + 2 SDs)
        $average = array_sum($cases) / count($cases);
        $variance = array_sum(array_map(fn($x) => pow($x - $average, 2), $cases)) / count($cases);
        $stdDev = sqrt($variance);
        $threshold = $average + (2 * $stdDev);

        error_log("Calculated dynamic threshold: " . $threshold);

        $denominator = $P0 * ($K - $Pn);

        if ($denominator > 0 && $Pn != 0 && $P0 != 0) {
            $numerator = $Pn * ($K - $P0);
            $r = (1 / $t) * log($numerator / $denominator);

            error_log("Calculated growth rate (r): " . $r);

            if ($r > 0 && $r < 2) {
                for ($i = 1; $i <= 5; $i++) {
                    $week = $selectedWeek + $i;
                    $yearForWeek = $year;
                    if ($week > 52) {
                        $week -= 52;
                        $yearForWeek += 1;
                    }

                    $pred = $K / (1 + (($K - $P0) / $P0) * exp(-$r * ($t + $i)));
                    $predVal = max(0, round($pred));

                    // Adjust prediction based on dynamic threshold
                    if ($predVal > $threshold) {
                        $predVal = round($threshold); // Clamp the predicted value to the threshold
                    }

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
            } else {
                error_log("Growth rate (r) out of expected range. Fallback triggered.");
            }
        } else {
            error_log("Invalid logistic calculation. Fallback triggered.");
        }

        // ✅ IMPROVED FALLBACK: Use only current year's data with Smoothing
        if (empty($predictions)) {
            $currentYearData = array_filter($historical, fn($row) => $row['year'] === $year);
            $recent = array_slice(array_column($currentYearData, 'cases'), -5);
            $countRecent = count($recent);
            $avg = $countRecent ? array_sum($recent) / $countRecent : 1;

            // Smoothing with Moving Average
            $slope = 0;
            if ($countRecent >= 2) {
                $xSum = $ySum = $xySum = $x2Sum = 0;
                for ($i = 0; $i < $countRecent; $i++) {
                    $x = $i;
                    $y = $recent[$i];
                    $xSum += $x;
                    $ySum += $y;
                    $xySum += $x * $y;
                    $x2Sum += $x * $x;
                }
                $slopeDenominator = ($countRecent * $x2Sum - $xSum * $xSum);
                if ($slopeDenominator != 0) {
                    $slope = ($countRecent * $xySum - $xSum * $ySum) / $slopeDenominator;
                }
            }

            // Boost slope slightly if it’s too flat but avg is high
            if (abs($slope) < 0.1 && $avg > 0) {
                $slope = 0.3;
            }

            for ($i = 1; $i <= 5; $i++) {
                $week = $selectedWeek + $i;
                $yearForWeek = $year;
                if ($week > 52) {
                    $week -= 52;
                    $yearForWeek += 1;
                }

                $predVal = round($avg + $slope * $i);
                $predVal = max(0, min(round($avg * 2), $predVal)); // clamp to avoid extreme values

                // Adjust prediction based on dynamic threshold
                if ($predVal > $threshold) {
                    $predVal = round($threshold); // Clamp the predicted value to the threshold
                }

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

            error_log("Fallback (current year only) used. Avg=$avg, Slope=$slope");
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
?>
