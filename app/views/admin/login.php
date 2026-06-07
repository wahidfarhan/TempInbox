<div class="container my-5 py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            
            <div class="text-center mb-4">
                <i class="fa-solid fa-user-shield text-primary fs-1 mb-2"></i>
                <h2 class="text-bright">Administration Access</h2>
                <p class="text-muted small">Access dashboard and system logs</p>
            </div>

            <!-- Error Alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger glass-panel border-danger text-light mb-4 animated-fade-in" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Login Card -->
            <div class="glass-panel p-4 p-md-5 animated-fade-in">
                <form action="" method="POST">
                    <?= $csrf_field ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label text-muted small">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-30 text-muted">
                                <i class="fa-solid fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label text-muted small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-secondary border-opacity-30 text-muted">
                                <i class="fa-solid fa-key"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-premium w-100 py-2.5">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Authenticate
                    </button>
                </form>
            </div>

            <div class="text-center mt-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>/" class="text-secondary text-decoration-none small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Return to Main Page
                </a>
            </div>

        </div>
    </div>
</div>
