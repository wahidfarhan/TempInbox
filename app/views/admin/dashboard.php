
<div class="container my-4">
    <!-- Dashboard Title -->
    <div class="row align-items-center mb-4 animated-fade-in">
        <div class="col-md-8">
            <h1 class="h3 text-bright mb-1"><i class="fa-solid fa-gauge-high text-primary me-2"></i>Administration Dashboard</h1>
            <p class="text-muted mb-0">Manage system settings, active temporary mailboxes, and storage cleanups.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-dark border border-secondary text-light px-3 py-2 rounded">
                <i class="fa-solid fa-user-shield text-success me-1"></i> Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
            </span>
        </div>
    </div>

    <!-- Alert Messaging -->
    <?php if ($success): ?>
        <div class="alert alert-success glass-panel border-success text-light mb-4 animated-fade-in" role="alert">
            <i class="fa-solid fa-circle-check me-2 text-success"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger glass-panel border-danger text-light mb-4 animated-fade-in" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="row g-3 mb-4 animated-fade-in" style="animation-delay: 0.1s;">
        <!-- Card 1: Total Aliases -->
        <div class="col-6 col-md-3">
            <div class="glass-panel p-3 d-flex align-items-center">
                <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-20 rounded-3 me-3 d-none d-sm-block">
                    <i class="fa-solid fa-envelope-open-text text-primary fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Aliases</span>
                    <h3 class="mb-0 text-bright font-monospace"><?= $total_aliases ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Aliases -->
        <div class="col-6 col-md-3">
            <div class="glass-panel p-3 d-flex align-items-center">
                <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-20 rounded-3 me-3 d-none d-sm-block">
                    <i class="fa-solid fa-circle-check text-success fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Active Aliases</span>
                    <h3 class="mb-0 text-bright font-monospace"><?= $active_aliases ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Messages -->
        <div class="col-6 col-md-3">
            <div class="glass-panel p-3 d-flex align-items-center">
                <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-20 rounded-3 me-3 d-none d-sm-block">
                    <i class="fa-solid fa-inbox text-info fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">Total Messages</span>
                    <h3 class="mb-0 text-bright font-monospace"><?= $total_messages ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 4: Database Size -->
        <div class="col-6 col-md-3">
            <div class="glass-panel p-3 d-flex align-items-center">
                <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-20 rounded-3 me-3 d-none d-sm-block">
                    <i class="fa-solid fa-database text-warning fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">SQLite Size</span>
                    <h3 class="mb-0 text-bright font-monospace"><?= number_format($db_size / 1024, 1) ?> <span class="fs-6">KB</span></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Workspace -->
    <div class="row animated-fade-in" style="animation-delay: 0.2s;">
        <div class="col-md-12">
            
            <!-- Tab Selectors -->
            <ul class="nav nav-tabs border-secondary border-opacity-20 mb-4" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active bg-transparent border-0 text-muted px-4 py-2.5" id="aliases-tab" data-bs-toggle="tab" data-bs-target="#aliases-tab-panel" type="button" role="tab">
                        <i class="fa-solid fa-list me-2"></i>Mail Aliases
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link bg-transparent border-0 text-muted px-4 py-2.5" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-tab-panel" type="button" role="tab">
                        <i class="fa-solid fa-sliders me-2"></i>Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link bg-transparent border-0 text-muted px-4 py-2.5" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-tab-panel" type="button" role="tab">
                        <i class="fa-solid fa-terminal me-2"></i>Logs & Utilities
                    </button>
                </li>
            </ul>

            <!-- Tab Panels -->
            <div class="tab-content" id="adminTabsContent">
                
                <!-- Tab 1: Aliases Table -->
                <div class="tab-pane fade show active" id="aliases-tab-panel" role="tabpanel">
                    <div class="glass-panel p-4">
                        <h5 class="text-light mb-3">All Active and Expired Aliases</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle border-secondary border-opacity-20">
                                <thead>
                                    <tr class="text-muted">
                                        <th style="width: 80px;">ID</th>
                                        <th>Email Alias</th>
                                        <th>Created At</th>
                                        <th>Expires At</th>
                                        <th>Status</th>
                                        <th>Access URL</th>
                                        <th style="width: 100px;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($aliases)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No temporary mailboxes found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($aliases as $row): ?>
                                            <?php 
                                            $isValid = (strtotime($row['expires_at']) > time()) && $row['is_active'];
                                            ?>
                                            <tr>
                                                <td class="font-monospace text-muted"><?= $row['id'] ?></td>
                                                <td>
                                                    <span class="text-bright font-monospace"><?= htmlspecialchars($row['alias'] . '@' . $row['domain']) ?></span>
                                                </td>
                                                <td class="small font-monospace"><?= $row['created_at'] ?></td>
                                                <td class="small font-monospace"><?= $row['expires_at'] ?></td>
                                                <td>
                                                    <?php if ($isValid): ?>
                                                        <span class="badge-custom-active">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge-custom-expired">Expired</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                                        <input type="text" class="form-control bg-dark border-secondary text-muted small" value="<?= $baseUrl ?>/inbox?token=<?= $row['token'] ?>" id="tokenUrl<?= $row['id'] ?>" readonly>
                                                        <button class="btn btn-outline-secondary" type="button" onclick="copyText('tokenUrl<?= $row['id'] ?>', this)">
                                                            <i class="fa-solid fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <form action="<?= $baseUrl ?>/admin/alias/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this alias?');">
                                                        <?= $csrf_field ?>
                                                        <input type="hidden" name="alias_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Alias">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pt-3">
                                <nav aria-label="Aliases pagination">
                                    <ul class="pagination pagination-sm justify-content-center m-0">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($i === $current_page) ? 'active' : '' ?>">
                                                <a class="page-link <?= ($i === $current_page) ? 'btn-premium text-white border-0' : 'bg-dark border-secondary text-light' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab 2: System Settings -->
                <div class="tab-pane fade" id="settings-tab-panel" role="tabpanel">
                    <div class="glass-panel p-4">
                        <h5 class="text-light mb-4">Application Configurations</h5>
                        
                        <form action="<?= $baseUrl ?>/admin/settings" method="POST">
                            <?= $csrf_field ?>
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">General Settings</h6>
                                    
                                    <div class="mb-3">
                                        <label for="domains" class="form-label text-muted small">Allowed Domains</label>
                                        <input type="text" class="form-control" id="domains" name="domains" value="<?= htmlspecialchars($domains_text) ?>" required placeholder="domain1.com, domain2.com">
                                        <div class="form-text text-muted small">Comma separated domains list available for alias generation.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="default_expiry" class="form-label text-muted small">Default Expiry (Hours)</label>
                                                <input type="number" class="form-control" id="default_expiry" name="default_expiry" value="<?= $default_expiry ?>" required min="1">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="email_retention" class="form-label text-muted small">Email Retention (Days)</label>
                                                <input type="number" class="form-control" id="email_retention" name="email_retention" value="<?= $email_retention ?>" required min="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Administrator Account</h6>
                                    
                                    <div class="mb-3">
                                        <label for="admin_username" class="form-label text-muted small">Admin Username</label>
                                        <input type="text" class="form-control" id="admin_username" name="admin_username" value="<?= htmlspecialchars($_SESSION['admin_user']) ?>" required>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label text-muted small">New Password</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Leave blank to keep current">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label text-muted small">Confirm Password</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="border-secondary border-opacity-30 my-4">
                            
                            <h6 class="text-primary mb-3"><i class="fa-solid fa-paper-plane me-2"></i>Outbound SMTP Settings (For Sending & Replies)</h6>
                            <p class="text-muted small mb-3">Configure credentials to allow sending emails. Leave blank to fallback to catch-all IMAP credentials on the same host.</p>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label text-muted small">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($smtp_host ?? '') ?>" placeholder="e.g. mail.yourdomain.com">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label text-muted small">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($smtp_port ?? '587') ?>" placeholder="587">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label for="smtp_encryption" class="form-label text-muted small">Encryption</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?= ($smtp_encryption ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS)</option>
                                            <option value="ssl" <?= ($smtp_encryption ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (SMTPS)</option>
                                            <option value="none" <?= ($smtp_encryption ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <label for="smtp_username" class="form-label text-muted small">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($smtp_username ?? '') ?>" placeholder="user@domain.com">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="mb-3">
                                         <label for="smtp_password" class="form-label text-muted small">SMTP Password</label>
                                         <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="" placeholder="Leave empty to keep current password">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-premium px-4"><i class="fa-solid fa-circle-check me-2"></i>Save Configurations</button>
                        </form>
                    </div>
                </div>

                <!-- Tab 3: System Logs & Utilities -->
                <div class="tab-pane fade" id="logs-tab-panel" role="tabpanel">
                    <div class="row g-4">
                        <!-- Manual Cleanup Tools -->
                        <div class="col-md-4">
                            <div class="glass-panel p-4 h-100">
                                <h5 class="text-light mb-3">Manual Maintenance Tools</h5>
                                <p class="text-muted small">These triggers run cleanup commands immediately. Use them to manually free up storage space.</p>
                                
                                <div class="d-flex flex-column gap-2 mt-4">
                                    <form action="<?= $baseUrl ?>/admin/cleanup" method="POST">
                                        <?= $csrf_field ?>
                                        <input type="hidden" name="action" value="expired_aliases">
                                        <button type="submit" class="btn btn-outline-warning w-100 py-2.5 text-start">
                                            <i class="fa-solid fa-trash-arrow-up me-2"></i>Clean Expired Aliases
                                        </button>
                                    </form>

                                    <form action="<?= $baseUrl ?>/admin/cleanup" method="POST">
                                        <?= $csrf_field ?>
                                        <input type="hidden" name="action" value="old_emails">
                                        <button type="submit" class="btn btn-outline-warning w-100 py-2.5 text-start">
                                            <i class="fa-solid fa-broom me-2"></i>Clean Old Emails (<?= $email_retention ?> Days Retention)
                                        </button>
                                    </form>

                                    <form action="<?= $baseUrl ?>/admin/cleanup" method="POST" onsubmit="return confirm('Clear all logs?');">
                                        <?= $csrf_field ?>
                                        <input type="hidden" name="action" value="clear_logs">
                                        <button type="submit" class="btn btn-outline-danger w-100 py-2.5 text-start">
                                            <i class="fa-solid fa-rectangle-list me-2"></i>Clear System Logs
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="mt-4 p-3 bg-black bg-opacity-20 border border-secondary border-opacity-10 rounded">
                                    <span class="text-light small d-block mb-1"><strong>Cron Command Example:</strong></span>
                                    <code class="font-monospace text-muted small d-block select-all" style="word-break: break-all;">
                                        php <?= ROOT_DIR ?>/cron/fetch.php
                                    </code>
                                </div>
                            </div>
                        </div>

                        <!-- System Logs List -->
                        <div class="col-md-8">
                            <div class="glass-panel p-4 h-100">
                                <h5 class="text-light mb-3">Latest System Log History</h5>
                                
                                <div class="overflow-y-auto font-monospace small text-muted pe-1" style="max-height: 400px;">
                                    <?php if (empty($logs)): ?>
                                        <p class="text-center py-5">No log history available.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush bg-transparent">
                                            <?php foreach ($logs as $log): ?>
                                                <?php 
                                                $levelColor = 'text-info';
                                                if ($log['level'] === 'ERROR') $levelColor = 'text-danger';
                                                if ($log['level'] === 'WARNING') $levelColor = 'text-warning';
                                                ?>
                                                <div class="py-2 border-bottom border-secondary border-opacity-10 d-flex align-items-start">
                                                    <span class="text-muted font-monospace me-2" style="font-size: 0.8rem;"><?= $log['created_at'] ?></span>
                                                    <span class="badge bg-transparent <?= $levelColor ?> border border-secondary border-opacity-35 me-2 font-monospace px-2" style="font-size: 0.7rem;"><?= $log['level'] ?></span>
                                                    <span class="text-light flex-grow-1" style="font-size: 0.82rem;"><?= htmlspecialchars($log['message']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
// Copy text helper
function copyText(inputId, btn) {
    const copyInput = document.getElementById(inputId);
    copyInput.select();
    navigator.clipboard.writeText(copyInput.value).then(function() {
        const icon = btn.querySelector('i');
        icon.className = 'fa-solid fa-check text-success';
        setTimeout(() => {
            icon.className = 'fa-solid fa-copy';
        }, 1500);
    });
}
</script>
