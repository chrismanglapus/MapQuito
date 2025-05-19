<?php
include('main/connection.php');
header('Content-Type: application/json');

$logs = [];

if (isset($_GET['barangay'], $_GET['year'], $_GET['week'])) {
    $barangay = $_GET['barangay'];
    $year = intval($_GET['year']);
    $selectedWeek = intval($_GET['week']);

    // Get latest week in database for this barangay and year (for reference/debug)
    $qLatest = "SELECT MAX(morbidity_week) as latest_week FROM morbidity_data WHERE barangay_name = ? AND year = ?";
    $stLatest = $conn->prepare($qLatest);
    $stLatest->bind_param('si', $barangay, $year);
    $stLatest->execute();
    $resLatest = $stLatest->get_result();
    $rowLatest = $resLatest->fetch_assoc();
    $referenceWeek = intval($rowLatest['latest_week']);
    $stLatest->close();

    // Define weeks to fetch in historical data: last 5 weeks + selected week
    $startWeek = max(1, $selectedWeek - 5);

    // Query to fetch morbidity data by week range, year and barangay
    $qHist = "SELECT morbidity_week, cases, year
              FROM morbidity_data
              WHERE barangay_name = ? 
              AND year = ? 
              AND morbidity_week BETWEEN ? AND ?
              ORDER BY morbidity_week ASC";

    // Function to fetch historical data for a specific year and week range
    function fetchHistoricalData($conn, $barangay, $year, $startWeek, $endWeek, $qHist) {
        $historical = [];
        $stmt = $conn->prepare($qHist);
        $stmt->bind_param('siii', $barangay, $year, $startWeek, $endWeek);
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

    // Fetch historical data for current year and previous year separately
    $historicalCurrentYear = fetchHistoricalData($conn, $barangay, $year, $startWeek, $selectedWeek, $qHist);
    $historicalPrevYear = fetchHistoricalData($conn, $barangay, $year - 1, $startWeek, $selectedWeek, $qHist);

    // Merge historical data for frontend (your frontend can differentiate by 'year' field)
    $historical = array_merge($historicalPrevYear, $historicalCurrentYear);

    $predictions = [];

    // Logistic prediction based on historical current year data ONLY
    if (count($historicalCurrentYear) >= 2) {
        $cases = array_column($historicalCurrentYear, 'cases');
        $nonZeroCases = array_filter($cases, fn($c) => $c > 0);

        if (count($nonZeroCases) >= 2) {
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

                if ($r < -0.5) {
                    $r = 0;
                }

                $logs[] = "Calculated growth rate (r): " . $r;

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

                        // Fetch actual cases if available for future weeks
                        $qActual = "SELECT cases FROM morbidity_data WHERE barangay_name = ? AND year = ? AND morbidity_week = ?";
                        $sActual = $conn->prepare($qActual);
                        $sActual->bind_param('sii', $barangay, $yearForWeek, $week);
                        $sActual->execute();
                        $rActual = $sActual->get_result();
                        $actualCases = $rActual->num_rows ? intval($rActual->fetch_assoc()['cases']) : null;
                        $sActual->close();

                        $predictions[] = [
                            'morbidity_week' => $week,
                            'year' => $yearForWeek,
                            'predicted_cases' => $predVal,
                            'actual_cases' => $actualCases
                        ];
                    }
                } else {
                    $logs[] = "Growth rate (r) out of expected range. Fallback triggered.";
                }
            } else {
                $logs[] = "Invalid logistic calculation. Fallback triggered.";
            }
        } else {
            $logs[] = "Not enough non-zero cases to calculate logistic growth. Fallback triggered.";
        }
    }

    // Fallback linear prediction if no logistic prediction generated
    if (empty($predictions)) {
        $recentCases = array_column($historicalCurrentYear, 'cases');
        $recent = array_slice($recentCases, -5);
        $avg = count($recent) ? array_sum($recent) / count($recent) : 1;

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

        $isDeclining = false;
        if (count($reactive) >= 3) {
            if ($reactive[2] < $reactive[1] && $reactive[1] < $reactive[0]) {
                $isDeclining = true;
                $slope = min($slope, 0);
            }
        }

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
            $predVal = max(0, min(round($avg * 2.5), $predVal));

            $qActual = "SELECT cases FROM morbidity_data WHERE barangay_name = ? AND year = ? AND morbidity_week = ?";
            $sActual = $conn->prepare($qActual);
            $sActual->bind_param('sii', $barangay, $yearForWeek, $week);
            $sActual->execute();
            $rActual = $sActual->get_result();
            $actualCases = $rActual->num_rows ? intval($rActual->fetch_assoc()['cases']) : null;
            $sActual->close();

            $predictions[] = [
                'morbidity_week' => $week,
                'year' => $yearForWeek,
                'predicted_cases' => $predVal,
                'actual_cases' => $actualCases
            ];
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
