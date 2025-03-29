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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Marriage Biodata Maker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            --gradient-accent: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-dark: #333;
            --text-light: #fff;
        }

        /* Navbar */
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

        /* Header */
        header {
            background: var(--gradient-primary);
            position: relative;
            padding-top: 100px;
            padding-bottom: 50px;
            overflow: hidden;
        }

        header .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/default-bg.jpg') no-repeat center center;
            background-size: cover;
            opacity: 0.15;
        }

        header .header-content {
            position: relative;
            z-index: 2;
        }

        .floating-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 1.5rem;
            margin: -30px auto 20px;
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
            color: var(--text-light);
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-30px);
            }

            60% {
                transform: translateY(-15px);
            }
        }

        /* Section Header */
        .section-header {
            background: var(--gradient-accent);
            padding: 2rem;
            border-radius: 15px;
            color: var(--text-light);
            margin: 2rem 0;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* Template Card */
        .template-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
        }

        .template-card:hover {
            transform: scale(1.03) translateY(-10px);
            box-shadow: 0 15px 30px rgba(78, 84, 200, 0.2);
        }

        /* Buttons */
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

        /* Smaller View Design Button */
        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
        }

        /* Info Cards */
        .info-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            color: var(--text-dark);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        /* Information Section Enhancements */
        .container.my-5.py-5 ul.list-group li:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }

        .container.my-5.py-5 h2:hover {
            color: #4e54c8;
            transition: color 0.3s;
        }

        /* Back-to-Top Button */
        #back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--gradient-primary);
            color: var(--text-light);
            border: none;
            border-radius: 50%;
            padding: 10px 12px;
            font-size: 18px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
            z-index: 999;
        }

        #back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #4a6491 100%);
            color: white;
            padding: 3rem 0;
            margin-top: 3rem;
        }

        footer a {
            color: #ddd;
            transition: all 0.3s ease;
        }

        footer a:hover {
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Biodata Maker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Search By Religion</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header>
        <div class="bg-overlay"></div>
        <div class="container text-center header-content">
            <div class="floating-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h1 class="display-4 fw-bold text-light mb-3">Create Your Perfect<br>Marriage Biodata</h1>
            <p class="lead text-light mb-4">Professional Templates • Easy Customization • Instant Download</p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button class="btn btn-light btn-lg px-4 rounded-pill" onclick="scrollToSection('templates-with-photo')">
                    <i class="fas fa-camera me-2"></i> With Photo
                </button>
                <button class="btn btn-outline-light btn-lg px-4 rounded-pill" onclick="scrollToSection('templates-without-photo')">
                    <i class="far fa-image me-2"></i> Without Photo
                </button>
            </div>
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down fa-2x"></i>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <div class="container my-5 py-5">
        <div class="section-header">
            <h2 class="display-5 mb-3">Why Choose Our Biodata Maker?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-card text-center p-4 h-100">
                        <i class="fas fa-magic text-primary fa-3x mb-3"></i>
                        <h4>Professional Designs</h4>
                        <p>Modern templates crafted by professional designers</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card text-center p-4 h-100">
                        <i class="fas fa-edit text-primary fa-3x mb-3"></i>
                        <h4>Easy Customization</h4>
                        <p>User-friendly editor with real-time preview</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card text-center p-4 h-100">
                        <i class="fas fa-download text-primary fa-3x mb-3"></i>
                        <h4>Instant Download</h4>
                        <p>Get PDF immediately after completion</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates With Photo -->
    <div class="container my-5" id="templates-with-photo">
        <h2 class="mb-4 fw-bold text-primary">Templates With Photo</h2>
        <div class="row g-4">
            <?php foreach ($templates as $template): ?>
                <?php if (!empty($template['photo'])): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="template-card p-4">
                            <img src="assets/images/<?php echo $template['preview_image']; ?>"
                                class="card-img-top img-fluid d-block mx-auto"
                                style="max-width: 210px; max-height: 297px; object-fit: contain;"
                                alt="Template">
                            <div class="card-body text-center mt-2">
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal<?php echo $template['id']; ?>">View Design</button>
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
                                        <a href="customize.php?id=<?php echo $template['id']; ?>" class="btn btn-primary btn-sm">Customize</a>
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
        <h2 class="mb-4 fw-bold text-primary">Templates Without Photo</h2>
        <div class="row g-4">
            <?php foreach ($templates as $template): ?>
                <?php if (empty($template['photo'])): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="template-card p-4">
                            <img src="assets/images/<?php echo $template['preview_image']; ?>"
                                class="card-img-top img-fluid d-block mx-auto"
                                style="max-width: 210px; max-height: 297px; object-fit: contain;"
                                alt="Template">
                            <div class="card-body text-center mt-2">
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#templateModal<?php echo $template['id']; ?>">View Design</button>
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
                                        <a href="customize.php?id=<?php echo $template['id']; ?>" class="btn btn-primary btn-sm">Customize</a>
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
    <div class="container my-5 py-5">
        <div class="section-header">
            <h2 class="display-5 mb-3">What Information Does the Marathi Biodata Format Contain?</h2>
            <p class="lead">
                As mentioned above, if a boy or girl wants to find a suitable partner, they must exchange their information. So, let's see what a biodata includes.
            </p>

            <h2 class="mt-5">Biodata Format Details</h2>
            <p>
                The first part is Personal Information. It includes details about the private life of the boy or girl and contains three types of information:
            </p>
            <ul class="list-group">
                <li class="list-group-item">Personal Information (वैयक्तिक माहिती)</li>
                <li class="list-group-item">Family Information (कौटुंबिक माहिती)</li>
                <li class="list-group-item">Contact Information (संपर्क माहिती)</li>
            </ul>
            <h2 class="mt-5">Personal Information</h2>
            <p>
                The first part is Personal Information. It includes details about the private life of the boy or girl and contains three types of information:
            </p>
            <ul class="list-group">
                <li class="list-group-item">Personal details of boy or girl (मुलाची किंवा मुलीची वैयक्तिक माहिती)</li>
                <li class="list-group-item">Kundli Information (कुंडली मधील माहिती)</li>
                <li class="list-group-item">Education and Occupation (शिक्षण व व्यवसाईक माहिती)</li>
            </ul>
            <h2 class="mt-5">Additional Information</h2>
            <p>
                To summarize, the Marathi Biodata format clearly lists the following information for a potential partner:
            </p>
            <ul class="list-group">
                <li class="list-group-item">Personal Information (वैयक्तिक माहिती)</li>
                <li class="list-group-item">Family Information (कौटुंबिक माहिती)</li>
                <li class="list-group-item">Contact Information (संपर्क माहिती)</li>
            </ul>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="text-white mb-3"><i class="fas fa-heart me-2"></i>Biodata Maker</h5>
                    <p>We help you create stunning marriage biodatas effortlessly.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                        <li class="mb-2"><a href="#" class="text-white"><i class="fas fa-chevron-right me-2"></i>Templates</a></li>
                        <li class="mb-2"><a href="#" class="text-white"><i class="fas fa-chevron-right me-2"></i>Contact</a></li>
                        <li><a href="#" class="text-white"><i class="fas fa-chevron-right me-2"></i>Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white mb-3">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Street, City, Country</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +91 1234567890</li>
                        <li><i class="fas fa-envelope me-2"></i> support@biodatamaker.com</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Biodata Maker. All rights reserved.</p>
            </div>
        </div>
    </footer>


    <!-- Back-to-Top Button -->
    <button id="back-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll function for header buttons
        function scrollToSection(id) {
            document.getElementById(id).scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Back-to-top scroll function
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/hide back-to-top button on scroll
        window.addEventListener('scroll', function() {
            const backToTopButton = document.getElementById('back-to-top');
            if (window.scrollY > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });

        // Scroll animation for cards and info sections
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            document.querySelectorAll('.template-card, .info-card').forEach((el) => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease-out';
                observer.observe(el);
            });
        });
    </script>
</body>

</html>