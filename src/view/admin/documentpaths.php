<?php
$page_title = 'Document Paths';
require_once __DIR__ . '/sidebar.php';

$errors = [];
$success = '';

// Handle create/update path
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_path'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid session token.';
        } else {
            $id = (int)($_POST['path_id'] ?? 0);
            $path_key = trim($_POST['path_key'] ?? '');
            $path_value = trim($_POST['path_value'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($path_key)) {
                $errors[] = 'Path key is required.';
            } elseif (!preg_match('/^[a-z_]+$/', $path_key)) {
                $errors[] = 'Path key must contain only lowercase letters and underscores.';
            }

            if (empty($path_value)) {
                $errors[] = 'Path value is required.';
            } elseif ($path_value[0] !== '/') {
                $errors[] = 'Path must start with a forward slash (/)';
            }

            if (empty($errors)) {
                try {
                    if ($id > 0) {
                        // Update existing
                        $stmt = $pdo->prepare("
                            UPDATE document_paths 
                            SET path_key = ?, path_value = ?, description = ?, is_active = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$path_key, $path_value, $description, $is_active, $id]);
                        $success = 'Document path updated successfully!';
                    } else {
                        // Check if key exists
                        $stmt = $pdo->prepare("SELECT id FROM document_paths WHERE path_key = ?");
                        $stmt->execute([$path_key]);
                        if ($stmt->fetch()) {
                            $errors[] = 'Path key already exists.';
                        } else {
                            $stmt = $pdo->prepare("
                                INSERT INTO document_paths (path_key, path_value, description, is_active, created_by)
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$path_key, $path_value, $description, $is_active, $_SESSION['user_id']]);
                            $success = 'Document path created successfully!';
                        }
                    }

                    // Create directory on filesystem if no errors so far
                    if (empty($errors)) {
                        $dir_path = PROJECT_ROOT . $path_value;
                        if (!is_dir($dir_path)) {
                            if (!mkdir($dir_path, 0755, true)) {
                                $errors[] = "Path saved but failed to create directory: $path_value. Check filesystem permissions.";
                            } else {
                                // Create index.html to prevent directory listing
                                $index_file = rtrim($dir_path, '/') . '/index.html';
                                if (!file_exists($index_file)) {
                                    file_put_contents($index_file, '<!DOCTYPE html><title></title>');
                                }
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }

    // Handle delete
    if (isset($_POST['delete_path'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid session token.';
        } else {
            $delete_id = (int)($_POST['delete_id'] ?? 0);
            if ($delete_id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM document_paths WHERE id = ?");
                    $stmt->execute([$delete_id]);
                    $success = 'Document path deleted successfully!';
                } catch (PDOException $e) {
                    $errors[] = 'Cannot delete document path.';
                }
            }
        }
    }
}

// Fetch all paths
try {
    $stmt = $pdo->query("SELECT * FROM document_paths ORDER BY path_key ASC");
    $paths = $stmt->fetchAll();
} catch (Exception $e) {
    $paths = [];
}

// Get single path for edit modal
$edit_path = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM document_paths WHERE id = ?");
        $stmt->execute([(int)$_GET['edit']]);
        $edit_path = $stmt->fetch();
    } catch (Exception $e) {}
}
?>
<style>
    .mono { font-family: 'Courier New', monospace; }
</style>

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Document Paths</h1>
        <p class="text-gray-500 mt-1">Manage upload directory paths for documents and images</p>
    </div>
    <button onclick="openCreateModal()" 
            class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add New Path
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-900"><?= count($paths) ?></p>
                <p class="text-sm text-blue-700">Total Paths</p>
            </div>
        </div>
    </div>
    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-900"><?= count(array_filter($paths, fn($p) => $p['is_active'])) ?></p>
                <p class="text-sm text-green-700">Active Paths</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
        <?php foreach ($errors as $e): ?>
            <p class="text-sm font-bold text-red-600"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 rounded-xl">
        <p class="text-sm font-bold text-green-600"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($paths)): ?>
        <div class="p-8 text-center text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <p>No document paths configured yet.</p>
            <button onclick="openCreateModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                Add First Path
            </button>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Path Key</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Directory Path</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($paths as $path): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <code class="mono text-sm bg-gray-100 px-2 py-1 rounded text-blue-700 font-medium">
                                    <?= htmlspecialchars($path['path_key']) ?>
                                </code>
                            </td>
                            <td class="px-4 py-4">
                                <code class="mono text-sm text-gray-600">
                                    <?= htmlspecialchars($path['path_value']) ?>
                                </code>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= htmlspecialchars($path['description'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($path['is_active']): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= date('M d, Y', strtotime($path['updated_at'])) ?>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <button onclick='openEditModal(<?= json_encode($path) ?>)' 
                                        class="text-blue-600 hover:text-blue-800 text-sm mr-3">Edit</button>
                                <button onclick="deletePath(<?= $path['id'] ?>, '<?= htmlspecialchars(addslashes($path['path_key'])) ?>')" 
                                        class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Modal -->
<div id="pathModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-lg w-full mx-4">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-xl font-bold" id="modalTitle">Add New Path</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="path_id" id="pathId" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Path Key *</label>
                <input type="text" name="path_key" id="pathKey" required 
                       pattern="[a-z_]+" title="Lowercase letters and underscores only"
                       placeholder="e.g., student_photo"
                       class="w-full px-3 py-2 border rounded-lg mono">
                <p class="text-xs text-gray-500 mt-1">Unique identifier (lowercase letters and underscores only)</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Directory Path *</label>
                <input type="text" name="path_value" id="pathValue" required 
                       placeholder="/assets/uploads/images/"
                       class="w-full px-3 py-2 border rounded-lg mono">
                <p class="text-xs text-gray-500 mt-1">Must start with forward slash (/)</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="pathDescription" rows="2"
                          placeholder="What this path is used for..."
                          class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="pathActive" value="1" checked
                       class="w-4 h-4 text-blue-600 rounded border-gray-300">
                <label for="pathActive" class="text-sm font-medium text-gray-700">Active</label>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 border rounded-lg font-medium hover:bg-gray-50">Cancel</button>
                <button type="submit" name="save_path" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl max-w-sm w-full mx-4 p-6">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h3 class="text-lg font-bold mb-2 text-center">Delete Document Path</h3>
        <p class="text-gray-600 mb-6 text-center text-sm">Are you sure you want to delete <code class="bg-gray-100 px-1 rounded"><?= htmlspecialchars($path['path_key'] ?? '') ?></code>? This action cannot be undone.</p>
        <form method="POST" id="deleteForm" class="flex gap-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="delete_id" id="deleteIdInput">
            <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2 border rounded-lg font-medium hover:bg-gray-50">Cancel</button>
            <button type="submit" name="delete_path" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">Delete</button>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Path';
    document.getElementById('pathId').value = '';
    document.getElementById('pathKey').value = '';
    document.getElementById('pathKey').readOnly = false;
    document.getElementById('pathValue').value = '';
    document.getElementById('pathDescription').value = '';
    document.getElementById('pathActive').checked = true;
    document.getElementById('pathModal').classList.remove('hidden');
    document.getElementById('pathModal').classList.add('flex');
}

function openEditModal(path) {
    document.getElementById('modalTitle').textContent = 'Edit Path';
    document.getElementById('pathId').value = path.id;
    document.getElementById('pathKey').value = path.path_key;
    document.getElementById('pathKey').readOnly = true;
    document.getElementById('pathValue').value = path.path_value;
    document.getElementById('pathDescription').value = path.description || '';
    document.getElementById('pathActive').checked = path.is_active == 1;
    document.getElementById('pathModal').classList.remove('hidden');
    document.getElementById('pathModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('pathModal').classList.add('hidden');
    document.getElementById('pathModal').classList.remove('flex');
}

function deletePath(id, key) {
    document.getElementById('deleteIdInput').value = id;
    document.querySelector('#deleteModal p code').textContent = key;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

// Open edit modal if edit param exists
<?php if ($edit_path): ?>
openEditModal(<?= json_encode($edit_path) ?>);
<?php endif; ?>
</script>

</main>
</div>
</body>
</html>
