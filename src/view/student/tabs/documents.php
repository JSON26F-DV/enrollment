<h2 class="text-lg font-black tracking-tighter mb-6">Documents</h2>

<div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
    <h4 class="text-xs font-bold text-blue-800 uppercase mb-2">Instructions</h4>
    <ul class="text-sm text-blue-700 space-y-1">
        <li>• Upload a clear and readable copy of each required document.</li>
        <li>• Accepted file formats: JPG, PNG, PDF (max 5MB per file).</li>
        <li>• Make sure the document matches the selected document type.</li>
        <li>• If you uploaded the wrong file, simply upload the correct one to replace it.</li>
        <li>• If you need assistance or made an error, you may visit the Registrar's Office in person for help.</li>
    </ul>
</div>

<?php if ($applicant): ?>
    <!-- Upload Form -->
    <div class="border border-black/10 rounded-2xl p-6 bg-white mb-6">
        <h3 class="text-sm font-bold mb-4">Upload Document</h3>
        <form method="POST" enctype="multipart/form-data" action="?tab=documents" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="applicant_id" value="<?= $applicant['id'] ?>">
            <input type="hidden" name="upload_document" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                    <label class="text-xs font-medium text-black/50">Document Type</label>
                    <select name="document_type" required
                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1">
                        <option value="">Select Document</option>
                        <?php foreach ($required_docs as $key => $label): ?>
                            <?php
                            $has_doc = isset($uploaded_docs[$key]);
                            $status_class = $has_doc ? ($uploaded_docs[$key]['status'] === 'approved' ? 'text-green-600' : 'text-amber-600') : '';
                            ?>
                            <option value="<?= $key ?>" class="<?= $status_class ?>">
                                <?= $label ?>        <?= $has_doc ? ' (Replace existing)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="border border-black/10 rounded-xl px-4 py-2 bg-black/5">
                    <label class="text-xs font-medium text-black/50">Select File (Max 5MB)</label>
                    <input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.gif,.pdf"
                        class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-google-blue file:text-white hover:file:bg-google-blue-hover">
                </div>
            </div>

            <button type="submit"
                class="px-6 py-2 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors">
                Upload Document
            </button>
        </form>
    </div>

    <!-- Accountability Table -->
    <div class="border border-black/10 rounded-2xl p-6 bg-white">
        <h3 class="text-sm font-bold mb-4">Document Accountability</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-black/10">
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Document</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Status</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">File Name</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Date Submitted</th>
                        <th class="text-left py-3 px-2 text-xs font-medium text-black/40 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($required_docs as $key => $label): ?>
                        <?php
                        $doc = $uploaded_docs[$key] ?? null;
                        $status = $doc ? ($doc['status'] ?? 'submitted') : 'not_submitted';
                        ?>
                        <tr class="border-b border-black/5 hover:bg-black/5">
                            <td class="py-3 px-2 font-medium"><?= htmlspecialchars($label) ?></td>
                            <td class="py-3 px-2">
                                <?php if ($status === 'approved'): ?>
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">Approved</span>
                                <?php elseif ($status === 'submitted' || $status === 'pending'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">Pending
                                        Review</span>
                                <?php elseif ($status === 'rejected'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">Rejected</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800">Not
                                        Submitted</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-2 text-black/60">
                                <?= $doc ? htmlspecialchars($doc['file_name']) : '-' ?>
                            </td>
                            <td class="py-3 px-2 text-black/60">
                                <?= $doc ? date('M d, Y', strtotime($doc['created_at'])) : '-' ?>
                            </td>
                            <td class="py-3 px-2 text-black/60 text-xs">
                                <?= $doc && !empty($doc['notes']) ? htmlspecialchars($doc['notes']) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 p-4 bg-amber-50 rounded-xl border border-amber-200">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="text-sm font-bold text-amber-800">Need help?</p>
                <p class="text-sm text-amber-700 mt-1">
                    If you made a mistake in uploading or need assistance with your documents,
                    you may visit the <strong>Registrar's Office</strong> during office hours.
                    Our staff will be happy to assist you in person.
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="p-5 bg-amber-50 rounded-2xl border border-amber-200">
        <p class="text-sm text-amber-800">
            <strong>Note:</strong> You haven't submitted an enrollment application yet.
            <a href="<?= url('/src/view/auth/register/register.php') ?>" class="underline font-bold">Apply now</a>
        </p>
    </div>
<?php endif; ?>