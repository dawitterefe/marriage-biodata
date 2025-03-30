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
    // Constants
    $width = 794;
    $height = 1123;
    $padding_left_right = 90;
    $padding_top_bottom = 30;
    $reserved_top_area = 150; // Fixed reserved space at top

    // Create canvas and load background
    $canvas = imagecreatetruecolor($width, $height);
    $bg = loadImage('assets/images/' . $background_image);
    if (!$bg) die("Error loading background image");

    // Fill canvas with background
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, $width, $height, imagesx($bg), imagesy($bg));
    imagedestroy($bg);

    // Place god image in reserved area if exists
    $current_y = $padding_top_bottom;
    if ($god_image) {
        $god_img = loadImage('assets/images/' . $god_image);
        if ($god_img) {
            $max_width = 100;
            $max_height = 100;
            $orig_width = imagesx($god_img);
            $orig_height = imagesy($god_img);

            // Calculate aspect ratio
            $ratio = min($max_width / $orig_width, $max_height / $orig_height);
            $new_width = (int)($orig_width * $ratio);
            $new_height = (int)($orig_height * $ratio);

            // Center in reserved area
            $x = (int)(($width - $new_width) / 2);
            $y = $padding_top_bottom + (int)(($reserved_top_area - $new_height) / 2);
            imagecopyresampled($canvas, $god_img, $x, $y, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            imagedestroy($god_img);
        }
    }

    // Start content below reserved area
    $current_y = $padding_top_bottom + $reserved_top_area + 20;
    $max_content_height = $height - $padding_top_bottom - $reserved_top_area - $padding_top_bottom;

    // Prepare fonts and colors
    $font_regular = 'C:/Windows/Fonts/times.ttf';
    $font_bold = 'C:/Windows/Fonts/timesbd.ttf';
    $black = imagecolorallocate($canvas, 0, 0, 0);

    // Initial font sizes
    $base_font_size = 10;
    $god_name_size = 16;
    $title_size = 14;

    // Calculate content heights
    $content_width = $width - 2 * $padding_left_right;
    $elements_height = 0;

    // God name height
    $elements_height += $god_name_size + 20;

    // Biodata height
    $biodata_data = json_decode($biodata, true);
    $biodata_height = calculateSectionHeight($biodata_data, $base_font_size, $font_regular, $content_width);
    $elements_height += $biodata_height;

    // Family details height
    $family_data = json_decode($family_details, true);
    $family_height = calculateSectionHeight($family_data, $base_font_size, $font_regular, $content_width);
    $elements_height += $family_height;

    // Contact details height
    $contact_height = 0;
    if ($contact_details) {
        $contact_data = json_decode($contact_details, true);
        $contact_height = calculateSectionHeight($contact_data, $base_font_size, $font_regular, $content_width);
        $elements_height += $contact_height;
    }

    // Calculate scaling factor
    $scale = 1;
    if ($elements_height > $max_content_height) {
        $scale = $max_content_height / $elements_height;
    }

    // Apply scaling
    $base_font_size *= $scale;
    $god_name_size *= $scale;
    $title_size *= $scale;
    $line_height = 15 * $scale;
    $text_section_width = $content_width * 0.7;

    // Draw god name
    $current_y = $padding_top_bottom + $reserved_top_area + 20;
    $god_name_box = imagettfbbox($god_name_size, 0, $font_bold, $god_name);
    $god_name_x = (int)(($width - ($god_name_box[2] - $god_name_box[0])) / 2);
    imagettftext($canvas, $god_name_size, 0, $god_name_x, $current_y, $black, $font_bold, $god_name);
    $current_y += $god_name_size + 20 * $scale;

    // Draw biodata
    $current_y = drawSection(
        $canvas,
        'Biodata',
        $biodata_data,
        $current_y,
        $title_size,
        $base_font_size,
        $font_bold,
        $font_regular,
        $padding_left_right,
        $text_section_width,
        $line_height,
        $black,
        $scale
    );

    // Draw family details
    $current_y = drawSection(
        $canvas,
        'Family Details',
        $family_data,
        $current_y,
        $title_size,
        $base_font_size,
        $font_bold,
        $font_regular,
        $padding_left_right,
        $text_section_width,
        $line_height,
        $black,
        $scale
    );

    // Draw contact details if exists
    if ($contact_details) {
        $current_y = drawSection(
            $canvas,
            'Contact Details',
            $contact_data,
            $current_y,
            $title_size,
            $base_font_size,
            $font_bold,
            $font_regular,
            $padding_left_right,
            $text_section_width,
            $line_height,
            $black,
            $scale
        );
    }

    // Add person photo if exists
    if ($photo) {
        $photo_path = 'assets/images/' . $photo;
        $person_img = loadImage($photo_path);
        if ($person_img) {
            $target_width = 130 * $scale;
            $target_height = 173 * $scale;

            // Calculate position
            $photo_x = $width - $padding_left_right - $target_width;
            $photo_y = $padding_top_bottom + $reserved_top_area + 20;

            imagecopyresampled(
                $canvas,
                $person_img,
                $photo_x,
                $photo_y,
                0,
                0,
                $target_width,
                $target_height,
                imagesx($person_img),
                imagesy($person_img)
            );
            imagedestroy($person_img);
        }
    }

    // Save preview
    $preview_path = 'previews/template_' . time() . '.png';
    imagepng($canvas, 'assets/images/' . $preview_path);
    imagedestroy($canvas);

    return $preview_path;
}

