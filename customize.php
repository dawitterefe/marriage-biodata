<?php
include 'config/db.php';

if (!isset($_GET['id'])) die("Template ID not provided.");
$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
$stmt->execute([$_GET['id']]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) die("Template not found.");

$biodata = json_decode($template['biodata'], true);
$family_details = json_decode($template['family_details'], true);
$contact_details = $template['contact_details'] ? json_decode($template['contact_details'], true) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Biodata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-5">
        <h1>Customize Your Biodata</h1>
        <form action="save_template.php" method="post">
            <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
            <h3>Biodata</h3>
            <?php foreach ($biodata as $key => $value): ?>
                <div class="mb-3">
                    <label><?php echo $key; ?>:</label>
                    <input type="text" name="biodata[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>" class="form-control">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Save and Preview</button>
        </form>
    </div>
</body>

</html>