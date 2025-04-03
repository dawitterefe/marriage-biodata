<?php
session_start();
include 'config/db.php';

// Suppress warnings temporarily to avoid header issues
ob_start();

// Check if editing an existing customized biodata or starting from a template
$customized_id = isset($_GET['customized_id']) ? $_GET['customized_id'] : null;
$template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;

if (!$customized_id && !$template_id) {
    die("No template or customized biodata ID provided.");
}

if ($customized_id) {
    $stmt = $pdo->prepare("SELECT * FROM customized_biodatas WHERE id = ?");
    $stmt->execute([$customized_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) die("Customized biodata not found.");
    $template_id = $data['template_id'];
} else {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) die("Template not found.");
}

$biodata = json_decode($data['biodata'], true);
$family_details = json_decode($data['family_details'], true);
$contact_details = $data['contact_details'] ? json_decode($data['contact_details'], true) : [];
$god_names = $data['god_name'] ? explode('|', $data['god_name']) : [''];

// Marathi translations for common fields
$mr_translations = [
    'Full Name' => 'नाव',
    'Date of Birth' => 'जन्मतारीख',
    'Time of Birth' => 'जन्मवेळ',
    'Place of Birth' => 'जन्मस्थळ',
    'Height' => 'उंची',
    'Weight' => 'वजन',
    'Kuldivat' => 'कुलदैवत',
    'Education' => 'शिक्षण',
    'Relationship' => 'नाते',
    'Occupation' => 'व्यवसाय',
    "Salary/Income" => 'पगार/उत्पन्न',
    "Mobile Number" => 'मोबाईल नंबर',
    "Job/Business" => 'नोकरी/व्यवसाय',
    'Income' => 'उत्पन्न',
    'Father' => 'वडील',
    'Uncle' => 'काका',
    'Brother' => 'भाऊ',
    'Sister' => 'बहीण',
    'Address' => 'पत्ता',
    'Mobile' => 'मोबाईल',
    'Email' => 'ईमेल',
    'Religion' => 'धर्म',
    'Zodiac Sign' => 'राशी',
    'Constellation' => 'नक्षत्र',
    'Clan' => 'कुळ',
    'Caste' => 'जात',
    'Pulse' => 'नाडी',
    'Manglik' => 'मंगळिक',
    'Tribe' => 'जमात',
    'Character' => 'स्वभाव',
    'Blood Type' => 'रक्त गट',
    "Mother's Name" => 'आईचे नाव',
    "Father's Name" => 'वडिलांचे नाव',
    "Father's Profession" => 'वडिलांचा व्यवसाय',
    "Mother's Profession" => 'आईचा व्यवसाय',
];

// Dropdown options with translations
$dropdown_options = [
    'Religion' => [
        'en' => ['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist', 'Jain', 'Other'],
        'mr' => ['हिंदू', 'मुस्लिम', 'ख्रिश्चन', 'शीख', 'बौद्ध', 'जैन', 'इतर']
    ],
    'Zodiac Sign' => [
        'en' => ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'],
        'mr' => ['मेष', 'वृषभ', 'मिथुन', 'कर्क', 'सिंह', 'कन्या', 'तुला', 'वृश्चिक', 'धनु', 'मकर', 'कुंभ', 'मीन']
    ],
    'Constellation' => [
        'en' => ['Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira', 'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra', 'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula', 'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta', 'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati'],
        'mr' => ['अश्विनी', 'भरणी', 'कृत्तिका', 'रोहिणी', 'मृगशिरा', 'आर्द्रा', 'पुनर्वसु', 'पुष्य', 'आश्लेषा', 'मघा', 'पूर्व फाल्गुनी', 'उत्तर फाल्गुनी', 'हस्त', 'चित्रा', 'स्वाती', 'विशाखा', 'अनुराधा', 'ज्येष्ठा', 'मूळ', 'पूर्वाषाढा', 'उत्तराषाढा', 'श्रवण', 'धनिष्ठा', 'शतभिषा', 'पूर्व भाद्रपदा', 'उत्तर भाद्रपदा', 'रेवती']
    ],
    'Clan' => [
        'en' => ['Custom Clan 1', 'Custom Clan 2', 'Custom Clan 3'],
        'mr' => ['कुळ १', 'कुळ २', 'कुळ ३']
    ],
    'Pulse' => [
        'en' => ['Adi', 'Madhya', 'Antya'],
        'mr' => ['आदि', 'मध्य', 'अंत्य']
    ],
    'Manglik' => [
        'en' => ['Yes', 'No', 'Not Sure'],
        'mr' => ['होय', 'नाही', 'खात्री नाही']
    ],
    'Tribe' => [
        'en' => ['Custom Tribe 1', 'Custom Tribe 2', 'Custom Tribe 3'],
        'mr' => ['जमात १', 'जमात २', 'जमात ३']
    ],
    'Height' => [
        'en' => ['4\'0"', '4\'6"', '5\'0"', '5\'6"', '6\'0"', '6\'6"', '7\'0"'],
        'mr' => ['४\'०"', '४\'६"', '५\'०"', '५\'६"', '६\'०"', '६\'६"', '७\'०"']
    ],
    'Character' => [
        'en' => ['Calm', 'Energetic', 'Friendly', 'Reserved', 'Outgoing'],
        'mr' => ['शांत', 'उत्साही', 'मैत्रीपूर्ण', 'संयमित', 'बाहेर जाणारा']
    ],
    'Blood Type' => [
        'en' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
        'mr' => ['ए+', 'ए-', 'बी+', 'बी-', 'एबी+', 'एबी-', 'ओ+', 'ओ-']
    ]
];