function drawSection(
    $canvas,
    $title,
    $data,
    $current_y,
    $title_size,
    $font_size,
    $font_bold,
    $font_regular,
    $padding,
    $text_width,
    $line_height,
    $color,
    $scale
) {
    // Draw section title
    $title_box = imagettfbbox($title_size, 0, $font_bold, $title);
    $title_x = (int)((794 - ($title_box[2] - $title_box[0])) / 2);
    imagettftext($canvas, $title_size, 0, $title_x, $current_y, $color, $font_bold, $title);
    $current_y += $title_size + 15 * $scale;

    // Calculate max key width
    $max_key_width = 0;
    foreach ($data as $key => $value) {
        $box = imagettfbbox($font_size, 0, $font_regular, $key . ': ');
        $max_key_width = max($max_key_width, $box[2] - $box[0]);
    }

    // Draw key-value pairs
    foreach ($data as $key => $value) {
        $key_x = $padding;
        imagettftext($canvas, $font_size, 0, $key_x, $current_y, $color, $font_regular, $key . ':');

        $value_x = $padding + $max_key_width + 10 * $scale;
        $lines = wrapText($value, $text_width - $max_key_width - 10 * $scale, $font_regular, $font_size, $canvas, $color, $value_x, $current_y, $line_height, $scale);

        $current_y += count($lines) * $line_height;
    }

    return $current_y + 20 * $scale;
}

function wrapText($text, $max_width, $font, $font_size, $canvas, $color, $x, $y, $line_height, $scale)
{
    $words = explode(' ', $text);
    $lines = [];
    $current_line = '';
    $current_font_size = $font_size;

    foreach ($words as $word) {
        $test_line = $current_line ? $current_line . ' ' . $word : $word;
        $box = imagettfbbox($current_font_size, 0, $font, $test_line);
        $text_width = $box[2] - $box[0];

        // Reduce font size if needed
        while ($text_width > $max_width && $current_font_size > 6 * $scale) {
            $current_font_size -= 0.5;
            $box = imagettfbbox($current_font_size, 0, $font, $test_line);
            $text_width = $box[2] - $box[0];
        }

        if ($text_width <= $max_width) {
            $current_line = $test_line;
        } else {
            if ($current_line) $lines[] = $current_line;
            $current_line = $word;
        }
    }
    if ($current_line) $lines[] = $current_line;

    // Draw lines with adjusted font size
    foreach ($lines as $line) {
        imagettftext($canvas, $current_font_size, 0, $x, $y, $color, $font, $line);
        $y += $line_height;
    }

    return $lines;
}

