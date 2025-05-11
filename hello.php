<?php
session_start();

// Plaintext password
$stored_password = 'secret123'; // Change this to your desired password
$auth_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    if ($password === $stored_password) {
        $_SESSION['authenticated'] = true;
    } else {
        $auth_error = 'Incorrect password.';
    }
}

// Check if authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAD TIGER priv8 Sender - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold text-center text-red-500 mb-6">MAD TIGER priv8 Sender - Login</h1>
    <?php if ($auth_error): ?>
    <div class="bg-red-600 text-white p-4 rounded mb-6"><?php echo htmlspecialchars($auth_error); ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
    <div>
    <label for="password" class="block text-sm font-medium">Password</label>
    <input type="password" id="password" name="password" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" required>
    </div>
    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white p-3 rounded font-semibold transition">Login</button>
    </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Main form logic (only shown if authenticated)
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $from_email = filter_input(INPUT_POST, 'from_email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $body = $_POST['body'];
    $email_list = filter_input(INPUT_POST, 'email_list', FILTER_SANITIZE_STRING);

    // Handle file upload
    $uploaded_emails = [];
    if (isset($_FILES['email_file']) && $_FILES['email_file']['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['email_file']['tmp_name']);
        $uploaded_emails = array_filter(array_map('trim', explode("\n", $file_content)), function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    // Combine textarea and uploaded emails
    $textarea_emails = array_filter(array_map('trim', explode("\n", $email_list)), function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    });
    $emails = array_unique(array_merge($textarea_emails, $uploaded_emails));

    if (empty($name) || empty($from_email) || empty($subject) || empty($body) || empty($emails)) {
        $error = "All fields are required, and at least one valid email is needed.";
    } elseif (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid from email address.";
    } else {
        // Store email data in session for AJAX sending
        $_SESSION['email_data'] = [
            'name' => $name,
            'from_email' => $from_email,
            'subject' => $subject,
            'body' => $body,
            'emails' => $emails
        ];
        $success = "Email sending started. Check the progress below.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MAD TIGER priv8 Sender</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
#preview {
border: 1px solid #4b5563;
min-height: 200px;
padding: 10px;
background: white;
color: black;
}
.tooltip {
    position: relative;
}
.tooltip:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    white-space: nowrap;
    z-index: 10;
}
#sendLog {
max-height: 200px;
overflow-y: auto;
background: #1f2937;
padding: 10px;
border-radius: 4px;
}
</style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-4xl bg-gray-800 p-6 rounded-lg shadow-lg">
<div class="flex justify-between items-center mb-6">
<h1 class="text-3xl font-bold text-red-500">MAD TIGER priv8 Sender</h1>
<form method="POST" action="">
<input type="hidden" name="logout" value="1">
<button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white p-2 rounded">Logout</button>
</form>
</div>

<?php if ($success): ?>
<div class="bg-green-600 text-white p-4 rounded mb-6"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="bg-red-600 text-white p-4 rounded mb-6"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form id="mailerForm" method="POST" enctype="multipart/form-data" class="space-y-4">
<div>
<label for="name" class="block text-sm font-medium tooltip" data-tooltip="Use a professional name. Ensure your domain has DKIM/SPF records.">Sender Name</label>
<input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="John Doe">
</div>
<div>
<label for="from_email" class="block text-sm font-medium tooltip" data-tooltip="Use a reputable domain. Avoid free email providers.">From Email</label>
<input type="email" id="from_email" name="from_email" value="<?php echo isset($from_email) ? htmlspecialchars($from_email) : ''; ?>" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="you@yourdomain.com">
</div>
<div>
<label for="subject" class="block text-sm font-medium tooltip" data-tooltip="Avoid spam words like 'free', 'win', or 'urgent'.">Subject</label>
<input type="text" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Your Email Subject">
</div>
<div>
<label for="body" class="block text-sm font-medium tooltip" data-tooltip="Write clean HTML. Avoid excessive links or images.">HTML Body</label>
<textarea id="body" name="body" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" rows="6" placeholder="<p>Your email content here</p>"><?php echo isset($body) ? htmlspecialchars($body) : ''; ?></textarea>
</div>
<div>
<label class="block text-sm font-medium">Live Preview</label>
<div id="preview" class="rounded"><?php echo isset($body) ? $body : '<p>Start typing to see the preview...</p>'; ?></div>
</div>
<div>
<label for="email_list" class="block text-sm font-medium tooltip" data-tooltip="One email per line. Use valid, opted-in addresses only.">Email List</label>
<textarea id="email_list" name="email_list" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500" rows="4" placeholder="email1@example.com
email2@example.com"><?php echo isset($email_list) ? htmlspecialchars($email_list) : ''; ?></textarea>
</div>
<div>
<label for="email_file" class="block text-sm font-medium tooltip" data-tooltip="Upload a .txt file with one email per line.">Upload Email List (.txt)</label>
<input type="file" id="email_file" name="email_file" accept=".txt" class="w-full p-2 bg-gray-700 rounded border border-gray-600 focus:outline-none focus:ring-2 focus:ring-red-500">
</div>
<button type="submit" id="sendButton" class="w-full bg-red-500 hover:bg-red-600 text-white p-3 rounded font-semibold transition">Send Emails</button>
</form>

<div id="progress" class="mt-4 hidden">
<div class="bg-gray-700 rounded h-2">
<div id="progressBar" class="bg-red-500 h-2 rounded" style="width: 0%"></div>
</div>
<p id="progressText" class="text-sm mt-2">Preparing to send...</p>
<div id="sendLog" class="mt-2"></div>
</div>

<p class="text-xs text-gray-400 mt-6 text-center">Note: Ensure your server's mail configuration (e.g., Sendmail/Postfix) is set up correctly. Use a domain with DKIM/SPF records for better deliverability.</p>
</div>

<script>
// Live preview for HTML body
const bodyInput = document.getElementById('body');
const preview = document.getElementById('preview');
bodyInput.addEventListener('input', () => {
    preview.innerHTML = bodyInput.value || '<p>Start typing to see the preview...</p>';
});

// Form validation and AJAX sending
const form = document.getElementById('mailerForm');
const sendButton = document.getElementById('sendButton');
const progress = document.getElementById('progress');
const progressBar = document.getElementById('progressBar');
const progressText = document.getElementById('progressText');
const sendLog = document.getElementById('sendLog');

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Validate form
    const name = document.getElementById('name').value.trim();
    const fromEmail = document.getElementById('from_email').value.trim();
    const subject = document.getElementById('subject').value.trim();
    const body = document.getElementById('body').value.trim();
    const emailList = document.getElementById('email_list').value.trim();
    const emailFile = document.getElementById('email_file').files[0];
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!name || !fromEmail || !subject || !body) {
        alert('All fields are required.');
        return;
    }

    if (!emailRegex.test(fromEmail)) {
        alert('Please enter a valid from email address.');
        return;
    }

    if (!emailList && !emailFile) {
        alert('Please provide an email list or upload a file.');
        return;
    }

    if (emailList) {
        const emails = emailList.split('\n').map(email => email.trim()).filter(email => email);
        for (const email of emails) {
            if (!emailRegex.test(email)) {
                alert(`Invalid email address: ${email}`);
                return;
            }
        }
    }

    // Submit form to store data
    const formData = new FormData(form);
    await fetch('', {
        method: 'POST',
        body: formData
    });

    // Start sending emails via AJAX
    progress.classList.remove('hidden');
    sendButton.disabled = true;
    sendLog.innerHTML = '';
progressText.textContent = 'Starting email sending...';

// Fetch emails from session
const response = await fetch('send.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'get_emails' })
});
const data = await response.json();
const emails = data.emails || [];
const total = emails.length;

if (total === 0) {
    progressText.textContent = 'No emails to send.';
sendButton.disabled = false;
return;
}

let sentCount = 0;
for (let i = 0; i < total; i++) {
    const email = emails[i];
    const sendResponse = await fetch('send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send', index: i })
    });
    const result = await sendResponse.json();

    const logEntry = document.createElement('p');
    logEntry.textContent = `${new Date().toLocaleTimeString()} - ${email}: ${result.success ? 'Sent' : 'Failed'}`;
    logEntry.className = result.success ? 'text-green-400' : 'text-red-400';
sendLog.appendChild(logEntry);
sendLog.scrollTop = sendLog.scrollHeight;

sentCount += result.success ? 1 : 0;
const progressPercent = ((i + 1) / total) * 100;
progressBar.style.width = `${progressPercent}%`;
progressText.textContent = `Sending ${i + 1}/${total} emails...`;
}

progressText.textContent = `Completed! Sent ${sentCount}/${total} emails.`;
sendButton.disabled = false;
});
</script>
</body>
</html>
<?php
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
