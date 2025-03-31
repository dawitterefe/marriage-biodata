<?php
session_start();
include 'config/db.php';

if (!isset($_GET['id'])) die("Customized biodata ID not provided.");
$customized_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM customized_biodatas WHERE id = ?");
$stmt->execute([$customized_id]);
$customized = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customized) die("Customized biodata not found.");

function addWatermarks($image_path)
{
    $image = loadImage('assets/images/' . $image_path);
    $width = imagesx($image);
    $height = imagesy($image);

    // Modern font choice (ensure you have the font file)
    $font = 'assets/fonts/Roboto-Medium.ttf';

    // Dynamic sizing based on image dimensions
    $base_size = min($width, $height);
    $font_size = max(24, (int)($base_size / 40));
    $margin = (int)($base_size / 40);
    $padding = 15;
    $corner_radius = 8;

    // Gradient colors (white to red with 70% transparency)
    $start_color = [255, 255, 255, 50];  // White with 60% opacity
    $end_color = [139, 0, 0, 90];        // Dark red with 65% opacity

    // Semi-transparent text color
    $text_color = imagecolorallocatealpha($image, 255, 255, 255, 30); // 88% transparent

    // Function to create gradient rectangles
    function gradient_rect($img, $x, $y, $w, $h, $c1, $c2)
    {
        $x = (int)$x;
        $y = (int)$y;
        $w = (int)$w;
        $h = (int)$h;

        for ($i = 0; $i < $w; $i++) {
            $ratio = $i / $w;
            // Fixed missing closing parentheses
            $r = $c1[0] + (int)(($c2[0] - $c1[0]) * $ratio);
            $g = $c1[1] + (int)(($c2[1] - $c1[1]) * $ratio);
            $b = $c1[2] + (int)(($c2[2] - $c1[2]) * $ratio);
            $a = $c1[3] + (int)(($c2[3] - $c1[3]) * $ratio);
            $color = imagecolorallocatealpha($img, $r, $g, $b, $a);
            imageline($img, $x + $i, $y, $x + $i, $y + $h, $color);
        }
    }

    // Top-right badge-style watermark
    $text = "PREVIEW";
    $box = imagettfbbox($font_size, 0, $font, $text);
    $text_width = (int)($box[2] - $box[0]);
    $text_height = (int)abs($box[7] - $box[1]);

    $badge_width = (int)($text_width + $padding * 2);
    $badge_height = (int)($text_height + $padding);
    $x = (int)$margin;  // Position from left margin
    $y = (int)($margin + $badge_height);

    // Draw gradient background with shadow effect
    gradient_rect($image, $x + 2, $y + 2, $badge_width, $badge_height, [0, 0, 0, 50], [0, 0, 0, 50]); // Shadow
    gradient_rect($image, $x, $y, $badge_width, $badge_height, $start_color, $end_color);

    // Add text
    $text_x = (int)($x + ($badge_width - $text_width) / 2);
    $text_y = (int)($y + ($badge_height - $text_height) / 2 + $text_height);
    imagettftext($image, $font_size, 0, $text_x, $text_y, $text_color, $font, $text);

    // Centered watermark
    $lines = [
        "DOWNLOAD THE FULL RESOLUTION IMAGE",
        "TO REMOVE THIS WATERMARK"
    ];

    // Calculate multi-line box size
    $max_width = 0;
    $total_height = 0;
    foreach ($lines as $line) {
        $box = imagettfbbox($font_size, 0, $font, $line);
        $max_width = max($max_width, (int)($box[2] - $box[0]));
        $total_height += (int)(abs($box[7] - $box[1]) + 5);
    }

    $box_width = (int)($max_width + $padding * 2);
    $box_height = (int)($total_height + $padding * 2);
    $x_center = (int)(($width - $box_width) / 2);
    $y_center = (int)(($height - $box_height) / 2);

    // Draw gradient background with shadow
    gradient_rect($image, $x_center + 3, $y_center + 3, $box_width, $box_height, [0, 0, 0, 50], [0, 0, 0, 50]);
    gradient_rect($image, $x_center, $y_center, $box_width, $box_height, $start_color, $end_color);

    // Add text lines
    $current_y = (int)($y_center + $padding + $text_height);
    foreach ($lines as $line) {
        $box = imagettfbbox($font_size, 0, $font, $line);
        $text_width = (int)($box[2] - $box[0]);
        $x_text = (int)($x_center + ($box_width - $text_width) / 2);
        imagettftext($image, $font_size, 0, $x_text, $current_y, $text_color, $font, $line);
        $current_y += (int)(abs($box[7] - $box[1]) + 15);
    }

    // Add subtle diagonal pattern
    $pattern_color = imagecolorallocatealpha($image, 255, 255, 255, 90);
    $angle = 30;
    $pattern_text = "PREVIEW";
    $pattern_size = (int)($base_size / 15);
    for ($i = -2; $i < 5; $i++) {
        for ($j = -2; $j < 5; $j++) {
            $x = (int)($width / 4 * $i);
            $y = (int)($height / 4 * $j);
            imagettftext($image, $pattern_size, $angle, $x, $y, $pattern_color, $font, $pattern_text);
        }
    }

    $watermarked_path = 'watermarked/customized_' . time() . '.png';
    imagepng($image, 'assets/images/' . $watermarked_path);
    imagedestroy($image);
    return $watermarked_path;
}

