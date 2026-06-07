<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'TempInbox - Free Temporary Email') ?></title>
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="TempInbox is a self-hosted, secure, and open-source temporary email service. Generate aliases and protect your inbox from spam.">
    <meta name="robots" content="index, follow">
    
    <!-- Premium SVG Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23a855f7'><path d='M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z'/></svg>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= htmlspecialchars($baseUrl) ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Premium Stylesheet -->
    <link href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css?v=1.0.1" rel="stylesheet">
    
    <!-- Dynamic Base URL Detector in Client JS -->
    <script>
        const APP_URL = (function() {
            let path = window.location.pathname;
            let adminIdx = path.indexOf('/admin');
            if (adminIdx !== -1) path = path.substring(0, adminIdx);
            let inboxIdx = path.indexOf('/inbox');
            if (inboxIdx !== -1) path = path.substring(0, inboxIdx);
            
            // Strip filename if present
            if (path.endsWith('.php') || path.split('/').pop().includes('.')) {
                path = path.substring(0, path.lastIndexOf('/'));
            }
            if (path.endsWith('/')) {
                path = path.substring(0, path.length - 1);
            }
            return window.location.protocol + '//' + window.location.host + path;
        })();
    </script>
</head>
<body>

    <!-- Responsive Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= htmlspecialchars($baseUrl) ?>/">
                <i class="fa-solid fa-envelope-open-text me-2 fs-3"></i>
                <span>TempInbox</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3 text-light" href="<?= htmlspecialchars($baseUrl) ?>/">
                            <i class="fa-solid fa-house me-1"></i> Home
                        </a>
                    </li>
                    <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 text-light" href="<?= htmlspecialchars($baseUrl) ?>/admin">
                                <i class="fa-solid fa-gauge-high me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-danger btn-sm px-3" href="<?= htmlspecialchars($baseUrl) ?>/admin/logout">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-premium-outline btn-sm px-3" href="<?= htmlspecialchars($baseUrl) ?>/admin">
                                <i class="fa-solid fa-lock me-1"></i> Admin Area
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Page Content -->
    <main class="py-4">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-1">&copy; <?= date('Y') ?> <strong>TempInbox</strong>. All rights reserved.</p>
                    <p class="mb-0 text-muted">
                        Built for speed, security, and privacy. 
                        <a href="https://github.com" target="_blank" class="text-secondary text-decoration-none ms-2"><i class="fa-brands fa-github"></i> Open Source</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle with Popper JS -->
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
