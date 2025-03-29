<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $god_image = $_FILES['god_image']['name'] ? 'gods/' . basename($_FILES['god_image']['name']) : null;
    $background_image = 'backgrounds/' . basename($_FILES['background_image']['name']);
    $photo = $_FILES['photo']['name'] ? 'persons/' . basename($_FILES['photo']['name']) : null;

    if ($god_image) move_uploaded_file($_FILES['god_image']['tmp_name'], 'assets/images/' . $god_image);
    move_uploaded_file($_FILES['background_image']['tmp_name'], 'assets/images/' . $background_image);
    if ($photo) move_uploaded_file($_FILES['photo']['tmp_name'], 'assets/images/' . $photo);

    $god_name = $_POST['god_name'];
    $biodata = json_encode($_POST['biodata']);
    $family_details = json_encode($_POST['family_details']);
    $contact_details = !empty($_POST['contact_details']) ? json_encode($_POST['contact_details']) : null;

    $preview_image = generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo);

    try {
        $stmt = $pdo->prepare("INSERT INTO templates (god_image, god_name, biodata, family_details, contact_details, background_image, photo, preview_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo, $preview_image]);
        $db_success = true;
    } catch (PDOException $e) {
        $db_success = false;
        echo "Database Error: " . $e->getMessage();
    }

    echo "<h3>Preview Generated " . ($db_success ? "and Saved Successfully!" : "but Failed to Save to Database!") . "</h3>";
    echo "<img src='assets/images/$preview_image' alt='Preview' style='max-width: 100%;'>";
}

function generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo)
{
    $width = 794;
    $height = 1123;
    $padding_left_right = 90;
    $padding_top_bottom = 30;
    $content_width = $width - 2 * $padding_left_right;
    $content_height = $height - 2 * $padding_top_bottom;

    $canvas = imagecreatetruecolor($width, $height);
    $bg = loadImage('assets/images/' . $background_image);
    if (!$bg) die("Error: Failed to load background image.");
    $bg_width = imagesx($bg);
    $bg_height = imagesy($bg);
    $bg_ratio = $bg_width / $bg_height;
    $canvas_ratio = $width / $height;
    if ($bg_ratio > $canvas_ratio) {
        $new_bg_height = $height;
        $new_bg_width = (int)($bg_ratio * $height);
    } else {
        $new_bg_width = $width;
        $new_bg_height = (int)($width / $bg_ratio);
    }
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, (int)$new_bg_width, (int)$new_bg_height, $bg_width, $bg_height);
    imagedestroy($bg);

    $font_regular = 'C:/Windows/Fonts/times.ttf';
    $font_bold = 'C:/Windows/Fonts/timesbd.ttf';
    $black = imagecolorallocate($canvas, 0, 0, 0);
    $default_font_size = 10;
    $current_y = $padding_top_bottom;

    // Calculate total height and scale if necessary
    $total_height = 0;
    $god_height = $god_image ? 115 : 0; // God image height + spacing
    $god_name_height = 36; // God name size + spacing
    $biodata_height = calculateSectionHeight(json_decode($biodata, true), $default_font_size, $font_regular, $content_width);
    $family_height = calculateSectionHeight(json_decode($family_details, true), $default_font_size, $font_regular, $content_width);
    $contact_height = $contact_details ? calculateSectionHeight(json_decode($contact_details, true), $default_font_size, $font_regular, $content_width) : 0;
    $total_height = $god_height + $god_name_height + $biodata_height + $family_height + $contact_height;

    $scale = $total_height > $content_height ? $content_height / $total_height : 1;
    $default_font_size *= $scale;
    $god_name_size = 16 * $scale;
    $title_size = 14 * $scale;
    $god_width = $god_image ? 100 * $scale : 0;
    $god_height = $god_image ? 100 * $scale : 0;
    $person_width = $photo ? 130 * $scale : 0;
    $person_height = $photo ? 173 * $scale : 0;

    if ($god_image) {
        $god_img = loadImage('assets/images/' . $god_image);
        if (!$god_img) die("Error: Failed to load god image.");
        $god_x = (int)(($width - $god_width) / 2);
        imagecopyresampled($canvas, $god_img, $god_x, $current_y, 0, 0, $god_width, $god_height, imagesx($god_img), imagesy($god_img));
        imagedestroy($god_img);
        $current_y += $god_height + 15 * $scale;
    }

    $god_name_box = imagettfbbox($god_name_size, 0, $font_bold, $god_name);
    $god_name_width = $god_name_box[2] - $god_name_box[0];
    imagettftext($canvas, $god_name_size, 0, (int)(($width - $god_name_width) / 2), $current_y, $black, $font_bold, $god_name);
    $god_name_y = $current_y;
    $current_y += 20 * $scale;

    $text_area_width = (int)(0.7 * $content_width);
    $photo_area_x = $padding_left_right + $text_area_width + 20 * $scale;

    $biodata_title = "Biodata";
    $title_box = imagettfbbox($title_size, 0, $font_bold, $biodata_title);
    $title_width = $title_box[2] - $title_box[0];
    $current_y += 8 * $scale;
    imagettftext($canvas, $title_size, 0, (int)(($width - $title_width) / 2), $current_y, $black, $font_bold, $biodata_title);
    $current_y += 25 * $scale;

    $biodata = json_decode($biodata, true);
    $max_key_width = 0;
    foreach ($biodata as $key => $value) {
        $key_text = $key . ":";
        $key_box = imagettfbbox($default_font_size, 0, $font_regular, $key_text);
        $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
    }
    foreach ($biodata as $key => $value) {
        $key_text = $key . ":";
        imagettftext($canvas, $default_font_size, 0, $padding_left_right, $current_y, $black, $font_regular, $key_text);
        $value_x = $padding_left_right + $max_key_width + 10 * $scale;
        $lines = wrapText($value, $text_area_width - $max_key_width - 10 * $scale, $font_regular, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding_top_bottom, $scale);
        $current_y += count($lines) * 15 * $scale;
    }

    if ($current_y + 40 * $scale < $height - $padding_top_bottom) {
        $family_title = "Family Details";
        $title_box = imagettfbbox($title_size, 0, $font_bold, $family_title);
        $title_width = $title_box[2] - $title_box[0];
        $current_y += 5 * $scale;
        imagettftext($canvas, $title_size, 0, (int)(($width - $title_width) / 2), $current_y, $black, $font_bold, $family_title);
        $current_y += 25 * $scale;
        $family_details = json_decode($family_details, true);
        $max_key_width = 0;
        foreach ($family_details as $key => $value) {
            $key_text = $key . ":";
            $key_box = imagettfbbox($default_font_size, 0, $font_regular, $key_text);
            $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
        }
        foreach ($family_details as $key => $value) {
            $key_text = $key . ":";
            imagettftext($canvas, $default_font_size, 0, $padding_left_right, $current_y, $black, $font_regular, $key_text);
            $value_x = $padding_left_right + $max_key_width + 10 * $scale;
            $lines = wrapText($value, $text_area_width - $max_key_width - 10 * $scale, $font_regular, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding_top_bottom, $scale);
            $current_y += count($lines) * 15 * $scale;
        }
    }

    if ($contact_details && $current_y + 40 * $scale < $height - $padding_top_bottom) {
        $contact_title = "Contact Details";
        $title_box = imagettfbbox($title_size, 0, $font_bold, $contact_title);
        $title_width = $title_box[2] - $title_box[0];
        $current_y += 5 * $scale;
        imagettftext($canvas, $title_size, 0, (int)(($width - $title_width) / 2), $current_y, $black, $font_bold, $contact_title);
        $current_y += 25 * $scale;
        $contact_details = json_decode($contact_details, true);
        $max_key_width = 0;
        foreach ($contact_details as $key => $value) {
            $key_text = $key . ":";
            $key_box = imagettfbbox($default_font_size, 0, $font_regular, $key_text);
            $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
        }
        foreach ($contact_details as $key => $value) {
            $key_text = $key . ":";
            imagettftext($canvas, $default_font_size, 0, $padding_left_right, $current_y, $black, $font_regular, $key_text);
            $value_x = $padding_left_right + $max_key_width + 10 * $scale;
            $lines = wrapText($value, $text_area_width - $max_key_width - 10 * $scale, $font_regular, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding_top_bottom, $scale);
            $current_y += count($lines) * 15 * $scale;
        }
    }

    if ($photo) {
        $person_img = loadImage('assets/images/' . $photo);
        if (!$person_img) die("Error: Failed to load person photo.");
        $person_x = (int)$photo_area_x;
        $person_y = $god_name_y + 20 * $scale;
        $img_width = imagesx($person_img);
        $img_height = imagesy($person_img);
        $img_ratio = $img_width / $img_height;
        $target_ratio = $person_width / $person_height;
        if ($img_ratio > $target_ratio) {
            $crop_width = (int)($img_height * $target_ratio);
            $crop_x = (int)(($img_width - $crop_width) / 2);
            $crop_y = 0;
            $crop_height = $img_height;
        } else {
            $crop_height = (int)($img_width / $target_ratio);
            $crop_y = (int)(($img_height - $crop_height) / 2);
            $crop_x = 0;
            $crop_width = $img_width;
        }
        imagecopyresampled($canvas, $person_img, $person_x, $person_y, $crop_x, $crop_y, $person_width, $person_height, $crop_width, $crop_height);
        imagedestroy($person_img);
    }

    $preview_path = 'previews/template' . time() . '_preview.png';
    imagepng($canvas, 'assets/images/' . $preview_path);
    imagedestroy($canvas);
    return $preview_path;
}