function loadImage($path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') return imagecreatefromjpeg($path);
    if ($ext === 'png') return imagecreatefrompng($path);
    return false;
}

$watermarked_image = addWatermarks($customized['preview_image']);

// Handle design change
if (isset($_POST['template_id'])) {
    $new_template_id = $_POST['template_id'];
    $stmt = $pdo->prepare("SELECT background_image FROM templates WHERE id = ?");
    $stmt->execute([$new_template_id]);
    $new_template = $stmt->fetch(PDO::FETCH_ASSOC);

    $new_preview = generatePreviewImage(
        $customized['god_image'],
        $customized['god_name'],
        $customized['biodata'],
        $customized['family_details'],
        $customized['contact_details'],
        $new_template['background_image'],
        $customized['photo']
    );

    $stmt = $pdo->prepare("UPDATE customized_biodatas SET template_id = ?, background_image = ?, preview_image = ? WHERE id = ?");
    $stmt->execute([$new_template_id, $new_template['background_image'], $new_preview, $customized_id]);
    header("Location: preview.php?id=$customized_id");
    exit;
}

// Handle payment form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['phone'])) {
    $user_identifier = $_SERVER['REMOTE_ADDR'] . '-' . session_id();
    $stmt = $pdo->prepare("INSERT INTO payments (customized_biodata_id, user_identifier, phone_number) VALUES (?, ?, ?)");
    $stmt->execute([$customized_id, $user_identifier, $_POST['phone']]);
    $payment_id = $pdo->lastInsertId();
    // Redirect to payment gateway (placeholder)
    header("Location: payment.php?payment_id=$payment_id");
    exit;
}

// Image generation function (same as in customize.php)
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Preview Biodata</title>
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

        .btn-secondary {
            background: var(--gradient-accent);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
        }

        /* Added interactivity: slight scale effect on hover for the preview image */
        img.img-fluid {
            transition: transform 0.3s ease-in-out;
        }

        img.img-fluid:hover {
            transform: scale(1.02);
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
                <img src="assets/images/<?php echo $watermarked_image; ?>" class="img-fluid" alt="Preview" style="pointer-events: none;">
            </div>
            <div class="col-md-4">
                <a href="customize.php?customized_id=<?php echo $customized_id; ?>" class="btn btn-primary mb-3">Edit Biodata</a>
                <button class="btn btn-secondary mb-3" data-bs-toggle="modal" data-bs-target="#changeDesignModal">Change Design</button>
                <p>वॉटरमार्कशिवाय बायोडाटा डाउनलोड करण्यासाठी<br>किंमत ₹ 100 फक्त 50/- रुपये.<br>आपणांस लगेच प्रिंटसाठी PDF आणि High Quality इमेज मिळते<br>Unlimited Edit and Download.</p>
                <form method="post">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number:</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <button type="submit" class="btn btn-success">Download Now</button>
                </form>
                <p class="mt-3">
                    <i class="fab fa-google-pay me-2"></i>
                    <i class="fab fa-phone-alt me-2"></i>
                    <i class="fab fa-paypal me-2"></i>
                    <i class="fab fa-amazon-pay me-2"></i>
                    <i class="fas fa-mobile-alt me-2"></i><br>
                    Safe and Secure Payment with 100% Payment Protection.<br>
                    Secure connection Https<br>
                    हा बायोडाटा डाउनलोड का करायचा?<br>
                    बायोडाटावरती वॉटरमार्क नसतो<br>
                    पेमेंट नंतर सुद्धा एडिट व डाउनलोड करू शकता.<br>
                    High-Quality Image आणि PDF मिळते<br>
                    Unlimited Edit & Download<br>
                    लोकप्रिय बायोडाटा डिझाईन<br>
                    एकदम भारी तयार झालेला बायोडाटा आत्ताच डाउनलोड करा<br>
                    पेमेंट झाल्यानंतर लगेच डाउनलोड करू शकता.
                </p>
            </div>
        </div>
    </div>

    <!-- Change Design Modal -->
    <div class="modal fade" id="changeDesignModal" tabindex="-1" aria-labelledby="changeDesignLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeDesignLabel">Select New Design</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="changeDesignForm">
                        <div class="row">
                            <?php
                            $stmt = $pdo->query("SELECT id, preview_image FROM templates");
                            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($templates as $template):
                            ?>
                                <div class="col-4">
                                    <img src="assets/images/<?php echo $template['preview_image']; ?>" class="img-fluid" alt="Template">
                                    <button type="submit" name="template_id" value="<?php echo $template['id']; ?>" class="btn btn-primary btn-sm mt-2">Select</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>