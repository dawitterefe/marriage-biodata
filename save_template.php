<?php
// save_template.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: preview.php');
    exit;
}
?>

<?php
// preview.php
echo "Preview page under construction.";
?>

<?php
// payment.php
echo "Payment page under construction.";
?>