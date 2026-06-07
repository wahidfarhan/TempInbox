<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center mb-5">
            <h1 class="display-4 fw-extrabold mb-3 animated-fade-in" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Secure. Disposable. Instant.
            </h1>
            <p class="lead text-muted animated-fade-in" style="animation-delay: 0.1s;">
                Generate dynamic, temporary email addresses to guard your mailbox against spam, phishing, and tracking.
            </p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">
            <!-- Alert Display -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger glass-panel border-danger text-light mb-4 animated-fade-in" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Main Generator Panel -->
            <div class="glass-panel p-4 p-md-5 mb-4 animated-fade-in" style="animation-delay: 0.2s;">
                
                <!-- Tab Headers -->
                <ul class="nav nav-pills mb-4 justify-content-center" id="aliasTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active px-4 py-2 me-2" id="random-tab" data-bs-toggle="pill" data-bs-target="#random-panel" type="button" role="tab">
                            <i class="fa-solid fa-shuffle me-2"></i>Random Alias
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-4 py-2" id="custom-tab" data-bs-toggle="pill" data-bs-target="#custom-panel" type="button" role="tab">
                            <i class="fa-solid fa-pen-to-square me-2"></i>Custom Suffix
                        </button>
                    </li>
                </ul>

                <!-- Form Content -->
                <div class="tab-content" id="aliasTabContent">
                    <!-- Random Alias Panel -->
                    <div class="tab-pane fade show active" id="random-panel" role="tabpanel">
                        <form id="randomAliasForm">
                            <input type="hidden" name="type" value="random">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="row g-3 align-items-center mb-4">
                                <div class="col-md-7">
                                    <label class="form-label text-muted small">Choose Domain</label>
                                    <select class="form-select" name="domain">
                                        <?php foreach ($domains as $domain): ?>
                                            <option value="<?= htmlspecialchars($domain) ?>">@<?= htmlspecialchars($domain) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label text-muted small">Expires In</label>
                                    <select class="form-select" name="expiry">
                                        <option value="1">1 Hour</option>
                                        <option value="12">12 Hours</option>
                                        <option value="24" selected>24 Hours</option>
                                        <option value="72">3 Days</option>
                                        <option value="168">7 Days</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-premium w-100 py-3 text-uppercase tracking-wider fs-6">
                                <i class="fa-solid fa-magic me-2"></i>Generate Random Email
                            </button>
                        </form>
                    </div>

                    <!-- Custom Alias Panel -->
                    <div class="tab-pane fade" id="custom-panel" role="tabpanel">
                        <form id="customAliasForm">
                            <input type="hidden" name="type" value="custom">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label text-muted small">Custom Alias Suffix</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="custom_name" placeholder="e.g. newsletter.work" minlength="3" maxlength="30" required>
                                    <select class="form-select" name="domain" style="max-width: 180px;">
                                        <?php foreach ($domains as $domain): ?>
                                            <option value="<?= htmlspecialchars($domain) ?>">@<?= htmlspecialchars($domain) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-text text-muted small">Only letters, numbers, dots, and hyphens allowed.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small">Expires In</label>
                                <select class="form-select" name="expiry">
                                    <option value="1">1 Hour</option>
                                    <option value="12">12 Hours</option>
                                    <option value="24" selected>24 Hours</option>
                                    <option value="72">3 Days</option>
                                    <option value="168">7 Days</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-premium w-100 py-3 text-uppercase tracking-wider fs-6">
                                <i class="fa-solid fa-bolt me-2"></i>Create Custom Email
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Result Box (Hidden by Default) -->
                <div id="resultBox" class="mt-4 p-4 rounded-4 border border-secondary bg-black bg-opacity-20 d-none">
                    <h5 class="text-center text-light mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i>Email Generated!</h5>
                    <div class="input-group mb-3">
                        <input type="text" id="generatedEmail" class="form-control text-center bg-dark border-secondary text-success font-monospace py-3 fs-5" readonly>
                        <button class="btn btn-outline-secondary px-3" type="button" id="copyEmailBtn" title="Copy to Clipboard">
                            <i class="fa-solid fa-copy fs-5"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <a href="#" id="viewInboxBtn" class="btn btn-premium w-100 py-2">
                                <i class="fa-solid fa-inbox me-2"></i>Open Inbox
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <button id="resetFormBtn" class="btn btn-outline-secondary w-100 py-2">
                                <i class="fa-solid fa-rotate-left me-2"></i>Create Another
                            </button>
                        </div>
                    </div>
                    <div class="text-center text-muted small mt-3" id="expirationWarning"></div>
                </div>

                <!-- Loader (Hidden by Default) -->
                <div id="formLoader" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Creating...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0">Securing your new inbox...</p>
                </div>

            </div>
            
            <!-- Technical Information Grid -->
            <div class="row text-center g-3 animated-fade-in" style="animation-delay: 0.3s;">
                <div class="col-4">
                    <div class="glass-panel p-3">
                        <i class="fa-solid fa-shield-halved text-primary mb-2 fs-4"></i>
                        <h6 class="text-light mb-0">Secure</h6>
                        <span class="text-muted small">Token access</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="glass-panel p-3">
                        <i class="fa-solid fa-clock-rotate-left text-primary mb-2 fs-4"></i>
                        <h6 class="text-light mb-0">Auto-Clean</h6>
                        <span class="text-muted small">Expired deleted</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="glass-panel p-3">
                        <i class="fa-solid fa-eye-slash text-primary mb-2 fs-4"></i>
                        <h6 class="text-light mb-0">Private</h6>
                        <span class="text-muted small">No registration</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Frontend Javascript Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const randomForm = document.getElementById('randomAliasForm');
    const customForm = document.getElementById('customAliasForm');
    const formLoader = document.getElementById('formLoader');
    const resultBox = document.getElementById('resultBox');
    const generatedEmail = document.getElementById('generatedEmail');
    const copyEmailBtn = document.getElementById('copyEmailBtn');
    const viewInboxBtn = document.getElementById('viewInboxBtn');
    const resetFormBtn = document.getElementById('resetFormBtn');
    const expirationWarning = document.getElementById('expirationWarning');
    const aliasTab = document.getElementById('aliasTab');
    const customTab = document.getElementById('custom-tab');
    const randomTab = document.getElementById('random-tab');

    // Handle Copy to Clipboard
    copyEmailBtn.addEventListener('click', function() {
        generatedEmail.select();
        navigator.clipboard.writeText(generatedEmail.value).then(function() {
            // Change icon temporarily to checkmark
            const icon = copyEmailBtn.querySelector('i');
            icon.className = 'fa-solid fa-check text-success';
            setTimeout(() => {
                icon.className = 'fa-solid fa-copy';
            }, 2000);
        });
    });

    // Reset Generator Screen
    resetFormBtn.addEventListener('click', function() {
        resultBox.classList.add('d-none');
        aliasTab.classList.remove('d-none');
        
        // Show active panel based on which tab was selected
        if (customTab.classList.contains('active')) {
            document.getElementById('custom-panel').classList.add('show', 'active');
            customForm.reset();
        } else {
            document.getElementById('random-panel').classList.add('show', 'active');
            randomForm.reset();
        }
    });

    // Form Submission Helper
    function handleFormSubmit(form) {
        // Hide form fields & tabs, show loader
        form.style.display = 'none';
        aliasTab.classList.add('d-none');
        formLoader.classList.remove('d-none');

        const formData = new FormData(form);

        fetch(APP_URL + '/alias/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            formLoader.classList.add('d-none');
            form.style.display = 'block'; // reset form display state in background
            
            if (data.success) {
                generatedEmail.value = data.email;
                viewInboxBtn.href = APP_URL + data.redirect_url;
                
                // Format expiration warning
                const expiryDate = new Date(data.expires_at.replace(/-/g, "/"));
                expirationWarning.innerHTML = `<i class="fa-regular fa-clock me-1"></i> Expires on: <strong>${expiryDate.toLocaleString()}</strong>`;
                
                resultBox.classList.remove('d-none');
            } else {
                // Show errors using standard alerts
                alert(data.message || 'Error occurred while creating your alias.');
                aliasTab.classList.remove('d-none');
            }
        })
        .catch(error => {
            formLoader.classList.add('d-none');
            aliasTab.classList.remove('d-none');
            form.style.display = 'block';
            alert('A connection error occurred. Please try again.');
        });
    }

    // Bind Forms
    randomForm.addEventListener('submit', function(e) {
        e.preventDefault();
        handleFormSubmit(randomForm);
    });

    customForm.addEventListener('submit', function(e) {
        e.preventDefault();
        handleFormSubmit(customForm);
    });
});
</script>
