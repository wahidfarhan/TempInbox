<div class="container my-4">
    <!-- Inbox Header -->
    <div class="row align-items-center mb-4 animated-fade-in">
        <div class="col-md-7 mb-3 mb-md-0">
            <h1 class="h3 text-light mb-1">Disposable Mailbox</h1>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fs-5 text-success font-monospace" id="inboxEmail"><?= htmlspecialchars($alias['alias'] . '@' . $alias['domain']) ?></span>
                <button class="btn btn-sm btn-outline-secondary py-1" id="copyInboxBtn" title="Copy Address">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
            </div>
        </div>
        <div class="col-md-5 text-md-end">
            <div class="d-inline-flex flex-column align-items-md-end">
                <div class="mb-1 text-muted small">
                    <i class="fa-regular fa-clock me-1 text-warning"></i>
                    Expires in: <strong id="countdownTimer" data-seconds="<?= $time_left ?>">Calculating...</strong>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-premium-outline" id="composeMailBtn">
                        <i class="fa-solid fa-paper-plane me-1"></i> Compose Mail
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="deleteInboxBtn" data-token="<?= $alias['token'] ?>">
                        <i class="fa-solid fa-trash-can me-1"></i> Delete Inbox
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="row g-4 animated-fade-in" style="animation-delay: 0.1s;">
        <!-- Left Sidebar (Email List) -->
        <div class="col-lg-4">
            <div class="glass-panel p-3 h-100 d-flex flex-column" style="min-height: 500px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0 text-light"><i class="fa-solid fa-envelope me-2 text-primary"></i>Messages</h5>
                        <button class="btn btn-sm btn-link text-muted p-0 ms-1" id="manualRefreshBtn" title="Check for new mail now" style="box-shadow: none;">
                            <i class="fa-solid fa-rotate fs-6"></i>
                        </button>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoRefreshSwitch" checked>
                        <label class="form-check-label text-muted small" for="autoRefreshSwitch">Auto-refresh (10s)</label>
                    </div>
                </div>

                <!-- Email List Scroll Area -->
                <div class="flex-grow-1 overflow-y-auto pe-1" style="max-height: 480px;" id="emailListContainer">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-5 text-muted" id="noMessagesPlaceholder">
                            <i class="fa-regular fa-folder-open display-6 mb-3 opacity-50"></i>
                            <p class="mb-0">Waiting for incoming emails...</p>
                            <span class="small opacity-70">Emails are fetched automatically.</span>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush bg-transparent">
                            <?php foreach ($messages as $msg): ?>
                                <div class="list-group-item bg-transparent mail-list-item px-3 py-3" data-id="<?= $msg['id'] ?>">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0 text-truncate text-bright" style="max-width: 70%;"><?= htmlspecialchars($msg['sender_name'] ?: 'Unknown') ?></h6>
                                        <span class="small text-muted font-monospace"><?= date('H:i', strtotime($msg['received_at'])) ?></span>
                                    </div>
                                    <div class="text-truncate text-light small mb-1"><?= htmlspecialchars($msg['subject'] ?: '(No Subject)') ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small text-muted font-monospace" style="font-size: 0.75rem;"><?= htmlspecialchars($msg['sender_email']) ?></span>
                                        <?php if (!empty($msg['attachments'])): ?>
                                            <i class="fa-solid fa-paperclip text-muted small" title="Contains attachments"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pt-3 border-top border-secondary border-opacity-20">
                        <nav aria-label="Inbox pagination">
                            <ul class="pagination pagination-sm justify-content-center m-0">
                                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link bg-dark border-secondary text-light" href="?token=<?= $alias['token'] ?>&page=<?= $current_page - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i === $current_page) ? 'active' : '' ?>">
                                        <a class="page-link <?= ($i === $current_page) ? 'btn-premium text-white border-0' : 'bg-dark border-secondary text-light' ?>" href="?token=<?= $alias['token'] ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link bg-dark border-secondary text-light" href="?token=<?= $alias['token'] ?>&page=<?= $current_page + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Content Area (Message Viewer) -->
        <div class="col-lg-8">
            <div class="glass-panel p-4 h-100 d-flex flex-column" id="messageViewer">
                <!-- Select Message Placeholder -->
                <div class="my-auto text-center py-5 text-muted" id="viewerPlaceholder">
                    <i class="fa-regular fa-envelope-open display-4 mb-3 opacity-50"></i>
                    <h5>No Message Selected</h5>
                    <p class="mb-0 small">Choose an email from the list to read its contents.</p>
                </div>

                <!-- Message Viewer Content (Hidden by Default) -->
                <div id="viewerContent" class="d-none h-100">
                    <div class="d-flex justify-content-between align-items-start border-bottom border-secondary border-opacity-20 pb-3 mb-3">
                        <div>
                            <h4 id="msgSubject" class="text-bright mb-2">Subject Placeholder</h4>
                            <div class="d-flex flex-column">
                                <span class="text-light small">From: <strong id="msgSenderName">Sender</strong> &lt;<span id="msgSenderEmail" class="text-muted font-monospace">email</span>&gt;</span>
                                <span class="text-muted small mt-1">Received: <span id="msgDate" class="font-monospace">Date</span></span>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-premium" id="replyMailBtn">
                                <i class="fa-solid fa-reply me-1"></i> Reply
                            </button>
                        </div>
                    </div>

                    <!-- Attachment Section (Hidden if empty) -->
                    <div id="msgAttachmentsContainer" class="mb-3 d-none">
                        <span class="small text-muted mb-2 d-block"><i class="fa-solid fa-paperclip me-1"></i>Attachments:</span>
                        <div id="msgAttachmentsList" class="d-flex flex-wrap gap-2">
                            <!-- Attachment pills go here -->
                        </div>
                    </div>

                    <!-- Message Body Tabs -->
                    <ul class="nav nav-tabs border-secondary border-opacity-20 mb-3" id="bodyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active bg-transparent border-0 text-muted" id="html-tab" data-bs-toggle="tab" data-bs-target="#html-tab-panel" type="button" role="tab">
                                <i class="fa-solid fa-code me-2"></i>Formatted HTML
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link bg-transparent border-0 text-muted" id="plain-tab" data-bs-toggle="tab" data-bs-target="#plain-tab-panel" type="button" role="tab">
                                <i class="fa-solid fa-align-left me-2"></i>Plain Text
                            </button>
                        </li>
                    </ul>

                    <!-- Message Body View -->
                    <div class="tab-content flex-grow-1" id="bodyTabsContent">
                        <div class="tab-pane fade show active" id="html-tab-panel" role="tabpanel">
                            <div id="htmlFrameContainer">
                                <!-- Secure sandboxed iframe serves HTML here -->
                            </div>
                        </div>
                        <div class="tab-pane fade" id="plain-tab-panel" role="tabpanel">
                            <div class="p-3 rounded bg-black bg-opacity-30 border border-secondary border-opacity-10 overflow-auto text-light font-monospace small" style="max-height: 500px; white-space: pre-wrap;" id="msgBodyPlain">
                                <!-- Plain text body goes here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compose Mail Modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border border-secondary border-opacity-30 text-light" style="background: rgba(15, 11, 34, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-secondary border-opacity-20 py-3">
                <h5 class="modal-title text-bright" id="composeModalLabel"><i class="fa-solid fa-paper-plane text-primary me-2"></i>Compose Outbound Email</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="composeForm">
                    <input type="hidden" name="token" value="<?= $alias['token'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" id="inReplyToId" name="in_reply_to_id" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted small">From Alias</label>
                        <input type="text" class="form-control bg-dark border-secondary text-muted font-monospace" value="<?= htmlspecialchars($alias['alias'] . '@' . $alias['domain']) ?>" readonly style="opacity: 0.8;">
                    </div>
                    
                    <div class="mb-3">
                        <label for="composeTo" class="form-label text-muted small">To (Recipient Email)</label>
                        <input type="email" class="form-control" id="composeTo" name="to" required placeholder="recipient@example.com">
                    </div>
                    
                    <div class="mb-3">
                        <label for="composeSubject" class="form-label text-muted small">Subject</label>
                        <input type="text" class="form-control" id="composeSubject" name="subject" placeholder="Enter email subject">
                    </div>
                    
                    <div class="mb-4">
                        <label for="composeBody" class="form-label text-muted small">Message Content</label>
                        <textarea class="form-control" id="composeBody" name="body" rows="8" required placeholder="Type your email body here..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i>Secure SMTP pipeline</span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-premium px-4" id="sendEmailSubmitBtn">
                                <i class="fa-solid fa-paper-plane me-1"></i> Send Email
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Send Loader overlay inside Modal -->
                <div id="composeModalLoader" class="d-none text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted mb-0">Transmitting email through SMTP...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSRF Field for actions -->
<div class="d-none">
    <input type="hidden" id="csrfToken" value="<?= $_SESSION['csrf_token'] ?>">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = '<?= $alias['token'] ?>';
    const baseUrl = APP_URL;
    
    const countdownTimer = document.getElementById('countdownTimer');
    const copyInboxBtn = document.getElementById('copyInboxBtn');
    const deleteInboxBtn = document.getElementById('deleteInboxBtn');
    const manualRefreshBtn = document.getElementById('manualRefreshBtn');
    const autoRefreshSwitch = document.getElementById('autoRefreshSwitch');
    const emailListContainer = document.getElementById('emailListContainer');
    
    const viewerPlaceholder = document.getElementById('viewerPlaceholder');
    const viewerContent = document.getElementById('viewerContent');
    const msgSubject = document.getElementById('msgSubject');
    const msgSenderName = document.getElementById('msgSenderName');
    const msgSenderEmail = document.getElementById('msgSenderEmail');
    const msgDate = document.getElementById('msgDate');
    const msgBodyPlain = document.getElementById('msgBodyPlain');
    const htmlFrameContainer = document.getElementById('htmlFrameContainer');
    const msgAttachmentsContainer = document.getElementById('msgAttachmentsContainer');
    const msgAttachmentsList = document.getElementById('msgAttachmentsList');
    
    // Outbound Mail & Modals selectors
    const composeMailBtn = document.getElementById('composeMailBtn');
    const replyMailBtn = document.getElementById('replyMailBtn');
    const composeModalEl = document.getElementById('composeModal');
    const composeForm = document.getElementById('composeForm');
    const composeTo = document.getElementById('composeTo');
    const composeSubject = document.getElementById('composeSubject');
    const composeBody = document.getElementById('composeBody');
    const inReplyToIdInput = document.getElementById('inReplyToId');
    const composeModalLabel = document.getElementById('composeModalLabel');
    const composeModalLoader = document.getElementById('composeModalLoader');
    
    const composeModal = new bootstrap.Modal(composeModalEl);
    
    let activeMessageId = null;
    let refreshInterval = null;
    let secondsLeft = parseInt(countdownTimer.getAttribute('data-seconds'), 10);
    
    // State for latest message ID tracking and details cache
    let latestMessageId = null;
    let loadedMessagesData = {};

    // 1. Expiration Countdown Timer
    function updateCountdown() {
        if (secondsLeft <= 0) {
            countdownTimer.textContent = "Expired";
            countdownTimer.className = "text-danger";
            alert("This mailbox has expired. You will be redirected to the homepage.");
            window.location.href = baseUrl + '/';
            return;
        }

        const days = Math.floor(secondsLeft / 86400);
        const hours = Math.floor((secondsLeft % 86400) / 3600);
        const minutes = Math.floor((secondsLeft % 3600) / 60);
        const seconds = secondsLeft % 60;

        let display = '';
        if (days > 0) display += days + 'd ';
        if (hours > 0 || days > 0) display += hours + 'h ';
        display += minutes + 'm ' + seconds + 's';

        countdownTimer.textContent = display;
        secondsLeft--;
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);

    // 2. Copy Inbox Address
    copyInboxBtn.addEventListener('click', function() {
        const email = document.getElementById('inboxEmail').textContent;
        navigator.clipboard.writeText(email).then(function() {
            copyInboxBtn.innerHTML = '<i class="fa-solid fa-check text-success"></i> Copied!';
            setTimeout(() => {
                copyInboxBtn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy';
            }, 2000);
        });
    });

    // 2.5 Manual Refresh Trigger
    const refreshIcon = manualRefreshBtn.querySelector('i');
    manualRefreshBtn.addEventListener('click', function() {
        // Spin animation
        refreshIcon.classList.add('fa-spin');
        manualRefreshBtn.setAttribute('disabled', 'true');

        const csrf = document.getElementById('csrfToken').value;
        const formData = new FormData();
        formData.append('token', token);
        formData.append('csrf_token', csrf);

        fetch(baseUrl + '/inbox/refresh', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            refreshIcon.classList.remove('fa-spin');
            manualRefreshBtn.removeAttribute('disabled');

            if (data.success) {
                renderEmailList(data.messages);
                if (data.imported_count > 0) {
                    // Quick visual feedback
                    const temp = manualRefreshBtn.innerHTML;
                    manualRefreshBtn.innerHTML = `<i class="fa-solid fa-check text-success fs-6"></i> <span class="text-success small ms-1" style="font-size:0.75rem;">+${data.imported_count}</span>`;
                    setTimeout(() => {
                        manualRefreshBtn.innerHTML = '<i class="fa-solid fa-rotate fs-6"></i>';
                    }, 2500);
                }
            } else {
                alert(data.message || 'Error occurred while checking for mail.');
            }
        })
        .catch(() => {
            refreshIcon.classList.remove('fa-spin');
            manualRefreshBtn.removeAttribute('disabled');
            alert('A network connection error occurred while checking for mail.');
        });
    });

    // 3. Delete Inbox
    deleteInboxBtn.addEventListener('click', function() {
        if (confirm("Are you sure you want to permanently delete this mailbox and all of its messages?")) {
            const csrf = document.getElementById('csrfToken').value;
            const formData = new FormData();
            formData.append('token', token);
            formData.append('csrf_token', csrf);

            fetch(baseUrl + '/alias/delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = baseUrl + '/';
                } else {
                    alert(data.message || "Failed to delete mailbox.");
                }
            })
            .catch(() => alert("An error occurred."));
        }
    });

    // 4. Load Message Contents
    function selectMessage(msgId) {
        activeMessageId = msgId;

        // Add active class in list UI
        document.querySelectorAll('.mail-list-item').forEach(item => {
            if (parseInt(item.getAttribute('data-id'), 10) === msgId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Show loading state
        viewerPlaceholder.classList.add('d-none');
        viewerContent.classList.remove('d-none');
        
        // Smooth scroll to message viewer on mobile/tablets
        if (window.innerWidth < 992) {
            document.getElementById('messageViewer').scrollIntoView({ behavior: 'smooth' });
        }
        
        msgSubject.textContent = "Loading message...";
        msgSenderName.textContent = "...";
        msgSenderEmail.textContent = "...";
        msgDate.textContent = "...";
        msgBodyPlain.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-light"></div></div>';
        htmlFrameContainer.innerHTML = '';
        msgAttachmentsContainer.classList.add('d-none');
        msgAttachmentsList.innerHTML = '';

        fetch(`${baseUrl}/inbox/message?token=${token}&id=${msgId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && activeMessageId === msgId) {
                // Cache message data for reply
                loadedMessagesData[msgId] = data;

                msgSubject.textContent = data.subject;
                msgSenderName.textContent = data.sender_name;
                msgSenderEmail.textContent = data.sender_email;
                msgDate.textContent = new Date(data.received_at.replace(/-/g, "/")).toLocaleString();
                
                // Plain body
                msgBodyPlain.innerHTML = data.body_plain || '<span class="text-muted italic">(No Plain Text Content)</span>';
                
                // Attachments
                if (data.attachments && data.attachments.length > 0) {
                    msgAttachmentsContainer.classList.remove('d-none');
                    data.attachments.forEach(file => {
                        const sizeKB = (file.size / 1024).toFixed(1);
                        const pill = document.createElement('span');
                        pill.className = 'badge bg-dark border border-secondary text-light px-3 py-2 rounded-pill font-monospace';
                        pill.innerHTML = `<i class="fa-regular fa-file me-2 text-primary"></i>${file.name} <span class="text-muted small">(${sizeKB} KB)</span>`;
                        msgAttachmentsList.appendChild(pill);
                    });
                } else {
                    msgAttachmentsContainer.classList.add('d-none');
                }

                // HTML secure iframe load
                const tabs = document.getElementById('bodyTabs');
                const htmlTab = document.getElementById('html-tab');
                const plainTab = document.getElementById('plain-tab');

                if (data.has_html) {
                    htmlTab.removeAttribute('disabled');
                    htmlTab.click(); // Default to HTML
                    
                    // Create secure inline container with Shadow DOM encapsulation
                    // This bypasses server-side iframe blocking (frame-ancestors 'none') 
                    // while fully isolating external CSS styles from the main app dashboard.
                    const emailContainer = document.createElement('div');
                    emailContainer.className = 'mail-shadow-container';
                    emailContainer.style.background = '#ffffff';
                    emailContainer.style.color = '#000000';
                    emailContainer.style.borderRadius = '8px';
                    emailContainer.style.padding = '24px';
                    emailContainer.style.marginTop = '15px';
                    emailContainer.style.minHeight = '450px';
                    emailContainer.style.maxHeight = '650px';
                    emailContainer.style.overflow = 'auto';
                    emailContainer.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    
                    // Attach Shadow Root
                    const shadow = emailContainer.attachShadow({mode: 'open'});
                    
                    // Fetch sanitized HTML directly and inject into Shadow DOM
                    fetch(`${baseUrl}/inbox/html-body?token=${token}&id=${msgId}`)
                    .then(res => res.text())
                    .then(html => {
                        shadow.innerHTML = html;
                    })
                    .catch(() => {
                        shadow.innerHTML = '<p class="text-danger p-3">Failed to load email HTML content.</p>';
                    });

                    htmlFrameContainer.appendChild(emailContainer);
                } else {
                    htmlTab.setAttribute('disabled', 'true');
                    plainTab.click(); // Fallback to plain text tab
                    htmlFrameContainer.innerHTML = '<p class="text-muted p-4 italic">HTML version of this email is not available.</p>';
                }
            }
        })
        .catch(() => {
            msgSubject.textContent = "Error loading message";
        });
    }

    // Bind click handlers to list items
    emailListContainer.addEventListener('click', function(e) {
        const item = e.target.closest('.mail-list-item');
        if (item) {
            const id = parseInt(item.getAttribute('data-id'), 10);
            selectMessage(id);
        }
    });

    // 4.5. Synthesize Chime Sound via Web Audio API
    let audioCtx = null;
    function playChime() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
            osc.frequency.setValueAtTime(880.00, audioCtx.currentTime + 0.12); // A5
            
            gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.35);
            
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            
            osc.start();
            osc.stop(audioCtx.currentTime + 0.35);
        } catch (e) {
            console.error("Audio synthesis blocked/failed: ", e);
        }
    }

    // Initialize/resume AudioContext on first click to bypass autoplay restrictions
    document.addEventListener('click', function() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
    }, { once: true });

    // Request Notification Permissions on load
    if (window.Notification && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showNotification(sender, subject) {
        if (window.Notification && Notification.permission === 'granted') {
            try {
                new Notification("New Temp Email Received", {
                    body: `From: ${sender}\nSubject: ${subject}`,
                    icon: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23a855f7"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>'
                });
            } catch (e) {
                console.error("Desktop notification failed: ", e);
            }
        }
    }

    // 4.6 Compose & Reply Modal Logic
    composeMailBtn.addEventListener('click', function() {
        composeForm.reset();
        inReplyToIdInput.value = "0";
        composeModalLabel.innerHTML = '<i class="fa-solid fa-paper-plane text-primary me-2"></i>Compose Outbound Email';
        
        composeTo.removeAttribute('readonly');
        composeSubject.removeAttribute('readonly');
        
        composeForm.classList.remove('d-none');
        composeModalLoader.classList.add('d-none');
        
        composeModal.show();
    });

    replyMailBtn.addEventListener('click', function() {
        if (!activeMessageId || !loadedMessagesData[activeMessageId]) {
            alert("No active message selected to reply to.");
            return;
        }

        const data = loadedMessagesData[activeMessageId];
        composeForm.reset();
        
        inReplyToIdInput.value = activeMessageId;
        composeTo.value = data.sender_email;
        composeTo.setAttribute('readonly', 'true');
        
        let replySub = data.subject || '';
        if (replySub && !replySub.toLowerCase().startsWith('re:')) {
            replySub = 'Re: ' + replySub;
        }
        composeSubject.value = replySub;
        composeSubject.setAttribute('readonly', 'true');
        
        composeModalLabel.innerHTML = `<i class="fa-solid fa-reply text-success me-2"></i>Reply to: ${escapeHtml(data.sender_name || 'Sender')}`;
        
        composeForm.classList.remove('d-none');
        composeModalLoader.classList.add('d-none');
        
        composeModal.show();
    });

    // Form Submission
    composeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        composeForm.classList.add('d-none');
        composeModalLoader.classList.remove('d-none');

        const formData = new FormData(composeForm);

        fetch(baseUrl + '/inbox/send', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Email sent successfully!");
                composeModal.hide();
                composeForm.reset();
            } else {
                alert(data.message || "Failed to send email.");
                composeForm.classList.remove('d-none');
                composeModalLoader.classList.add('d-none');
            }
        })
        .catch(err => {
            alert("A transmission network error occurred.");
            composeForm.classList.remove('d-none');
            composeModalLoader.classList.add('d-none');
        });
    });

    // Initialize latest message ID from the DOM items list
    const initialItems = document.querySelectorAll('.mail-list-item');
    if (initialItems.length > 0) {
        let maxId = 0;
        initialItems.forEach(item => {
            const id = parseInt(item.getAttribute('data-id'), 10);
            if (id > maxId) maxId = id;
        });
        latestMessageId = maxId;
    } else {
        latestMessageId = 0;
    }

    // 5. Fetch New Emails (Poller)
    function fetchUpdates() {
        fetch(`${baseUrl}/inbox?token=${token}&ajax=1`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderEmailList(data.messages);
            }
        })
        .catch(err => console.error("Error updates: ", err));
    }

    function renderEmailList(messages) {
        if (!messages || messages.length === 0) {
            latestMessageId = 0;
            emailListContainer.innerHTML = `
                <div class="text-center py-5 text-muted" id="noMessagesPlaceholder">
                    <i class="fa-regular fa-folder-open display-6 mb-3 opacity-50"></i>
                    <p class="mb-0">Waiting for incoming emails...</p>
                    <span class="small opacity-70">Emails are fetched automatically.</span>
                </div>`;
            return;
        }

        // Check for new message chime & notify
        const newLatestId = parseInt(messages[0].id, 10);
        if (latestMessageId !== null && newLatestId > latestMessageId) {
            const newMsg = messages[0];
            playChime();
            showNotification(newMsg.sender_name || 'Unknown', newMsg.subject || '(No Subject)');
        }
        latestMessageId = newLatestId;

        let html = '<div class="list-group list-group-flush bg-transparent">';
        messages.forEach(msg => {
            const activeClass = (activeMessageId === parseInt(msg.id, 10)) ? 'active' : '';
            const clipIcon = (msg.attachments && msg.attachments.length > 0) ? '<i class="fa-solid fa-paperclip text-muted small" title="Contains attachments"></i>' : '';
            const timeStr = new Date(msg.received_at.replace(/-/g, "/")).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            html += `
                <div class="list-group-item bg-transparent mail-list-item px-3 py-3 ${activeClass}" data-id="${msg.id}">
                    <div class="d-flex justify-content-between mb-1">
                        <h6 class="mb-0 text-truncate text-bright" style="max-width: 70%;">${escapeHtml(msg.sender_name || 'Unknown')}</h6>
                        <span class="small text-muted font-monospace">${timeStr}</span>
                    </div>
                    <div class="text-truncate text-light small mb-1">${escapeHtml(msg.subject || '(No Subject)')}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted font-monospace" style="font-size: 0.75rem;">${escapeHtml(msg.sender_email)}</span>
                        ${clipIcon}
                    </div>
                </div>`;
        });
        html += '</div>';
        
        // Save current scroll position
        const scrollTop = emailListContainer.scrollTop;
        emailListContainer.innerHTML = html;
        emailListContainer.scrollTop = scrollTop;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Manage Polling interval state
    function setupPolling() {
        if (autoRefreshSwitch.checked) {
            if (!refreshInterval) {
                refreshInterval = setInterval(fetchUpdates, 10000); // 10 seconds
            }
        } else {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }
    }

    autoRefreshSwitch.addEventListener('change', setupPolling);
    setupPolling(); // Initialize
});
</script>