function loadImage($path)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            return imagecreatefromjpeg($path);
        case 'png':
            return imagecreatefrompng($path);
        default:
            return false;
    }
}

function wrapText($text, $max_width, $font, $font_size, &$canvas, &$black, $x, &$y, $max_height, $scale)
{
    $adjusted_font_size = $font_size;
    if (strlen($text) > 50) {
        $adjusted_font_size = max(6 * $scale, $font_size - (strlen($text) - 50) / 20);
    }
    $words = explode(' ', $text);
    $lines = [];
    $current_line = '';
    $line_height = 15 * $scale;

    foreach ($words as $word) {
        $test_line = $current_line . ($current_line ? ' ' : '') . $word;
        $box = imagettfbbox($adjusted_font_size, 0, $font, $test_line);
        $width = $box[2] - $box[0];
        if ($width > $max_width && $current_line) {
            $lines[] = $current_line;
            $current_line = $word;
        } else {
            $current_line = $test_line;
        }
    }
    if ($current_line) {
        $lines[] = $current_line;
    }

    $total_height = count($lines) * $line_height;
    if ($y + $total_height <= $max_height) {
        foreach ($lines as $line) {
            imagettftext($canvas, $adjusted_font_size, 0, $x, $y, $black, $font, $line);
            $y += $line_height;
        }
    }
    return $lines;
}

function calculateSectionHeight($data, $font_size, $font, $content_width)
{
    $height = 30; // Title + spacing
    $max_key_width = 0;
    foreach ($data as $key => $value) {
        $key_box = imagettfbbox($font_size, 0, $font, $key . ":");
        $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
    }
    $text_width = (int)(0.7 * $content_width) - $max_key_width - 10;
    foreach ($data as $value) {
        $words = explode(' ', $value);
        $lines = [];
        $current_line = '';
        foreach ($words as $word) {
            $test_line = $current_line . ($current_line ? ' ' : '') . $word;
            $box = imagettfbbox($font_size, 0, $font, $test_line);
            if (($box[2] - $box[0]) > $text_width && $current_line) {
                $lines[] = $current_line;
                $current_line = $word;
            } else {
                $current_line = $test_line;
            }
        }
        if ($current_line) $lines[] = $current_line;
        $height += count($lines) * 15;
    }
    return $height;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Template</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-5">
        <h1>Upload New Template</h1>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label>God Image (optional):</label>
                <input type="file" name="god_image" class="form-control">
            </div>
            <div class="mb-3">
                <label>God Name:</label>
                <input type="text" name="god_name" class="form-control" required>
            </div>
            <h3>Biodata</h3>
            <?php foreach (['Full Name', 'Date of Birth', 'Place of Birth', 'Religion', 'Caste', 'Kuldivat', 'Zodiac Sign', 'Constellation', 'Clan', 'Pulse', 'Manglik', 'Tribe', 'Height', 'Character', 'Blood Type', 'Job/Business', 'Education', 'Salary/Income'] as $field): ?>
                <div class="mb-3">
                    <label><?php echo $field; ?>:</label>
                    <input type="text" name="biodata[<?php echo $field; ?>]" class="form-control" required>
                </div>
            <?php endforeach; ?>
            <h3>Family Details</h3>
            <?php foreach (['Father\'s Name', 'Father\'s Profession', 'Mother\'s Name', 'Mother\'s Profession', 'Sister', 'Brother', 'Uncle', 'Relationship'] as $field): ?>
                <div class="mb-3">
                    <label><?php echo $field; ?>:</label>
                    <input type="text" name="family_details[<?php echo $field; ?>]" class="form-control" required>
                </div>
            <?php endforeach; ?>
            <h3>Contact Details (optional)</h3>
            <?php foreach (['Address', 'Mobile Number'] as $field): ?>
                <div class="mb-3">
                    <label><?php echo $field; ?>:</label>
                    <input type="text" name="contact_details[<?php echo $field; ?>]" class="form-control">
                </div>
            <?php endforeach; ?>
            <div class="mb-3">
                <label>Background Image:</label>
                <input type="file" name="background_image" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Person Photo (optional):</label>
                <input type="file" name="photo" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Upload Template</button>
        </form>
    </div>
</body>

</html>