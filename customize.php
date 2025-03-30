<?php
session_start();
include 'config/db.php';

// Check if editing an existing customized biodata or starting from a template
$customized_id = isset($_GET['customized_id']) ? $_GET['customized_id'] : null;
$template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;

if (!$customized_id && !$template_id) {
    die("No template or customized biodata ID provided.");
}

if ($customized_id) {
    // Load from customized_biodatas
    $stmt = $pdo->prepare("SELECT * FROM customized_biodatas WHERE id = ?");
    $stmt->execute([$customized_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) die("Customized biodata not found.");
    $template_id = $data['template_id'];
} else {
    // Load from templates
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) die("Template not found.");
}

$biodata = json_decode($data['biodata'], true);
$family_details = json_decode($data['family_details'], true);
$contact_details = $data['contact_details'] ? json_decode($data['contact_details'], true) : [];
$god_names = $data['god_name'] ? explode('|', $data['god_name']) : [''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_identifier = $_SERVER['REMOTE_ADDR'] . '-' . session_id();
    $god_image = isset($_FILES['god_image']) && $_FILES['god_image']['name'] ? 'gods/' . basename($_FILES['god_image']['name']) : $data['god_image'];
    if ($god_image && $_FILES['god_image']['name']) {
        move_uploaded_file($_FILES['god_image']['tmp_name'], 'assets/images/' . $god_image);
    }
    $god_name = implode('|', array_filter($_POST['god_names']));
    $photo = isset($_FILES['photo']) && $_FILES['photo']['name'] ? 'persons/' . basename($_FILES['photo']['name']) : $data['photo'];
    if ($photo && $_FILES['photo']['name']) {
        move_uploaded_file($_FILES['photo']['tmp_name'], 'assets/images/' . $photo);
    }

    $biodata_post = [];
    foreach ($_POST['biodata_keys'] as $i => $key) {
        if ($key && $_POST['biodata_values'][$i]) {
            $biodata_post[$key] = $_POST['biodata_values'][$i];
        }
    }
    $family_post = [];
    foreach ($_POST['family_keys'] as $i => $key) {
        if ($key && $_POST['family_values'][$i]) {
            $family_post[$key] = $_POST['family_values'][$i];
        }
    }
    $contact_post = [];
    foreach ($_POST['contact_keys'] as $i => $key) {
        if ($key && $_POST['contact_values'][$i]) {
            $contact_post[$key] = $_POST['contact_values'][$i];
        }
    }

    $preview_image = generatePreviewImage(
        $god_image,
        $god_name,
        json_encode($biodata_post),
        json_encode($family_post),
        json_encode($contact_post),
        $data['background_image'],
        $photo
    );

    $stmt = $pdo->prepare(
        "INSERT INTO customized_biodatas (template_id, user_identifier, god_image, god_name, biodata, family_details, contact_details, background_image, photo, preview_image) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $template_id,
        $user_identifier,
        $god_image,
        $god_name,
        json_encode($biodata_post),
        json_encode($family_post),
        json_encode($contact_post),
        $data['background_image'],
        $photo,
        $preview_image
    ]);
    $customized_id = $pdo->lastInsertId();

    header("Location: preview.php?id=$customized_id");
    exit;
}

