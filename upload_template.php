<?php
// Include database connection
include 'config/db.php';

// Suppress libpng warnings
ini_set('gd.png_ignore_warning', 1); // Global suppression attempt
libxml_use_internal_errors(true); // Suppress XML-related warnings if any

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file uploads
    $god_image = !empty($_FILES['god_image']['name']) ? 'gods/' . basename($_FILES['god_image']['name']) : null;
    $background_image = 'backgrounds/' . basename($_FILES['background_image']['name']);
    $photo = !empty($_FILES['photo']['name']) ? 'persons/' . basename($_FILES['photo']['name']) : null;

    // Move uploaded files to assets/images/
    if ($god_image) move_uploaded_file($_FILES['god_image']['tmp_name'], 'assets/images/' . $god_image);
    move_uploaded_file($_FILES['background_image']['tmp_name'], 'assets/images/' . $background_image);
    if ($photo) move_uploaded_file($_FILES['photo']['tmp_name'], 'assets/images/' . $photo);

    // Collect text data
    $god_name = $_POST['god_name'];
    $biodata = json_encode($_POST['biodata']);
    $family_details = json_encode($_POST['family_details']);
    $contact_details = !empty($_POST['contact_details']) ? json_encode($_POST['contact_details']) : null;

    // Generate preview image
    $preview_image = generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo);

    // Insert into database
    try {
        $stmt = $pdo->prepare("INSERT INTO templates (god_image, god_name, biodata, family_details, contact_details, background_image, photo, preview_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo, $preview_image]);
        $db_success = true;
    } catch (PDOException $e) {
        $db_success = false;
        echo "Database Error: " . $e->getMessage();
    }

    // Display the preview
    echo "<h3>Preview Generated " . ($db_success ? "and Data Saved Successfully!" : "but Data Failed to Save!") . "</h3>";
    echo "<img src='assets/images/$preview_image' alt='Preview' style='max-width: 100%;'>";
}

function generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo)
{
    // A4 dimensions at 96 DPI
    $width = 794;
    $height = 1123;
    $padding = 50;
    $content_width = $width - 2 * $padding;
    $content_height = $height - 2 * $padding;
    $current_y = $padding;

    // Create canvas
    $canvas = imagecreatetruecolor($width, $height);

    // Load and scale background image
    $bg = loadImage('assets/images/' . $background_image);
    if (!$bg) die("Error: Failed to load background image.");
    $bg_width = imagesx($bg);
    $bg_height = imagesy($bg);
    $bg_ratio = $bg_width / $bg_height;
    $canvas_ratio = $width / $height;
    if ($bg_ratio > $canvas_ratio) {
        $new_bg_height = $height;
        $new_bg_width = (int)($bg_ratio * $height); // Explicit cast to int
    } else {
        $new_bg_width = $width;
        $new_bg_height = (int)($width / $bg_ratio); // Explicit cast to int
    }
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, $new_bg_width, $new_bg_height, $bg_width, $bg_height);
    imagedestroy($bg);

    // Define font and color
    $font = 'C:/Windows/Fonts/arial.ttf'; // Update to your server's font path
    $black = imagecolorallocate($canvas, 0, 0, 0);
    $default_font_size = 12; // Smaller default font size

    // Add god image (centered)
    if ($god_image) {
        $god_img = loadImage('assets/images/' . $god_image);
        if (!$god_img) die("Error: Failed to load god image.");
        $god_width = 100;
        $god_height = 100;
        $god_x = ($width - $god_width) / 2;
        imagecopyresampled($canvas, $god_img, $god_x, $current_y, 0, 0, $god_width, $god_height, imagesx($god_img), imagesy($god_img));
        imagedestroy($god_img);
        $current_y += $god_height + 20; // Reduced spacing
    }

    // Add god name (centered)
    $god_name_size = 16;
    $god_name_box = imagettfbbox($god_name_size, 0, $font, $god_name);
    $god_name_width = $god_name_box[2] - $god_name_box[0];
    imagettftext($canvas, $god_name_size, 0, ($width - $god_name_width) / 2, $current_y, $black, $font, $god_name);
    $current_y += 20; // Reduced spacing

    // Add "Biodata" title
    $title_size = 14;
    imagettftext($canvas, $title_size, 0, $padding, $current_y, $black, $font, "Biodata");
    $current_y += 20;

    // Add biodata details with tabular structure
    $biodata = json_decode($biodata, true);
    $max_key_width = 0;
    foreach ($biodata as $key => $value) {
        $key_text = $key . ":";
        $key_box = imagettfbbox($default_font_size, 0, $font, $key_text);
        $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
    }
    foreach ($biodata as $key => $value) {
        $key_text = $key . ":";
        imagettftext($canvas, $default_font_size, 0, $padding, $current_y, $black, $font, $key_text);
        $value_x = $padding + $max_key_width + 20;
        $lines = wrapText($value, $content_width - $max_key_width - 20, $font, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding);
        $current_y += count($lines) * 15; // Compact line height
    }

    // Add "Family Details" title
    if ($current_y + 40 < $height - $padding) {
        imagettftext($canvas, $title_size, 0, $padding, $current_y, $black, $font, "Family Details");
        $current_y += 20;
        $family_details = json_decode($family_details, true);
        $max_key_width = 0;
        foreach ($family_details as $key => $value) {
            $key_text = $key . ":";
            $key_box = imagettfbbox($default_font_size, 0, $font, $key_text);
            $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
        }
        foreach ($family_details as $key => $value) {
            $key_text = $key . ":";
            imagettftext($canvas, $default_font_size, 0, $padding, $current_y, $black, $font, $key_text);
            $value_x = $padding + $max_key_width + 20;
            $lines = wrapText($value, $content_width - $max_key_width - 20, $font, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding);
            $current_y += count($lines) * 15;
        }
    }

    // Add "Contact Details" title (if exists)
    if ($contact_details && $current_y + 40 < $height - $padding) {
        imagettftext($canvas, $title_size, 0, $padding, $current_y, $black, $font, "Contact Details");
        $current_y += 20;
        $contact_details = json_decode($contact_details, true);
        $max_key_width = 0;
        foreach ($contact_details as $key => $value) {
            $key_text = $key . ":";
            $key_box = imagettfbbox($default_font_size, 0, $font, $key_text);
            $max_key_width = max($max_key_width, $key_box[2] - $key_box[0]);
        }
        foreach ($contact_details as $key => $value) {
            $key_text = $key . ":";
            imagettftext($canvas, $default_font_size, 0, $padding, $current_y, $black, $font, $key_text);
            $value_x = $padding + $max_key_width + 20;
            $lines = wrapText($value, $content_width - $max_key_width - 20, $font, $default_font_size, $canvas, $black, $value_x, $current_y, $height - $padding);
            $current_y += count($lines) * 15;
        }
    }

    // Add person photo (top-right)
    if ($photo) {
        $person_img = loadImage('assets/images/' . $photo);
        if (!$person_img) die("Error: Failed to load person photo.");
        $person_width = 130;
        $person_height = 173;
        $person_x = $width - $person_width - $padding;
        $person_y = $padding;

        // Zoom and crop to maintain 3:4 aspect ratio
        $img_width = imagesx($person_img);
        $img_height = imagesy($person_img);
        $img_ratio = $img_width / $img_height;
        $target_ratio = $person_width / $person_height;
        if ($img_ratio > $target_ratio) {
            $crop_width = $img_height * $target_ratio;
            $crop_x = ($img_width - $crop_width) / 2;
            $crop_y = 0;
            $crop_height = $img_height;
        } else {
            $crop_height = $img_width / $target_ratio;
            $crop_y = ($img_height - $crop_height) / 2;
            $crop_x = 0;
            $crop_width = $img_width;
        }
        imagecopyresampled($canvas, $person_img, $person_x, $person_y, $crop_x, $crop_y, $person_width, $person_height, $crop_width, $crop_height);
        imagedestroy($person_img);
    }

    // Save and return preview
    $preview_path = 'previews/template' . time() . '_preview.png';
    imagepng($canvas, 'assets/images/' . $preview_path);
    imagedestroy($canvas);
    return $preview_path;
}

// Helper function to load image with warning suppression
function loadImage($path)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            return @imagecreatefromjpeg($path); // Suppress warnings
        case 'png':
            return @imagecreatefrompng($path);  // Suppress warnings
        default:
            return false;
    }
}

// Helper function to wrap text with dynamic font size
function wrapText($text, $max_width, $font, $font_size, &$canvas, &$black, $x, &$y, $max_height)
{
    $words = explode(' ', $text);
    $lines = [];
    $current_line = '';
    $min_font_size = 8; // Minimum font size for compactness
    $adjusted_font_size = $font_size;

    // Initial wrapping
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

    // Adjust font size if content exceeds height
    $line_height = 15; // Compact line height
    $total_height = count($lines) * $line_height;
    while ($y + $total_height > $max_height && $adjusted_font_size > $min_font_size) {
        $adjusted_font_size--;
        $lines = [];
        $current_line = '';
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
    }

    // Render lines if they fit
    if ($y + $total_height <= $max_height) {
        foreach ($lines as $line) {
            imagettftext($canvas, $adjusted_font_size, 0, $x, $y, $black, $font, $line);
            $y += $line_height;
        }
    }
    return $lines;
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