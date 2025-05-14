<?php
require('main/session_users.php');
include('phps/php__barangay_list.php')
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/barangay_list.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <title>Manage Data | MapQuito</title>
</head>

<body class="theme-orange">

    <main class="barangay-list" id="barangayList">

        <!-- ✅ Page Header -->
        <header class="barangay-list__header">
            <h1>Barangay List for Year: <?php echo $year; ?></h1>
        </header>

        <!-- ✅ Message Section (If No Barangays Exist) -->
        <?php if (empty($barangays)) : ?>
            <section id="message-section">
                <p style="color:red; text-align:center; font-size: 18px;">No barangay data available for this year.</p>
                <button class="back-btn" onclick="window.location.href='manage_data.php'; return false;">Back</button>
            </section>
        <?php else : ?>

            <!-- ✅ Form Section -->
            <section id="form-section">
                <form method="post" action="barangay_list__delete_barangay.php" id="deleteForm">
                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($year, ENT_QUOTES); ?>">

                    <!-- ✅ Sticky Delete Button Container (NOW INSIDE FORM) -->
                    <div class="sticky-container">
                        <button class="back-btn" onclick="window.location.href='manage_data.php'; return false;"> Back</button>
                        <button type="submit" name="deleteSelected" class="delete-btn">Delete Selected</button>
                        <button type="submit" name="deleteAll" class="delete-btn" onclick="return confirmDeleteAll();">Delete All</button>
                    </div>

                    <!-- ✅ Table Section -->
                    <section id="table-section">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>Barangay Name</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barangays as $barangay) : ?>
                                        <tr>
                                            <td><input type="checkbox" name="selected_barangays[]" value="<?php echo htmlspecialchars($barangay, ENT_QUOTES); ?>"></td>
                                            <td><?php echo htmlspecialchars($barangay, ENT_QUOTES); ?></td>
                                            <td>
                                                <button type="button" class="btn-tertiary" onclick="showData('<?php echo urlencode($barangay); ?>', '<?php echo urlencode($year); ?>')">
                                                    <span class="text">Show Data</span>
                                                </button>
                                                <input type="hidden" name="barangay" value="<?php echo urlencode($barangay); ?>">
                                                <input type="hidden" name="year" value="<?php echo urlencode($year); ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </form>
            </section>

        <?php endif; ?>
    </main>

    <script src="js/barangay_list.js" defer></script>
</body>

</html>