// Image generation function (moved here for completeness)
function generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo)
{
    $width = 794;
    $height = 1123;
    $padding_left_right = 90;
    $padding_top_bottom = 30;
    $reserved_top_area = 150;

    $canvas = imagecreatetruecolor($width, $height);
    $bg = loadImage('assets/images/' . $background_image);
    if (!$bg) die("Error loading background image");
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, $width, $height, imagesx($bg), imagesy($bg));

    $brightness = calculateBrightness($bg);
    $text_color = ($brightness < 128) ? imagecolorallocate($canvas, 255, 255, 255) : imagecolorallocate($canvas, 0, 0, 0);
    imagedestroy($bg);

    $current_y = $padding_top_bottom;
    if ($god_image) {
        $god_img = loadImage('assets/images/' . $god_image);
        if ($god_img) {
            $max_width = 100;
            $max_height = 100;
            $orig_width = imagesx($god_img);
            $orig_height = imagesy($god_img);
            $ratio = min($max_width / $orig_width, $max_height / $orig_height);
            $new_width = (int)($orig_width * $ratio);
            $new_height = (int)($orig_height * $ratio);
            $x = (int)(($width - $new_width) / 2);
            $y = $padding_top_bottom + (int)(($reserved_top_area - $new_height) / 2);
            imagecopyresampled($canvas, $god_img, $x, $y, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            imagedestroy($god_img);
        }
    }

    $current_y = $padding_top_bottom + $reserved_top_area + 20;
    $max_content_height = $height - $padding_top_bottom - $reserved_top_area - $padding_top_bottom;

    $font_regular = 'C:/Windows/Fonts/times.ttf';
    $font_bold = 'C:/Windows/Fonts/timesbd.ttf';

    $base_font_size = 10;
    $god_name_size = 16;
    $title_size = 14;

    $content_width = $width - 2 * $padding_left_right;
    $elements_height = $god_name_size + 20;
    $biodata_data = json_decode($biodata, true);
    $elements_height += calculateSectionHeight($biodata_data, $base_font_size, $font_regular, $content_width);
    $family_data = json_decode($family_details, true);
    $elements_height += calculateSectionHeight($family_data, $base_font_size, $font_regular, $content_width);
    $contact_data = json_decode($contact_details, true);
    $contact_height = $contact_data ? calculateSectionHeight($contact_data, $base_font_size, $font_regular, $content_width) : 0;
    $elements_height += $contact_height;

    $scale = $elements_height > $max_content_height ? $max_content_height / $elements_height : 1;
    $base_font_size *= $scale;
    $god_name_size *= $scale;
    $title_size *= $scale;
    $line_height = 15 * $scale;
    $text_section_width = $content_width * 0.7;

    $current_y = $padding_top_bottom + $reserved_top_area + 20;
    $god_names = $god_name ? explode('|', $god_name) : [''];
    foreach ($god_names as $name) {
        if ($name) {
            $box = imagettfbbox($god_name_size, 0, $font_bold, $name);
            $x = (int)(($width - ($box[2] - $box[0])) / 2);
            imagettftext($canvas, $god_name_size, 0, $x, $current_y, $text_color, $font_bold, $name);
            $current_y += $god_name_size + 5 * $scale;
        }
    }
    $current_y += 15 * $scale;

    $current_y = drawSection($canvas, 'BIODATA', $biodata_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    $current_y = drawSection($canvas, 'Family Details', $family_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    if ($contact_data) {
        $current_y = drawSection($canvas, 'Contact Details', $contact_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    }

    if ($photo) {
        $person_img = loadImage('assets/images/' . $photo);
        if ($person_img) {
            $target_width = 130 * $scale;
            $target_height = 173 * $scale;
            $photo_x = $width - $padding_left_right - $target_width;
            $photo_y = $padding_top_bottom + $reserved_top_area + 20;
            imagecopyresampled($canvas, $person_img, $photo_x, $photo_y, 0, 0, $target_width, $target_height, imagesx($person_img), imagesy($person_img));
            imagedestroy($person_img);
        }
    }

    $preview_path = 'previews/customized_' . time() . '.png';
    imagepng($canvas, 'assets/images/' . $preview_path);
    imagedestroy($canvas);
    return $preview_path;
}

function drawSection($canvas, $title, $data, $current_y, $title_size, $font_size, $font_bold, $font_regular, $padding, $text_width, $line_height, $color, $scale)
{
    $box = imagettfbbox($title_size, 0, $font_bold, $title);
    $x = (int)((794 - ($box[2] - $box[0])) / 2);
    imagettftext($canvas, $title_size, 0, $x, $current_y, $color, $font_bold, $title);
    $current_y += $title_size + 15 * $scale;

    $max_key_width = 0;
    foreach ($data as $key => $value) {
        $box = imagettfbbox($font_size, 0, $font_regular, $key . ': ');
        $max_key_width = max($max_key_width, $box[2] - $box[0]);
    }

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
    if ($ext === 'jpg' || $ext === 'jpeg') return imagecreatefromjpeg($path);
    if ($ext === 'png') return imagecreatefrompng($path);
    return false;
}

function calculateBrightness($image)
{
    $width = imagesx($image);
    $height = imagesy($image);
    $total_brightness = 0;
    for ($x = 0; $x < $width; $x += 10) { // Sample every 10 pixels for performance
        for ($y = 0; $y < $height; $y += 10) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
            $total_brightness += $brightness;
        }
    }
    $pixel_count = ($width / 10) * ($height / 10);
    return $total_brightness / $pixel_count;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customize Biodata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            --gradient-accent: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #333;
            --text-light: #fff;
        }

        .navbar-custom {
            background: var(--gradient-primary);
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: var(--text-light);
            font-weight: 500;
        }

        .navbar-custom .nav-link:hover {
            color: #d0e8ff;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
        }

        .photo-upload {
            position: fixed;
            top: 100px;
            right: 20px;
            width: 30%;
            z-index: 1000;
        }

        .field-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .field-row .move-btns {
            flex: 0 0 40px;
        }

        .field-row .delete-btn {
            flex: 0 0 40px;
        }

        .field-row .key-input {
            flex: 1;
            margin-right: 10px;
        }

        .field-row .value-input {
            flex: 1;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Biodata Maker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5 pt-5">
        <div class="row">
            <div class="col-md-8">
                <h1 class="mb-4">Customize Your Biodata</h1>
                <form method="post" enctype="multipart/form-data" id="customizeForm">
                    <!-- Language Selection -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="lang-english">English</button>
                        <button type="button" class="btn btn-primary" id="lang-marathi">मराठी</button>
                    </div>

                    <!-- God Image -->
                    <div class="mb-3 text-center">
                        <?php if ($data['god_image']): ?>
                            <img src="assets/images/<?php echo $data['god_image']; ?>" id="godImagePreview" class="img-fluid" style="max-width: 100px;" alt="God Image">
                        <?php endif; ?>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#changeGodImageModal">Change God Photo</button>
                            <button type="button" class="btn btn-sm btn-danger" id="removeGodImage">Remove God Photo</button>
                        </div>
                        <input type="file" name="god_image" id="godImageInput" style="display: none;">
                    </div>

                    <!-- God Names -->
                    <div class="mb-3">
                        <h3>God Name(s)</h3>
                        <div id="godNames">
                            <?php foreach ($god_names as $i => $name): ?>
                                <div class="field-row god-name-row">
                                    <div class="move-btns">
                                        <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                                        <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                                    </div>
                                    <div class="delete-btn">
                                        <button type="button" class="btn btn-sm btn-danger delete-god-name"><i class="fas fa-trash"></i></button>
                                    </div>
                                    <input type="text" name="god_names[]" class="form-control" value="<?php echo $name; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" id="addGodName">Add God Name</button>
                    </div>

                    <!-- Biodata Title -->
                    <div class="mb-3">
                        <label>Title:</label>
                        <div class="input-group">
                            <select class="form-control" id="biodata_title" name="biodata_title">
                                <option selected>BIODATA</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomHeadingModal">Add Custom Heading</button>
                        </div>
                    </div>

                    <!-- Biodata Fields -->
                    <h3>Biodata</h3>
                    <div id="biodataFields">
                        <?php foreach ($biodata as $key => $value): ?>
                            <div class="field-row">
                                <div class="move-btns">
                                    <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                                </div>
                                <div class="delete-btn">
                                    <button type="button" class="btn btn-sm btn-danger delete-field"><i class="fas fa-trash"></i></button>
                                </div>
                                <input type="text" name="biodata_keys[]" class="form-control key-input" value="<?php echo $key; ?>">
                                <input type="text" name="biodata_values[]" class="form-control value-input" value="<?php echo $value; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-success mt-2" id="addBiodataField">Add Field</button>

                    <!-- Family Details -->
                    <h3 class="mt-4">Family Details</h3>
                    <div id="familyFields">
                        <?php foreach ($family_details as $key => $value): ?>
                            <div class="field-row">
                                <div class="move-btns">
                                    <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                                </div>
                                <div class="delete-btn">
                                    <button type="button" class="btn btn-sm btn-danger delete-field"><i class="fas fa-trash"></i></button>
                                </div>
                                <input type="text" name="family_keys[]" class="form-control key-input" value="<?php echo $key; ?>">
                                <input type="text" name="family_values[]" class="form-control value-input" value="<?php echo $value; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-success mt-2" id="addFamilyField">Add Field</button>

                    <!-- Contact Details -->
                    <h3 class="mt-4">Contact Details</h3>
                    <div id="contactFields">
                        <?php foreach ($contact_details as $key => $value): ?>
                            <div class="field-row">
                                <div class="move-btns">
                                    <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                                </div>
                                <div class="delete-btn">
                                    <button type="button" class="btn btn-sm btn-danger delete-field"><i class="fas fa-trash"></i></button>
                                </div>
                                <input type="text" name="contact_keys[]" class="form-control key-input" value="<?php echo $key; ?>">
                                <input type="text" name="contact_values[]" class="form-control value-input" value="<?php echo $value; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-success mt-2" id="addContactField">Add Field</button>

                    <!-- Reset and Submit -->
                    <div class="mt-4">
                        <button type="button" class="btn btn-warning" id="resetForm">Reset Form</button>
                        <button type="submit" class="btn btn-primary" id="createBiodata">Create Biodata</button>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="photo-upload card p-3">
                    <h5>Upload Your Photo</h5>
                    <div id="photoPlaceholder" class="text-center" style="border: 2px dashed #ccc; padding: 20px; cursor: pointer;">
                        <?php if ($data['photo']): ?>
                            <img src="assets/images/<?php echo $data['photo']; ?>" id="photoPreview" class="img-fluid" style="max-width: 100%;">
                        <?php else: ?>
                            <p>Click here to add your photo</p>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="photo" id="photoInput" style="display: none;">
                </div>
            </div>
        </div>
    </div>

    <!-- Change God Image Modal -->
    <div class="modal fade" id="changeGodImageModal" tabindex="-1" aria-labelledby="changeGodImageLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeGodImageLabel">Select God Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php
                        $god_images = ['ganesh.png', 'krishna.png', 'shiva.png']; // Add more as needed
                        foreach ($god_images as $img):
                        ?>
                            <div class="col-4">
                                <img src="assets/images/gods/<?php echo $img; ?>" class="img-fluid god-image-option" style="cursor: pointer;" alt="<?php echo basename($img, '.png'); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Custom Heading Modal -->
    <div class="modal fade" id="addCustomHeadingModal" tabindex="-1" aria-labelledby="addCustomHeadingLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomHeadingLabel">Add Custom Heading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" id="customHeadingInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCustomHeading">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Language Switching (Placeholder)
        let currentLang = 'English';
        const translations = {
            English: {
                'Biodata': 'Biodata',
                'Family Details': 'Family Details',
                'Contact Details': 'Contact Details',
                'Add Field': 'Add Field',
                'Reset Form': 'Reset Form',
                'Create Biodata': 'Create Biodata'
            },
            Marathi: {
                'Biodata': 'बायोडाटा',
                'Family Details': 'कौटुंबिक माहिती',
                'Contact Details': 'संपर्क माहिती',
                'Add Field': 'फील्ड जोडा',
                'Reset Form': 'फॉर्म रीसेट करा',
                'Create Biodata': 'बायोडाटा तयार करा'
            }
        };

        function updateLanguage(lang) {
            currentLang = lang;
            document.querySelectorAll('h3').forEach(h => {
                h.textContent = translations[lang][h.textContent] || h.textContent;
            });
            document.querySelectorAll('.btn-success').forEach(btn => {
                btn.textContent = translations[lang]['Add Field'];
            });
            document.getElementById('resetForm').textContent = translations[lang]['Reset Form'];
            document.getElementById('createBiodata').textContent = translations[lang]['Create Biodata'];
        }

        document.getElementById('lang-english').addEventListener('click', () => updateLanguage('English'));
        document.getElementById('lang-marathi').addEventListener('click', () => updateLanguage('Marathi'));

        // God Image Handling
        document.querySelectorAll('.god-image-option').forEach(img => {
            img.addEventListener('click', function() {
                document.getElementById('godImagePreview').src = this.src;
                document.getElementById('godImagePreview').style.display = 'block';
                document.getElementById('godImageInput').dataset.path = this.src.split('/').pop();
                bootstrap.Modal.getInstance(document.getElementById('changeGodImageModal')).hide();
            });
        });

        document.getElementById('removeGodImage').addEventListener('click', () => {
            document.getElementById('godImagePreview').style.display = 'none';
            document.getElementById('godImageInput').value = '';
        });

        // God Names Handling
        document.getElementById('addGodName').addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'field-row god-name-row';
            row.innerHTML = `
                <div class="move-btns">
                    <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                </div>
                <div class="delete-btn">
                    <button type="button" class="btn btn-sm btn-danger delete-god-name"><i class="fas fa-trash"></i></button>
                </div>
                <input type="text" name="god_names[]" class="form-control">
            `;
            document.getElementById('godNames').appendChild(row);
        });

        document.addEventListener('click', e => {
            if (e.target.closest('.delete-god-name')) {
                e.target.closest('.god-name-row').remove();
            }
        });

        // Field Handling
        function addField(section) {
            const fields = document.getElementById(section + 'Fields');
            const row = document.createElement('div');
            row.className = 'field-row';
            row.innerHTML = `
                <div class="move-btns">
                    <button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button>
                </div>
                <div class="delete-btn">
                    <button type="button" class="btn btn-sm btn-danger delete-field"><i class="fas fa-trash"></i></button>
                </div>
                <input type="text" name="${section}_keys[]" class="form-control key-input" placeholder="Key">
                <input type="text" name="${section}_values[]" class="form-control value-input" placeholder="Value">
            `;
            fields.appendChild(row);
        }

        document.getElementById('addBiodataField').addEventListener('click', () => addField('biodata'));
        document.getElementById('addFamilyField').addEventListener('click', () => addField('family'));
        document.getElementById('addContactField').addEventListener('click', () => addField('contact'));

        document.addEventListener('click', e => {
            if (e.target.closest('.delete-field')) {
                e.target.closest('.field-row').remove();
            }
            if (e.target.closest('.move-up')) {
                const row = e.target.closest('.field-row');
                const prev = row.previousElementSibling;
                if (prev) row.parentNode.insertBefore(row, prev);
            }
            if (e.target.closest('.move-down')) {
                const row = e.target.closest('.field-row');
                const next = row.nextElementSibling;
                if (next) row.parentNode.insertBefore(next, row);
            }
        });

        // Custom Heading
        document.getElementById('saveCustomHeading').addEventListener('click', () => {
            const heading = document.getElementById('customHeadingInput').value;
            if (heading) {
                const select = document.getElementById('biodata_title');
                const option = document.createElement('option');
                option.value = heading;
                option.text = heading;
                select.add(option);
                select.value = heading;
                bootstrap.Modal.getInstance(document.getElementById('addCustomHeadingModal')).hide();
            }
        });

        // Photo Upload
        document.getElementById('photoPlaceholder').addEventListener('click', () => {
            document.getElementById('photoInput').click();
        });
        document.getElementById('photoInput').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.id = 'photoPreview';
                    img.className = 'img-fluid';
                    img.style.maxWidth = '100%';
                    const placeholder = document.getElementById('photoPlaceholder');
                    placeholder.innerHTML = '';
                    placeholder.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });

        // Reset Form
        document.getElementById('resetForm').addEventListener('click', () => {
            document.querySelectorAll('.value-input').forEach(input => {
                input.value = input.dataset.default || '';
            });
            document.getElementById('godNames').innerHTML = '<?php echo $data['god_name'] ? '<div class="field-row god-name-row"><div class="move-btns"><button type="button" class="btn btn-sm btn-secondary move-up"><i class="fas fa-arrow-up"></i></button><button type="button" class="btn btn-sm btn-secondary move-down"><i class="fas fa-arrow-down"></i></button></div><div class="delete-btn"><button type="button" class="btn btn-sm btn-danger delete-god-name"><i class="fas fa-trash"></i></button></div><input type="text" name="god_names[]" class="form-control" value="' . $data['god_name'] . '"></div>' : ''; ?>';
            document.getElementById('photoPreview')?.remove();
            document.getElementById('photoPlaceholder').innerHTML = '<p>Click here to add your photo</p>';
            document.getElementById('photoInput').value = '';
            <?php if ($data['god_image']): ?>
                document.getElementById('godImagePreview').src = 'assets/images/<?php echo $data['god_image']; ?>';
                document.getElementById('godImagePreview').style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>

</html>