function calculateSectionHeight($data, $font_size, $font, $content_width)
{
    $height = 30;
    $max_key_width = 0;

    foreach ($data as $key => $value) {
        $box = imagettfbbox($font_size, 0, $font, $key . ':');
        $max_key_width = max($max_key_width, $box[2] - $box[0]);
    }

    $text_width = $content_width - $max_key_width - 10;
    foreach ($data as $value) {
        $lines = wrapTextForHeightCalc($value, $text_width, $font, $font_size);
        $height += count($lines) * 15;
    }
    return $height;
}

function wrapTextForHeightCalc($text, $max_width, $font, $font_size)
{
    $words = explode(' ', $text);
    $lines = [];
    $current_line = '';

    foreach ($words as $word) {
        $test_line = $current_line ? $current_line . ' ' . $word : $word;
        $box = imagettfbbox($font_size, 0, $font, $test_line);
        if (($box[2] - $box[0]) > $max_width) {
            if ($current_line) $lines[] = $current_line;
            $current_line = $word;
        } else {
            $current_line = $test_line;
        }
    }
    if ($current_line) $lines[] = $current_line;
    return $lines;
}

function loadImage($path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        return imagecreatefromjpeg($path);
    } elseif ($ext === 'png') {
        return imagecreatefrompng($path);
    }
    return false;
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
            <div class="mb-3">
                <label>Full Name:</label>
                <input type="text" name="biodata[Full Name]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Date of Birth:</label>
                <input type="text" name="biodata[Date of Birth]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Place of Birth:</label>
                <input type="text" name="biodata[Place of Birth]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Religion:</label>
                <input type="text" name="biodata[Religion]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Caste:</label>
                <input type="text" name="biodata[Caste]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Kuldivat:</label>
                <input type="text" name="biodata[Kuldivat]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Zodiac Sign:</label>
                <input type="text" name="biodata[Zodiac Sign]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Constellation:</label>
                <input type="text" name="biodata[Constellation]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Clan:</label>
                <input type="text" name="biodata[Clan]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Pulse:</label>
                <input type="text" name="biodata[Pulse]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Manglik:</label>
                <input type="text" name="biodata[Manglik]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Tribe:</label>
                <input type="text" name="biodata[Tribe]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Height:</label>
                <input type="text" name="biodata[Height]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Character:</label>
                <input type="text" name="biodata[Character]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Blood Type:</label>
                <input type="text" name="biodata[Blood Type]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Job/Business:</label>
                <input type="text" name="biodata[Job/Business]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Education:</label>
                <input type="text" name="biodata[Education]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Salary/Income:</label>
                <input type="text" name="biodata[Salary/Income]" class="form-control" required>
            </div>

            <h3>Family Details</h3>
            <div class="mb-3">
                <label>Father's Name:</label>
                <input type="text" name="family_details[Father's Name]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Father's Profession:</label>
                <input type="text" name="family_details[Father's Profession]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Mother's Name:</label>
                <input type="text" name="family_details[Mother's Name]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Mother's Profession:</label>
                <input type="text" name="family_details[Mother's Profession]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Sister:</label>
                <input type="text" name="family_details[Sister]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Brother:</label>
                <input type="text" name="family_details[Brother]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Uncle:</label>
                <input type="text" name="family_details[Uncle]" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Relationship:</label>
                <input type="text" name="family_details[Relationship]" class="form-control" required>
            </div>

            <h3>Contact Details (optional)</h3>
            <div class="mb-3">
                <label>Address:</label>
                <input type="text" name="contact_details[Address]" class="form-control">
            </div>
            <div class="mb-3">
                <label>Mobile Number:</label>
                <input type="text" name="contact_details[Mobile Number]" class="form-control">
            </div>

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