// Image generation function
function generatePreviewImage($god_image, $god_name, $biodata, $family_details, $contact_details, $background_image, $photo, $language = 'en', $biodata_title = 'BIODATA', $family_title = 'Family Details', $contact_title = 'Contact Details')
{
    $width = 2480;  // 8.26in * 300 DPI
    $height = 3508; // 11.69in * 300 DPI

    $scale_factor = 3.125; // 2480/794 ≈ 3.125

    $padding_left_right = 90 * $scale_factor;
    $padding_top_bottom = 30 * $scale_factor;
    $reserved_top_area = 150 * $scale_factor;

    $canvas = imagecreatetruecolor($width, $height);
    imageantialias($canvas, true);
    imagesetthickness($canvas, 1);
    $bg_path = __DIR__ . '/assets/images/' . $background_image;
    if (!file_exists($bg_path)) die("Background image not found: $bg_path");
    $bg = loadImage($bg_path);
    if (!$bg) die("Error loading background image: $bg_path");
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, $width, $height, imagesx($bg), imagesy($bg));
    $brightness = calculateBrightness($bg);
    $text_color = ($brightness < 128) ? imagecolorallocate($canvas, 255, 255, 255) : imagecolorallocate($canvas, 0, 0, 0);
    imagedestroy($bg);

    $current_y = $padding_top_bottom;
    if ($god_image) {
        $god_image_path = __DIR__ . '/assets/images/' . $god_image;
        if (!file_exists($god_image_path)) die("God image not found: $god_image_path");
        $god_img = loadImage($god_image_path);
        if ($god_img) {
            $max_width = 312;
            $max_height = 312;

            $orig_width = imagesx($god_img);
            $orig_height = imagesy($god_img);
            $ratio = min($max_width / $orig_width, $max_height / $orig_height);
            $new_width = (int)($orig_width * $ratio);
            $new_height = (int)($orig_height * $ratio);

            $x = (int)(($width - $new_width) / 2);
            $y = $padding_top_bottom + (int)(($reserved_top_area - $new_height) / 2);

            imagecopyresampled($canvas, $god_img, $x, $y, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            imagedestroy($god_img);
        } else {
            die("Error loading god image: $god_image_path");
        }
    }
    $current_y = $padding_top_bottom + $reserved_top_area + 20;
    $max_content_height = $height - $padding_top_bottom - $reserved_top_area - $padding_top_bottom;

    $font_regular = ($language === 'mr') ? __DIR__ . '/assets/fonts/mangal.ttf' : __DIR__ . '/assets/fonts/times.ttf';
    $font_bold = ($language === 'mr') ? __DIR__ . '/assets/fonts/mangalb.ttf' : __DIR__ . '/assets/fonts/timesbd.ttf';
    if (!file_exists($font_regular) || !file_exists($font_bold)) {
        die("Font files missing: Ensure times.ttf, timesbd.ttf, mangal.ttf, and mangalb.ttf are in assets/fonts/");
    }

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
    $base_font_size = 10 * $scale_factor;
    $god_name_size = 16 * $scale_factor;
    $title_size = 14 * $scale_factor;
    $line_height = 15 * $scale_factor;

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

    $current_y = drawSection($canvas, $biodata_title, $biodata_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    $current_y = drawSection($canvas, $family_title, $family_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    if ($contact_data) {
        $current_y = drawSection($canvas, $contact_title, $contact_data, $current_y, $title_size, $base_font_size, $font_bold, $font_regular, $padding_left_right, $text_section_width, $line_height, $text_color, $scale);
    }

    if ($photo) {
        $photo_path = __DIR__ . '/assets/images/' . $photo;
        if (!file_exists($photo_path)) die("Person photo not found: $photo_path");
        $person_img = loadImage($photo_path);
        if ($person_img) {
            $target_width = 130 * $scale_factor;
            $target_height = 173 * $scale_factor;
            $photo_x = $width - $padding_left_right - $target_width;
            $photo_y = $padding_top_bottom + $reserved_top_area + 20;
            imagecopyresampled($canvas, $person_img, $photo_x, $photo_y, 0, 0, $target_width, $target_height, imagesx($person_img), imagesy($person_img));
            imagedestroy($person_img);
        } else {
            die("Error loading person photo: $photo_path");
        }
    }

    $preview_path = 'previews/customized_' . time() . '.png';
    $full_preview_path = __DIR__ . '/assets/images/' . $preview_path;
    imagepng($canvas, $full_preview_path);
    imagedestroy($canvas);
    return $preview_path;
}

function drawSection($canvas, $title, $data, $current_y, $title_size, $font_size, $font_bold, $font_regular, $padding, $text_width, $line_height, $color, $scale)
{
    $box = imagettfbbox($title_size, 0, $font_bold, $title);
    // Ensure $width is assigned a value
    $width = 794; // Replace 794 with the appropriate value for your context

    // Now use $width in the calculation
    $x = (int)(($width - ($box[2] - $box[0])) / 2); // Center title

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
    if (!file_exists($path)) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') return @imagecreatefromjpeg($path);
    if ($ext === 'png') return @imagecreatefrompng($path);
    return false;
}

function calculateBrightness($image)
{
    $width = imagesx($image);
    $height = imagesy($image);
    $total_brightness = 0;
    for ($x = 0; $x < $width; $x += 10) {
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_identifier = $_SERVER['REMOTE_ADDR'] . '-' . session_id();
    $language = isset($_POST['language']) ? $_POST['language'] : 'en';

    // Handle god image
    if (isset($_POST['remove_god_image']) && $_POST['remove_god_image'] == '1') {
        $god_image = null;
    } elseif (isset($_FILES['god_image']) && $_FILES['god_image']['error'] == UPLOAD_ERR_OK) {
        $god_image_name = 'gods/' . time() . '_' . basename($_FILES['god_image']['name']);
        $god_image_path = __DIR__ . '/assets/images/' . $god_image_name;
        if (!move_uploaded_file($_FILES['god_image']['tmp_name'], $god_image_path)) {
            die("Failed to move uploaded god image to: $god_image_path");
        }
        $god_image = $god_image_name;
    } elseif (isset($_POST['selected_god_image']) && !empty($_POST['selected_god_image'])) {
        $god_image = 'gods/' . basename($_POST['selected_god_image']);
    } else {
        $god_image = !empty($data['god_image']) ? $data['god_image'] : null;
    }

    $god_name = implode('|', array_filter($_POST['god_names']));

    // Handle person photo
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        $photo = null;
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photo_name = 'persons/' . time() . '_' . basename($_FILES['photo']['name']);
        $photo_path = __DIR__ . '/assets/images/' . $photo_name;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $photo = $photo_name;
        } else {
            error_log("Failed to move uploaded person photo to: $photo_path");
            $photo = !empty($data['photo']) ? $data['photo'] : null;
        }
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] != UPLOAD_ERR_NO_FILE) {
            error_log("Photo upload error: " . $_FILES['photo']['error']);
        }
        $photo = !empty($data['photo']) ? $data['photo'] : null;
    }

    // Handle biodata, family, and contact details
    $biodata_post = [];
    foreach ($_POST['biodata_keys'] as $i => $key) {
        if ($key && isset($_POST['biodata_values'][$i]) && $_POST['biodata_values'][$i]) {
            $biodata_post[$key] = $_POST['biodata_values'][$i];
        }
    }

    $family_post = [];
    foreach ($_POST['family_keys'] as $i => $key) {
        if ($key && isset($_POST['family_values'][$i]) && $_POST['family_values'][$i]) {
            $family_post[$key] = $_POST['family_values'][$i];
        }
    }

    $contact_post = [];
    foreach ($_POST['contact_keys'] as $i => $key) {
        if ($key && isset($_POST['contact_values'][$i]) && $_POST['contact_values'][$i]) {
            $contact_post[$key] = $_POST['contact_values'][$i];
        }
    }

    $biodata_title = isset($_POST['biodata_title']) ? $_POST['biodata_title'] : ($language == 'mr' ? 'बायोडाटा' : 'BIODATA');
    $family_title = isset($_POST['family_title']) ? $_POST['family_title'] : ($language == 'mr' ? 'कौटुंबिक माहिती' : 'Family Details');
    $contact_title = isset($_POST['contact_title']) ? $_POST['contact_title'] : ($language == 'mr' ? 'संपर्क माहिती' : 'Contact Details');

    $background_image = $data['background_image'];

    $preview_image = generatePreviewImage(
        $god_image,
        $god_name,
        json_encode($biodata_post),
        json_encode($family_post),
        json_encode($contact_post),
        $background_image,
        $photo,
        $language,
        $biodata_title,
        $family_title,
        $contact_title
    );

    // Insert or update the customized_biodatas table
    if ($customized_id) {
        $stmt = $pdo->prepare(
            "UPDATE customized_biodatas SET 
                god_image = ?, god_name = ?, biodata = ?, family_details = ?, contact_details = ?, 
                background_image = ?, photo = ?, preview_image = ? 
            WHERE id = ?"
        );
        $stmt->execute([
            $god_image,
            $god_name,
            json_encode($biodata_post),
            json_encode($family_post),
            json_encode($contact_post),
            $background_image,
            $photo,
            $preview_image,
            $customized_id
        ]);
    } else {
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
            $background_image,
            $photo,
            $preview_image
        ]);
        $customized_id = $pdo->lastInsertId();
    }

    ob_end_clean();
    header("Location: preview.php?id=$customized_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-translate="title">Customize Biodata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            --gradient-accent: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #333;
            --text-light: #fff;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: var(--gradient-primary);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: var(--text-light);
            font-weight: 500;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.4);
        }

        .photo-upload {
            position: sticky;
            top: 100px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .field-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 12px 15px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.2s ease;
        }

        .field-row:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .field-actions {
            margin-left: auto;
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-icon i {
            font-size: 0.8rem;
        }

        .form-control,
        .form-select {
            border-radius: var(--border-radius);
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #8f94fb;
            box-shadow: 0 0 0 0.25rem rgba(142, 148, 251, 0.25);
        }

        .lang-btn.active {
            background: var(--gradient-accent);
            border-color: transparent;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: #4e54c8;
            font-weight: 600;
        }

        #photoPlaceholder,
        #godImagePlaceholder {
            border: 2px dashed #ddd;
            border-radius: var(--border-radius);
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        #photoPlaceholder:hover,
        #godImagePlaceholder:hover {
            border-color: #8f94fb;
            background: rgba(142, 148, 251, 0.05);
        }

        .god-image-option {
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .god-image-option:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }


        /* Add new styles for cropping modal */
        .cropper-modal {
            background: rgba(0, 0, 0, 0.8);
        }

        .cropper-view-box {
            outline: 2px solid #4e54c8;
            outline-color: rgba(78, 84, 200, 0.75);
        }

        .cropper-dashed {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .zoom-slider {
            width: 100%;
            margin: 15px 0;
            -webkit-appearance: none;
            height: 6px;
            background: #ddd;
            border-radius: 3px;
        }

        .zoom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: #4e54c8;
            border-radius: 50%;
            cursor: pointer;
        }

        .cropper-container {
            max-width: 100%;
            margin: 0 auto;
        }

        #cropperWrapper {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .cropper-controls {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body class="bg-light">
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
                <h1 class="mb-4" data-translate="title">Customize Your Biodata</h1>
                <form method="post" enctype="multipart/form-data" id="customizeForm">
                    <input type="hidden" name="language" id="currentLanguage" value="en">
                    <input type="hidden" name="selected_god_image" id="selectedGodImage" value="<?php echo htmlspecialchars($data['god_image'] ?? ''); ?>">
                    <input type="hidden" name="remove_god_image" id="removeGodImageInput" value="0">
                    <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">

                    <div class="mb-4">
                        <label class="mb-2 d-block" data-translate="select_language">Select Language:</label>
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary lang-btn active" id="lang-english">English</button>
                            <button type="button" class="btn btn-primary lang-btn" id="lang-marathi">मराठी</button>
                        </div>
                    </div>

                    <div class="mb-4 text-center">
                        <?php if (!empty($data['god_image'])): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($data['god_image']); ?>" id="godImagePreview" class="img-fluid rounded-circle shadow" style="max-width: 120px; height: 120px; object-fit: cover;" alt="God Image">
                        <?php else: ?>
                            <div id="godImagePlaceholder" data-translate="click_to_add_god_image">Click to add god image</div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#changeGodImageModal" data-translate="change_god_photo">Change God Photo</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeGodImage" data-translate="remove_god_photo">Remove God Photo</button>
                        </div>
                        <input type="file" name="god_image" id="godImageInput" style="display: none;" accept="image/*">
                    </div>

                    <div class="mb-4">
                        <h3 data-translate="god_names">God Name(s)</h3>
                        <div id="godNames">
                            <?php foreach ($god_names as $i => $name): ?>
                                <div class="field-row god-name-row">
                                    <input type="text" name="god_names[]" class="form-control" value="<?php echo htmlspecialchars($name); ?>">
                                    <div class="field-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-god-name">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" id="addGodName" data-translate="add_god_name">
                            <i class="fas fa-plus me-1"></i> Add God Name
                        </button>
                    </div>

                    <div class="mb-4">
                        <label data-translate="biodata_title">Title:</label>
                        <div class="input-group">
                            <select class="form-control" id="biodata_title" name="biodata_title">
                                <option selected>BIODATA</option>
                            </select>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomHeadingModal" data-section="biodata" data-translate="add_custom_heading">
                                Add Custom Heading
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 data-translate="biodata_section">Biodata</h3>
                        <div id="biodataFields">
                            <?php foreach ($biodata as $key => $value): ?>
                                <div class="field-row">
                                    <?php if (array_key_exists($key, $dropdown_options)): ?>
                                        <input type="text" name="biodata_keys[]" class="form-control key-input"
                                            value="<?php echo htmlspecialchars($key); ?>"
                                            data-lang-en="<?php echo htmlspecialchars($key); ?>"
                                            data-lang-mr="<?php echo htmlspecialchars($mr_translations[$key] ?? $key); ?>">
                                        <select class="form-select value-input" data-key="<?php echo htmlspecialchars($key); ?>">
                                            <?php foreach ($dropdown_options[$key]['en'] as $index => $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>"
                                                    data-lang-en="<?php echo htmlspecialchars($option); ?>"
                                                    data-lang-mr="<?php echo htmlspecialchars($dropdown_options[$key]['mr'][$index]); ?>"
                                                    <?php echo $value === $option ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="biodata_values[]" class="hidden-value"
                                            value="<?php echo htmlspecialchars($value); ?>">
                                    <?php else: ?>
                                        <input type="text" name="biodata_keys[]" class="form-control key-input"
                                            value="<?php echo htmlspecialchars($key); ?>"
                                            data-lang-en="<?php echo htmlspecialchars($key); ?>"
                                            data-lang-mr="<?php echo htmlspecialchars($mr_translations[$key] ?? $key); ?>">
                                        <input type="text" name="biodata_values[]" class="form-control value-input"
                                            value="<?php echo htmlspecialchars($value); ?>">
                                    <?php endif; ?>
                                    <div class="field-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-field">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" id="addBiodataField" data-translate="add_field">
                            <i class="fas fa-plus me-1"></i> Add Field
                        </button>
                    </div>

                    <div class="mb-4">
                        <label data-translate="family_details">Title:</label>
                        <div class="input-group">
                            <select class="form-control" id="family_title" name="family_title">
                                <option selected>Family Details</option>
                            </select>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomHeadingModal" data-section="family" data-translate="add_custom_heading">
                                Add Custom Heading
                            </button>
                        </div>
                        <h3 data-translate="family_details">Family Details</h3>
                        <div id="familyFields">
                            <?php foreach ($family_details as $key => $value): ?>
                                <div class="field-row">
                                    <input type="text" name="family_keys[]" class="form-control key-input"
                                        value="<?php echo htmlspecialchars($key); ?>"
                                        data-lang-en="<?php echo htmlspecialchars($key); ?>"
                                        data-lang-mr="<?php echo htmlspecialchars($mr_translations[$key] ?? $key); ?>">
                                    <input type="text" name="family_values[]" class="form-control value-input"
                                        value="<?php echo htmlspecialchars($value); ?>">
                                    <div class="field-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-field">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" id="addFamilyField" data-translate="add_field">
                            <i class="fas fa-plus me-1"></i> Add Field
                        </button>
                    </div>

                    <div class="mb-4">
                        <label data-translate="contact_details">Title:</label>
                        <div class="input-group">
                            <select class="form-control" id="contact_title" name="contact_title">
                                <option selected>Contact Details</option>
                            </select>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomHeadingModal" data-section="contact" data-translate="add_custom_heading">
                                Add Custom Heading
                            </button>
                        </div>
                        <h3 data-translate="contact_details">Contact Details</h3>
                        <div id="contactFields">
                            <?php foreach ($contact_details as $key => $value): ?>
                                <div class="field-row">
                                    <input type="text" name="contact_keys[]" class="form-control key-input"
                                        value="<?php echo htmlspecialchars($key); ?>"
                                        data-lang-en="<?php echo htmlspecialchars($key); ?>"
                                        data-lang-mr="<?php echo htmlspecialchars($mr_translations[$key] ?? $key); ?>">
                                    <input type="text" name="contact_values[]" class="form-control value-input"
                                        value="<?php echo htmlspecialchars($value); ?>">
                                    <div class="field-actions">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-field">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success mt-2" id="addContactField" data-translate="add_field">
                            <i class="fas fa-plus me-1"></i> Add Field
                        </button>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <button type="button" class="btn btn-warning" id="resetForm" data-translate="reset_form">
                            <i class="fas fa-undo me-1"></i> Reset Form
                        </button>
                        <button type="submit" class="btn btn-primary" id="createBiodata" data-translate="create_biodata">
                            <i class="fas fa-save me-1"></i> Create Biodata
                        </button>
                    </div>

            </div>
            <div class="col-md-4">
                <div class="photo-upload card">
                    <h5 data-translate="upload_photo">Upload Your Photo</h5>
                    <div id="photoPlaceholder" class="text-center">
                        <?php if (!empty($data['photo'])): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($data['photo']); ?>" id="photoPreview" class="img-fluid rounded shadow" style="max-width: 100%;">
                        <?php else: ?>
                            <p data-translate="click_to_add_photo">Click here to add your photo</p>
                            <i class="fas fa-user-circle" style="font-size: 80px; color: #ccc;"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="photo" id="photoInput" style="display: none;" accept="image/*">
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="removePhoto" data-translate="remove_photo">Remove Photo</button>
                        <small class="text-muted" data-translate="photo_requirements">Recommended size: 300x400px</small>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="changeGodImageModal" tabindex="-1" aria-labelledby="changeGodImageLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-translate="select_god_image">Select God Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php
                        $god_images = ['ganesh.png', 'krishna.png', 'shiva.png', 'durga.png', 'sai-baba.png'];
                        foreach ($god_images as $img):
                        ?>
                            <div class="col-4 mb-3">
                                <img src="assets/images/gods/<?php echo htmlspecialchars($img); ?>" class="img-fluid god-image-option" style="cursor: pointer; max-height: 150px; object-fit: contain;" alt="<?php echo htmlspecialchars(basename($img, '.png')); ?>" data-image="<?php echo htmlspecialchars($img); ?>">
                                <p class="text-center mt-2"><?php echo htmlspecialchars(ucfirst(basename($img, '.png'))); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-translate="close">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCustomHeadingModal" tabindex="-1" aria-labelledby="addCustomHeadingLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-translate="add_custom_heading">Add Custom Heading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" id="customHeadingInput" placeholder="Enter custom heading">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-translate="cancel">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCustomHeading" data-translate="save">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add cropping modal -->
    <div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" data-translate="crop_photo">Crop Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cropperWrapper">
                        <img id="cropperImage" src="#" alt="Crop preview" style="max-width: 100%;">
                    </div>
                    <div class="cropper-controls">
                        <input type="range" class="zoom-slider" id="zoomSlider" min="0" max="1" step="0.1" value="0">
                        <div class="mt-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-translate="cancel">Cancel</button>
                            <button type="button" class="btn btn-primary" id="cropButton" data-translate="crop">Crop</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        const translations = {
            en: {
                'title': 'Customize Your Biodata',
                'select_language': 'Select Language:',
                'change_god_photo': 'Change God Photo',
                'remove_god_photo': 'Remove God Photo',
                'god_names': 'God Name(s)',
                'add_god_name': 'Add God Name',
                'biodata_title': 'Title:',
                'add_custom_heading': 'Add Custom Heading',
                'biodata_section': 'Biodata',
                'family_details': 'Family Details',
                'contact_details': 'Contact Details',
                'add_field': 'Add Field',
                'reset_form': 'Reset Form',
                'create_biodata': 'Create Biodata',
                'upload_photo': 'Upload Your Photo',
                'click_to_add_photo': 'Click here to add your photo',
                'click_to_add_god_image': 'Click to add god image',
                'photo_requirements': 'Recommended size: 300x400px',
                'select_god_image': 'Select God Image',
                'close': 'Close',
                'cancel': 'Cancel',
                'save': 'Save',
                'remove_photo': 'Remove Photo',
            },
            mr: {
                'title': 'तुमचे बायोडाटा सानुकूलित करा',
                'select_language': 'भाषा निवडा:',
                'change_god_photo': 'देवाचे फोटो बदला',
                'remove_god_photo': 'देवाचे फोटो काढून टाका',
                'god_names': 'देवाचे नाव(ने)',
                'add_god_name': 'देवाचे नाव जोडा',
                'biodata_title': 'शीर्षक:',
                'add_custom_heading': 'सानुकूल शीर्षक जोडा',
                'biodata_section': 'बायोडाटा',
                'family_details': 'कौटुंबिक माहिती',
                'contact_details': 'संपर्क माहिती',
                'add_field': 'फील्ड जोडा',
                'reset_form': 'फॉर्म रीसेट करा',
                'create_biodata': 'बायोडाटा तयार करा',
                'upload_photo': 'तुमचे फोटो अपलोड करा',
                'click_to_add_photo': 'फोटो जोडण्यासाठी येथे क्लिक करा',
                'click_to_add_god_image': 'देवाचे प्रतिमा जोडण्यासाठी क्लिक करा',
                'photo_requirements': 'शिफारस केलेले आकार: 300x400px',
                'select_god_image': 'देवाचे प्रतिमा निवडा',
                'close': 'बंद करा',
                'cancel': 'रद्द करा',
                'save': 'जतन करा',
                'remove_photo': 'फोटो काढून टाका',
            }
        };

        translations.en.crop_photo = 'Crop Photo';
        translations.en.crop = 'Crop';
        translations.mr.crop_photo = 'फोटो क्रॉप करा';
        translations.mr.crop = 'क्रॉप करा';

        let cropper;
        let originalImageUrl;

        const dropdownOptions = <?php echo json_encode($dropdown_options); ?>;

        let currentLang = 'en';

        function updateLanguage(lang) {
            currentLang = lang;
            document.querySelectorAll('[data-translate]').forEach(el => {
                const key = el.getAttribute('data-translate');
                if (translations[lang][key]) {
                    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                        el.placeholder = translations[lang][key];
                    } else {
                        el.textContent = translations[lang][key];
                    }
                }
            });

            // Update key inputs
            document.querySelectorAll('.key-input').forEach(input => {
                const enValue = input.getAttribute('data-lang-en');
                const mrValue = input.getAttribute('data-lang-mr');
                input.value = lang === 'en' ? enValue : mrValue;
            });

            // Update dropdown displays and hidden values
            document.querySelectorAll('.value-input.form-select').forEach(select => {
                const key = select.getAttribute('data-key');
                if (dropdownOptions[key]) {
                    const selectedValue = select.value;
                    const options = select.options;
                    for (let i = 0; i < options.length; i++) {
                        const enValue = options[i].getAttribute('data-lang-en');
                        const mrValue = options[i].getAttribute('data-lang-mr');
                        options[i].textContent = lang === 'en' ? enValue : mrValue;
                    }
                    // Update hidden input with the correct language value
                    const hiddenInput = select.nextElementSibling;
                    if (hiddenInput && hiddenInput.classList.contains('hidden-value')) {
                        const index = dropdownOptions[key].en.indexOf(selectedValue);
                        if (index !== -1) {
                            hiddenInput.value = lang === 'en' ?
                                dropdownOptions[key].en[index] :
                                dropdownOptions[key].mr[index];
                        }
                    }
                }
            });

            document.getElementById('currentLanguage').value = lang;
            document.querySelectorAll('.lang-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`lang-${lang === 'en' ? 'english' : 'marathi'}`).classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateLanguage('en');
            document.getElementById('lang-english').addEventListener('click', () => updateLanguage('en'));
            document.getElementById('lang-marathi').addEventListener('click', () => updateLanguage('mr'));

            // Handle dropdown changes
            document.querySelectorAll('.value-input.form-select').forEach(select => {
                select.addEventListener('change', function() {
                    const key = this.getAttribute('data-key');
                    const selectedValue = this.value;
                    const hiddenInput = this.nextElementSibling;
                    if (hiddenInput && hiddenInput.classList.contains('hidden-value')) {
                        const index = dropdownOptions[key].en.indexOf(selectedValue);
                        if (index !== -1) {
                            hiddenInput.value = currentLang === 'en' ?
                                dropdownOptions[key].en[index] :
                                dropdownOptions[key].mr[index];
                        }
                    }
                });
            });

            const godImagePreview = document.getElementById('godImagePreview');
            const godImagePlaceholder = document.getElementById('godImagePlaceholder');

            // Fixed god image alignment
            document.querySelectorAll('.god-image-option').forEach(img => {
                img.addEventListener('click', function() {
                    const imgPath = this.getAttribute('data-image');
                    const godImageContainer = document.querySelector('.god-image-container');

                    if (!godImageContainer) {
                        const container = document.createElement('div');
                        container.className = 'god-image-container text-center';
                        document.querySelector('.mb-4.text-center').prepend(container);
                    }

                    const existingImg = document.getElementById('godImagePreview');
                    if (existingImg) {
                        existingImg.src = `assets/images/gods/${imgPath}`;
                    } else {
                        const img = document.createElement('img');
                        img.id = 'godImagePreview';
                        img.src = `assets/images/gods/${imgPath}`;
                        img.className = 'img-fluid rounded-circle shadow';
                        img.style.maxWidth = '120px';
                        img.style.height = '120px';
                        img.style.objectFit = 'cover';
                        document.querySelector('.god-image-container').appendChild(img);
                    }

                    if (godImagePlaceholder) godImagePlaceholder.style.display = 'none';
                    document.getElementById('selectedGodImage').value = imgPath;
                    document.getElementById('godImageInput').value = '';
                    document.getElementById('removeGodImageInput').value = '0';
                    bootstrap.Modal.getInstance(document.getElementById('changeGodImageModal')).hide();
                });
            });

            document.getElementById('godImageInput').addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        if (godImagePreview) {
                            godImagePreview.src = e.target.result;
                            godImagePreview.style.display = 'block';
                        } else {
                            const img = document.createElement('img');
                            img.id = 'godImagePreview';
                            img.src = e.target.result;
                            img.className = 'img-fluid rounded-circle shadow';
                            img.style.maxWidth = '120px';
                            img.style.height = '120px';
                            img.style.objectFit = 'cover';
                            document.querySelector('.mb-4.text-center').insertBefore(img, document.querySelector('.mt-3'));
                        }
                        if (godImagePlaceholder) godImagePlaceholder.style.display = 'none';
                        document.getElementById('selectedGodImage').value = '';
                        document.getElementById('removeGodImageInput').value = '0';
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('removeGodImage').addEventListener('click', () => {
                if (godImagePreview) godImagePreview.style.display = 'none';
                if (godImagePlaceholder) godImagePlaceholder.style.display = 'block';
                document.getElementById('godImageInput').value = '';
                document.getElementById('selectedGodImage').value = '';
                document.getElementById('removeGodImageInput').value = '1';
            });

            document.getElementById('addGodName').addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'field-row god-name-row';
                row.innerHTML = `
                    <input type="text" name="god_names[]" class="form-control" placeholder="${translations[currentLang]['add_god_name']}">
                    <div class="field-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-god-name">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                document.getElementById('godNames').appendChild(row);
            });

            function addField(section) {
                const containerId = `${section}Fields`;
                const fields = document.getElementById(containerId);
                const row = document.createElement('div');
                row.className = 'field-row';
                row.innerHTML = `
                    <input type="text" name="${section}_keys[]" class="form-control key-input" placeholder="${translations[currentLang]['add_field']}" data-lang-en="New Field" data-lang-mr="नवीन फील्ड">
                    <input type="text" name="${section}_values[]" class="form-control value-input" placeholder="Value">
                    <div class="field-actions">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-up">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon move-down">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger btn-icon delete-field">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                fields.appendChild(row);
            }

            document.getElementById('addBiodataField').addEventListener('click', () => addField('biodata'));
            document.getElementById('addFamilyField').addEventListener('click', () => addField('family'));
            document.getElementById('addContactField').addEventListener('click', () => addField('contact'));

            document.addEventListener('click', e => {
                if (e.target.closest('.delete-field') || e.target.closest('.delete-god-name')) {
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
                if (e.target.closest('[data-bs-target="#addCustomHeadingModal"]')) {
                    const section = e.target.getAttribute('data-section');
                    document.getElementById('saveCustomHeading').setAttribute('data-section', section);
                }
            });

            document.getElementById('saveCustomHeading').addEventListener('click', () => {
                const heading = document.getElementById('customHeadingInput').value.trim();
                const section = document.getElementById('saveCustomHeading').getAttribute('data-section');
                if (heading && section) {
                    const select = document.getElementById(`${section}_title`);
                    const option = document.createElement('option');
                    option.value = heading;
                    option.textContent = heading;
                    select.appendChild(option);
                    select.value = heading;
                    bootstrap.Modal.getInstance(document.getElementById('addCustomHeadingModal')).hide();
                    document.getElementById('customHeadingInput').value = '';
                }
            });

            document.getElementById('photoPlaceholder').addEventListener('click', () => {
                document.getElementById('photoInput').click();
            });

            // Modified photo input handler
            document.getElementById('photoInput').addEventListener('change', function(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const file = files[0];
                    const reader = new FileReader();

                    reader.onload = (event) => {
                        originalImageUrl = event.target.result;
                        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

                        // Initialize cropper when modal is shown
                        document.getElementById('cropModal').addEventListener('shown.bs.modal', () => {
                            const image = document.getElementById('cropperImage');
                            image.src = originalImageUrl;

                            if (cropper) {
                                cropper.destroy();
                            }

                            cropper = new Cropper(image, {
                                aspectRatio: 3 / 4,
                                viewMode: 1,
                                autoCropArea: 1,
                                background: false,
                                guides: false,
                                zoomOnWheel: false,
                                ready() {
                                    document.getElementById('zoomSlider').addEventListener('input', (e) => {
                                        const value = parseFloat(e.target.value);
                                        cropper.zoomTo(value);
                                    });
                                }
                            });
                        });

                        // Cleanup when modal hides
                        document.getElementById('cropModal').addEventListener('hidden.bs.modal', () => {
                            if (cropper) {
                                cropper.destroy();
                                cropper = null;
                            }
                        });

                        cropModal.show();
                    };

                    reader.readAsDataURL(file);
                }
            });

            // Handle crop button click
            document.getElementById('cropButton').addEventListener('click', () => {
                if (cropper) {
                    const canvas = cropper.getCroppedCanvas({
                        width: 300,
                        height: 400
                    });

                    canvas.toBlob((blob) => {
                        const file = new File([blob], 'cropped_photo.png', {
                            type: 'image/png'
                        });
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);

                        // Update photo input and preview
                        const photoInput = document.getElementById('photoInput');
                        photoInput.files = dataTransfer.files;

                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const placeholder = document.getElementById('photoPlaceholder');
                            placeholder.innerHTML = '';
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.id = 'photoPreview';
                            img.className = 'img-fluid rounded shadow';
                            img.style.maxWidth = '100%';
                            placeholder.appendChild(img);
                            document.getElementById('removePhotoInput').value = '0';
                        };
                        reader.readAsDataURL(file);

                        bootstrap.Modal.getInstance(document.getElementById('cropModal')).hide();
                    });
                }
            });

            document.getElementById('removePhoto').addEventListener('click', () => {
                const placeholder = document.getElementById('photoPlaceholder');
                placeholder.innerHTML = '<p data-translate="click_to_add_photo">Click here to add your photo</p><i class="fas fa-user-circle" style="font-size: 80px; color: #ccc;"></i>';
                document.getElementById('photoInput').value = '';
                document.getElementById('removePhotoInput').value = '1';
            });

            document.getElementById('resetForm').addEventListener('click', () => {
                if (confirm(translations[currentLang]['confirm_reset'] || 'Are you sure you want to reset the form?')) {
                    window.location.reload();
                }
            });

            document.getElementById('customizeForm').addEventListener('submit', function(e) {
                document.getElementById('currentLanguage').value = currentLang;
            });
        });
    </script>
</body>

</html>