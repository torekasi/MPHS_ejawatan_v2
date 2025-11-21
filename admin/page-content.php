<?php
// Start session and include necessary files
session_start();
// Centralized bootstrap (logging, DB helper, global handlers)
require_once '../includes/bootstrap.php';
// Keep lightweight error logger used by this page
require_once 'includes/error_handler.php';
require_once 'auth.php';
// Do NOT include admin_logger.php directly; bootstrap auto-loads it in admin context

// Get database connection from main config
$config = require '../config.php';

// Get database connection using the merged function
try {
    $result = get_database_connection($config);
    $pdo = $result['pdo'];
    
    if (!$pdo) {
        logError('Database connection not available in page-content.php', 'DATABASE_ERROR');
        $error_message = 'Ralat sambungan ke pangkalan data: Sambungan tidak tersedia';
    }
} catch (PDOException $e) {
    logError('Database connection error in page-content.php: ' . $e->getMessage(), 'DATABASE_ERROR');
    $pdo = null;
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Initialize variables
$message = '';
$message_type = '';
$content = '';

// Check if id parameter is set
if (isset($_GET['id']) && $_GET['id'] == '1') {
    $content_key = 'pengistiharan_terms';
    $page_title = 'Pengishtiharan Permohonan';
} else {
    $content_key = 'application_instructions';
    $page_title = 'Cara Memohon';
}

// Debug - log the content key being used
error_log('Using content_key: ' . $content_key);

// Fetch current content from database
try {
    $stmt = $pdo->prepare("SELECT content_value FROM page_content WHERE content_key = ?");
    $stmt->execute([$content_key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $content = $result['content_value'];
    } else {
        // Insert default content if not exists
        if ($content_key == 'pengistiharan_terms') {
            $default_content = '<p>Saya mengaku bahawa segala maklumat yang diberikan dalam permohonan ini adalah benar dan tepat.</p>
<p>Saya faham bahawa sebarang maklumat palsu atau tidak tepat boleh menyebabkan permohonan saya ditolak atau tawaran ditarik balik.</p>
<p>Saya bersetuju untuk mematuhi semua syarat dan peraturan yang ditetapkan oleh pihak majikan.</p>';
        } else {
            $default_content = '<p>Permohonan hendaklah dibuat secara dalam talian melalui portal ini dengan mengklik butang "Mohon Sekarang" di atas.</p>
<p>Sila pastikan anda memenuhi semua syarat kelayakan sebelum memohon.</p>
<p>Hanya calon yang disenarai pendek sahaja akan dipanggil untuk temu duga.</p>';
        }
        
        $stmt = $pdo->prepare("INSERT INTO page_content (content_key, content_value) VALUES (?, ?)");
        $stmt->execute([$content_key, $default_content]);
        $content = $default_content;
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $message = 'Ralat sistem. Sila cuba sebentar lagi.';
    $message_type = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    // Validate and sanitize input
    $new_content = trim($_POST['content']);
    $form_content_key = isset($_POST['content_key']) ? $_POST['content_key'] : $content_key;
    
    // Log the content key from the form
    error_log('Form content_key: ' . $form_content_key);
    
    if (empty($new_content)) {
        $message = 'Kandungan tidak boleh kosong.';
        $message_type = 'error';
    } else {
        try {
            // Update content in database
            $stmt = $pdo->prepare("UPDATE page_content SET content_value = ? WHERE content_key = ?");
            $result = $stmt->execute([$new_content, $form_content_key]);
            
            if ($result) {
                // Log the update action
                log_admin_action('Updated page content: ' . $form_content_key, 'UPDATE', 'page_content', null, ['content_key' => $form_content_key]);
                
                $message = 'Kandungan telah dikemaskini.';
                $message_type = 'success';
                $content = $new_content;
                
                // Set session notification
                $_SESSION['notification'] = [
                    'message' => 'Kandungan telah berjaya dikemaskini.',
                    'type' => 'success'
                ];
                
                // Redirect to refresh the page and avoid form resubmission
                $redirect_url = 'page-content.php';
                if (isset($_GET['id'])) {
                    $redirect_url .= '?id=' . urlencode($_GET['id']);
                }
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $message = 'Gagal mengemaskini kandungan.';
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $message = 'Ralat sistem. Sila cuba sebentar lagi.';
            $message_type = 'error';
        }
    }
}

// Include header
include 'templates/header.php';
?>

<!-- Page Content -->
<div class="max-w-7xl mx-auto bg-white rounded-lg shadow-sm p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengurusan Kandungan Halaman</h1>
        <h2 class="text-xl text-gray-600"><?php echo htmlspecialchars($page_title); ?></h2>
    </div>

    <!-- Notification -->
    <?php if (isset($_SESSION['notification'])): ?>
        <div class="mb-6 p-4 rounded-md <?php echo $_SESSION['notification']['type'] === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <?php if ($_SESSION['notification']['type'] === 'success'): ?>
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    <?php else: ?>
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">
                        <?php echo htmlspecialchars($_SESSION['notification']['message']); ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="notification-close inline-flex rounded-md p-1.5 text-gray-500 hover:bg-gray-100 focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['notification']); ?>
    <?php endif; ?>

    <!-- Manual Message Display (if any) -->
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Content Editor -->
    <div class="bg-white rounded-lg overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4"><?php echo htmlspecialchars($page_title); ?></h2>
            <p class="text-gray-600 mb-6">
                Kandungan ini akan dipaparkan pada halaman butiran jawatan kosong. Anda boleh mengedit kandungan ini menggunakan editor di bawah.
            </p>
            
            <form method="POST" action="page-content.php<?php echo isset($_GET['id']) ? '?id=' . htmlspecialchars($_GET['id']) : ''; ?>">
                <div class="mb-6">
                    <label for="editor-container" class="block text-sm font-medium text-gray-700 mb-2">Kandungan:</label>
                    <!-- Hidden textarea to store the HTML content -->
                    <input type="hidden" id="content" name="content" value="<?php echo htmlspecialchars($content); ?>">
                    <!-- Hidden input to store the content key -->
                    <input type="hidden" name="content_key" value="<?php echo htmlspecialchars($content_key); ?>">
                    <!-- Quill editor container -->
                    <div id="editor-container" class="h-64 mb-4"></div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Preview Section -->
    <div class="mt-8 bg-white rounded-lg overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Pratonton</h2>
            <div class="bg-blue-50 rounded-lg p-6">
                <h3 class="text-xl font-bold text-blue-800 mb-4"><?php echo htmlspecialchars($page_title); ?></h3>
                <div class="prose prose-blue max-w-none requirements cara-memohon">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cara-memohon ul { list-style-type: disc; margin-left: 1.5em; }
.cara-memohon ol { list-style-type: decimal; margin-left: 1.5em; }
.cara-memohon li { margin-bottom: 0.5em; }
</style>
<!-- Include Quill CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Include Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editor
        var quill = new Quill('#editor-container', {
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'clean']
                ]
            },
            placeholder: 'Tulis kandungan di sini...',
            theme: 'snow'
        });
        
        // Set initial content
        quill.root.innerHTML = document.getElementById('content').value;
        
        // Update hidden form field before submit
        document.querySelector('form').onsubmit = function() {
            document.getElementById('content').value = quill.root.innerHTML;
            return true;
        };
        
        // Update preview when content changes
        quill.on('text-change', function() {
            // Find the preview element in the DOM
            const previewElement = document.querySelector('.cara-memohon');
            if (previewElement) {
                previewElement.innerHTML = quill.root.innerHTML;
            }
        });
        
        // Handle notification close button
        const closeButtons = document.querySelectorAll('.notification-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.p-4').remove();
            });
        });
    });
</script>

<?php
// Include footer
include 'templates/footer.php';
?>
