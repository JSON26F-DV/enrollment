<h2 class="text-lg font-black tracking-tighter mb-6">Contact Information</h2>
<div class="border border-black/10 rounded-2xl p-6 bg-white">
    <form method="POST" action="?tab=contact">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="update_contact" value="1">

        <div class="mb-6 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
            <label class="text-xs font-medium text-black/50">Email Address *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                placeholder="juan.delacruz@email.com">
        </div>

        <div class="mb-6 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
            <label class="text-xs font-medium text-black/50">Contact Number</label>
            <input type="tel" name="contact_number" value="<?= htmlspecialchars($applicant['contact_number'] ?? '') ?>"
                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                placeholder="09123456789">
        </div>

        <div class="mb-6 relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
            <label class="text-xs font-medium text-black/50">Home Address</label>
            <input type="text" name="home_address" value="<?= htmlspecialchars($applicant['home_address'] ?? '') ?>"
                class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                placeholder="House No., Street Name">
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                <label class="text-xs font-medium text-black/50">Province</label>
                <input type="text" name="province" value="<?= htmlspecialchars($applicant['province'] ?? '') ?>"
                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                    placeholder="Cavite">
            </div>
            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                <label class="text-xs font-medium text-black/50">City/Municipality</label>
                <input type="text" name="city" value="<?= htmlspecialchars($applicant['city'] ?? '') ?>"
                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                    placeholder="Imus">
            </div>
            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                <label class="text-xs font-medium text-black/50">Barangay</label>
                <input type="text" name="barangay" value="<?= htmlspecialchars($applicant['barangay'] ?? '') ?>"
                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                    placeholder="Bucandala">
            </div>
            <div class="relative border border-black/10 rounded-xl px-4 py-2 focus-within:border-google-blue focus-within:ring-1 focus-within:ring-google-blue transition-all bg-black/5">
                <label class="text-xs font-medium text-black/50">ZIP Code</label>
                <input type="text" name="zip_code" value="<?= htmlspecialchars($applicant['zip_code'] ?? '') ?>"
                    class="w-full bg-transparent text-sm font-medium text-black outline-none mt-1 py-1 placeholder:text-black/30"
                    placeholder="4103">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="px-6 py-2.5 text-sm font-bold text-white bg-google-blue hover:bg-google-blue-hover rounded-full transition-colors shadow-sm">
                Save Changes
            </button>
            <button type="reset"
                class="px-6 py-2.5 text-sm font-bold text-black/60 hover:text-black hover:bg-black/5 rounded-full transition-colors">
                Reset
            </button>
        </div>
    </form>
</div>
