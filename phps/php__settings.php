<?php
$messages = [];
$anyChange = false;

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $user_sql = "SELECT id, name, username, mobile, email, profile_picture_path FROM admin_users WHERE username=?";
    if ($stmt = $conn->prepare($user_sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    $new_name = $_POST['new_name'] ?? '';
    $new_username = $_POST['new_username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_mobile = $_POST['new_mobile'] ?? '';
    $new_email = $_POST['new_email'] ?? '';

    // Update user details
    $update_sql = "UPDATE admin_users SET name=?, username=?, mobile=?, email=? WHERE username=?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("sssss", $new_name, $new_username, $new_mobile, $new_email, $username);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $_SESSION['username'] = $new_username;
            $_SESSION['name'] = $new_name;
            $anyChange = true;
        }
    }

    // Update password if provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $pass_sql = "UPDATE admin_users SET password=? WHERE username=?";
        if ($stmt = $conn->prepare($pass_sql)) {
            $stmt->bind_param("ss", $hashed_password, $username);
            $stmt->execute();
            $anyChange = true;
        }
    }

    // Handle profile picture deletion
    if (isset($_POST['delete_profile_picture']) && $_POST['delete_profile_picture'] == "1") {
        $default_picture = "assets/uploads/default.png";
        $delete_sql = "UPDATE admin_users SET profile_picture_path=? WHERE username=?";
        if ($stmt = $conn->prepare($delete_sql)) {
            $stmt->bind_param("ss", $default_picture, $username);
            $stmt->execute();
            $_SESSION['profile_picture_path'] = $default_picture;
            $anyChange = true;
        }
    }

    function resizeImage($file, $maxWidth, $maxHeight, $quality = 80)
    {
        list($origWidth, $origHeight, $imageType) = getimagesize($file);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($file);
                break;
            default:
                return false;
        }

        // Fix image orientation based on EXIF data
        if ($imageType == IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = exif_read_data($file);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        }

        // Crop to square
        $sideLength = min($origWidth, $origHeight);
        $croppedImage = imagecreatetruecolor($maxWidth, $maxHeight);

        imagecopyresampled(
            $croppedImage,
            $image,
            0,
            0,
            ($origWidth - $sideLength) / 2,
            ($origHeight - $sideLength) / 2,
            $maxWidth,
            $maxHeight,
            $sideLength,
            $sideLength
        );

        imagejpeg($croppedImage, $file, $quality);
        imagedestroy($image);
        imagedestroy($croppedImage);

        return true;
    }

    function generateRandomString($length = 10)
    {
        return bin2hex(random_bytes($length));
    }

    if (isset($_FILES['profile_picture']) && !empty($_FILES['profile_picture']['name'])) {
        $targetDir = "assets/uploads/";
        $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));

        $randomString = generateRandomString(10);
        $uniqueName = "IMG_" . $randomString . "_" . time() . ".$imageFileType";
        $targetFile = $targetDir . $uniqueName;

        if (in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
                resizeImage($targetFile, 800, 800, 80);

                $profile_picture_path = $targetFile;
                $pic_sql = "UPDATE admin_users SET profile_picture_path=? WHERE username=?";
                if ($stmt = $conn->prepare($pic_sql)) {
                    $stmt->bind_param("ss", $profile_picture_path, $username);
                    $stmt->execute();
                    $_SESSION['profile_picture_path'] = $profile_picture_path;
                    $anyChange = true;
                    $_SESSION['showModal'] = true; // Add this to show modal on success
                }
            }
        }
    }

    $_SESSION['showModal'] = $anyChange ? true : false;
}

if ($anyChange && !isset($_SESSION['showModal'])) {
    $_SESSION['showModal'] = true;
}

$showModal = isset($_SESSION['showModal']) && $_SESSION['showModal'];
unset($_SESSION['showModal']); // Unset to avoid showing the modal on refresh
