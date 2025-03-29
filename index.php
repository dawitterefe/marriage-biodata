<?php
include 'config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM templates");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching templates: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marriage Biodata Maker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Biodata Maker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Search By Religion</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="container my-5 text-center">
        <h1>Marriage Biodata Maker</h1>
        <p class="lead">Create your perfect biodata in minutes!</p>
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-primary" onclick="scrollToSection('templates-with-photo')">Templates With Photo</button>
            <button class="btn btn-primary" onclick="scrollToSection('templates-without-photo')">Templates Without Photo</button>
        </div>
        <!-- <img src="assets/images/default-bg.jpg" class="img-fluid mt-4" alt="Biodata Illustration" style="max-width: 300px;"> -->
    </div>

    <!-- About Section -->
    <div class="container my-5">
        <h2>Marriage Biodata Format in Marathi</h2>
        <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Beatae provident illo quidem facilis ipsam minus maiores neque corrupti quis, optio autem delectus quos nihil expedita ullam a cumque, quae officia! . Lorem, ipsum dolor sit amet consectetur adipisicing elit. Odio voluptates in quidem rerum, ratione optio. Perspiciatis provident placeat nesciunt recusandae esse vitae alias eveniet similique non fugit? Illo, fugit? Maiores.. Lorem ipsum dolor sit amet consectetur adipisicing elit. In iusto tempore voluptates reiciendis doloremque reprehenderit. Quam doloribus sit voluptatum voluptas adipisci veniam quae accusamus perspiciatis molestias, modi nulla eveniet dolore? Why is it necessary to prepare biodata for marriage in Marathi? A well-crafted biodata showcases your personality, family background, and values, making it easier to find the perfect match within the Marathi community.</p>
    </div>

    <!-- Templates With Photo -->
    <div class="container my-5" id="templates-with-photo">
        <h2>Biodata Templates With Photo</h2>
        <p class="text-muted">Choose from our beautifully designed templates.</p>
        <div class="row">
            <?php foreach ($templates as $template): ?>
                <?php if (!empty($template['photo'])): ?>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                        <div class="card">
                            <img src="assets/images/<?php echo $template['preview_image']; ?>" class="card-img-top template-img" alt="Template">
                            <div class="card-body text-center">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal<?php echo $template['id']; ?>">View Design</button>
                            </div>
                        </div>
                        <!-- Modal -->
                        <div class="modal fade" id="templateModal<?php echo $template['id']; ?>" tabindex="-1" aria-labelledby="templateModalLabel<?php echo $template['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="templateModalLabel<?php echo $template['id']; ?>">Template Preview</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="assets/images/<?php echo $template['preview_image']; ?>" class="img-fluid" alt="Template">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="customize.php?id=<?php echo $template['id']; ?>" class="btn btn-primary">Customize</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Templates Without Photo -->
    <div class="container my-5" id="templates-without-photo">
        <h2>Biodata Templates Without Photo</h2>
        <p class="text-muted">Simple yet elegant designs for your biodata.</p>
        <div class="row">
            <?php foreach ($templates as $template): ?>
                <?php if (empty($template['photo'])): ?>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                        <div class="card">
                            <img src="assets/images/<?php echo $template['preview_image']; ?>" class="card-img-top template-img" alt="Template">
                            <div class="card-body text-center">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal<?php echo $template['id']; ?>">View Design</button>
                            </div>
                        </div>
                        <!-- Modal -->
                        <div class="modal fade" id="templateModal<?php echo $template['id']; ?>" tabindex="-1" aria-labelledby="templateModalLabel<?php echo $template['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="templateModalLabel<?php echo $template['id']; ?>">Template Preview</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="assets/images/<?php echo $template['preview_image']; ?>" class="img-fluid" alt="Template">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="customize.php?id=<?php echo $template['id']; ?>" class="btn btn-primary">Customize</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Information Section -->
    <div class="container my-5">
        <h2>What Information Does the Marathi Biodata Format Contain?</h2>
        <p>It includes personal details like name, date of birth, and education, family background, contact information, and more, all presented in an attractive format.</p>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>We help you create stunning marriage biodatas effortlessly.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Home</a></li>
                        <li><a href="#" class="text-white">Templates</a></li>
                        <li><a href="#" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Email: support@biodatamaker.com<br>Phone: +91 1234567890</p>
                </div>
            </div>
            <div class="text-center mt-3">
                <p>&copy; 2023 Biodata Maker. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>