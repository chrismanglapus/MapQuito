<?php
include('main/connection.php');
header('Content-Type: application/json');

$logs = [];

if (isset($_GET['barangay'], $_GET['year'], $_GET['week'])) {
    $barangay = $_GET['barangay'];
    $year = intval($_GET['year']);
    $selectedWeek = intval($_GET['week']);

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

    function fetchHistoricalData($conn, $barangay, $year, $startWeek, $selectedWeek, $qHist) {
        $historical = [];
        $stmt = $conn->prepare($qHist);
        $stmt->bind_param('siii', $barangay, $year, $startWeek, $selectedWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $historical[] = [
                'morbidity_week' => intval($row['morbidity_week']),
                'cases' => intval($row['cases']),
                'year' => intval($row['year'])
            ];
        }
        $stmt->close();
        return $historical;
    }

    $historical = array_merge(
        fetchHistoricalData($conn, $barangay, $year, $startWeek, $selectedWeek, $qHist),
        fetchHistoricalData($conn, $barangay, $year - 1, $startWeek, $selectedWeek, $qHist)
    );

    $predictions = [];

    if (count($historical) >= 2) {
        $cases = array_column($historical, 'cases');
        $nonZeroCases = array_filter($cases, fn($c) => $c > 0);

        $P0 = reset($nonZeroCases);
        $Pn = end($nonZeroCases);
        $t = count($cases) - 1;
        $K = max($cases) * 1.2;

        $denominator = $P0 * ($K - $Pn);
        $thresholdMultiplier = 1.5;
        $threshold = max($cases) * $thresholdMultiplier;
        $isSpike = max($cases) > $threshold;

        $logs[] = "P0: $P0, Pn: $Pn, t: $t, K: $K, Denominator: $denominator, Threshold: $threshold, Is Spike: " . ($isSpike ? 'Yes' : 'No');

        if ($denominator > 0 && $Pn != 0 && $P0 != 0) {
            $numerator = $Pn * ($K - $P0);
            $r = (1 / $t) * log($numerator / $denominator);

            // Adjust for declining trends or unrealistic drops
            if ($r < -0.5) {
                $r = 0; // Avoid false decline detection by setting a floor for r
            }

            $logs[] = "Calculated growth rate (r): " . $r;

            // Detect if spike is present and adjust the K value accordingly
            if ($isSpike) {
                $K = max($cases) * 1.3;
            }

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

                    // Ensure that predictions do not suddenly drop after high cases
                    if (empty($predictions)) {
                        $predictions[] = [
                            'morbidity_week' => $week,
                            'year' => $yearForWeek,
                            'predicted_cases' => $predVal,
                            'actual_cases' => null
                        ];
                    } else {
                        // Ensure prediction stays within range of prior values
                        $lastPrediction = end($predictions);
                        $prevPrediction = $lastPrediction['predicted_cases'];

                        // If the predicted value drops significantly, force it to stay above the previous prediction
                        if ($predVal < $prevPrediction * 0.5) {
                            $predVal = $prevPrediction;
                        }

                        $predictions[] = [
                            'morbidity_week' => $week,
                            'year' => $yearForWeek,
                            'predicted_cases' => $predVal,
                            'actual_cases' => null
                        ];
                    }

                }
            } else {
                $logs[] = "Growth rate (r) out of expected range. Fallback triggered.";
            }
        } else {
            $logs[] = "Invalid logistic calculation. Fallback triggered.";
        }

        if (empty($predictions)) {
            $currentYearData = array_filter($historical, fn($row) => $row['year'] === $year);
            $recentCases = array_column($currentYearData, 'cases');
            $recent = array_slice($recentCases, -5);
            $avg = count($recent) ? array_sum($recent) / count($recent) : 1;

            // Use only the last 3 weeks for slope calculation
            $reactive = array_slice($recentCases, -3);
            $slope = 0;

            if (count($reactive) >= 2) {
                $xSum = $ySum = $xySum = $x2Sum = 0;
                foreach ($reactive as $i => $y) {
                    $x = $i;
                    $xSum += $x;
                    $ySum += $y;
                    $xySum += $x * $y;
                    $x2Sum += $x * $x;
                }
                $slopeDenominator = (count($reactive) * $x2Sum - $xSum * $xSum);
                if ($slopeDenominator != 0) {
                    $slope = (count($reactive) * $xySum - $xSum * $ySum) / $slopeDenominator;
                }
            }

            // Detect if cases are declining based on the difference between weeks
            $isDeclining = false;
            if (count($reactive) >= 3) {
                if ($reactive[2] < $reactive[1] && $reactive[1] < $reactive[0]) {
                    $isDeclining = true;
                    $slope = min($slope, 0); // Ensure slope doesn't turn positive if declining trend
                }
            }

            // Debug log for slope values
            $logs[] = "Reactive weeks: " . implode(", ", $reactive) . " -> Slope calculation: " . $slope;

            // Weaken seasonal adjustment if decline is detected
            $seasonalAdjustment = ($selectedWeek >= 24 && $selectedWeek <= 36) ? ($isDeclining ? 1.0 : 1.3) : 1;

            $logs[] = "Fallback used. Avg=$avg, Slope=$slope, Seasonal=$seasonalAdjustment";

            for ($i = 1; $i <= 5; $i++) {
                $week = $selectedWeek + $i;
                $yearForWeek = $year;
                if ($week > 52) {
                    $week -= 52;
                    $yearForWeek += 1;
                }

                $rawPred = $avg + $slope * $i;
                $predVal = round($rawPred * $seasonalAdjustment);
                $predVal = max(0, min(round($avg * 2.5), $predVal)); // Tight cap to prevent extreme values

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

    echo json_encode([
        'status' => 'success',
        'historical' => $historical,
        'predictions' => $predictions,
        'logs' => $logs
    ]);

    $conn->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing barangay, year, or week parameter'
    ]);
}
?>
