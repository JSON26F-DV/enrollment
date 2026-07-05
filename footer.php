<footer class="bg-black/5 border-t border-black/10 mt-auto">
    <div class="max-w-[1040px] mx-auto px-4 md:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">

            <div class="md:col-span-4">
                <div class="flex items-center gap-3 mb-4">
                    <img src="<?= url('/public/images/ncst.png') ?>" alt="NCST"
                        class="w-10 h-10 rounded-xl object-cover shadow shadow-pixs-mint/20">
                    <span class="text-lg font-black tracking-tighter uppercase">NCST</span>
                </div>
                <p class="text-sm text-black/60 font-medium leading-relaxed mb-4">
                    NCST Violation Management System — streamlining academic integrity and campus discipline tracking
                    for the National College of Science and Technology.
                </p>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-bold text-black/70">Contact Us</span>
                    <a href="mailto:support@ncst.edu.ph"
                        class="text-xs font-medium text-google-blue hover:underline">support@ncst.edu.ph</a>
                </div>
                <div class="flex items-center gap-3 mt-4">
                    <a href="#"
                        class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center text-black/60 hover:bg-black/20 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center text-black/60 hover:bg-black/20 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                        </svg>
                    </a>
                    <a href="#"
                        class="w-8 h-8 rounded-full bg-black/10 flex items-center justify-center text-black/60 hover:bg-black/20 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-2 16h-2v-6h2v6zm-1-6.891c-.607 0-1.1-.496-1.1-1.109 0-.612.492-1.109 1.1-1.109s1.1.497 1.1 1.109c0 .613-.493 1.109-1.1 1.109zM16 16h-2v-3.5c0-.973-.487-1.5-1.315-1.5-.828 0-1.315.527-1.315 1.5V16h-2v-6h2v.812c.422-.56 1.018-.812 1.79-.812 1.166 0 2.21.735 2.21 2.312V16z" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="md:col-span-2">
                <h4 class="text-sm font-black tracking-tighter uppercase mb-4">Explore</h4>
                <ul class="space-y-2">
                    <li><a href="<?= url('/src/view/guest/landing/landingpage.php') ?>"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Home</a>
                    </li>
                    <li><a href="<?= url('/src/view/guest/aboutus.php') ?>"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">About
                            Us</a></li>
                    <li><a href="<?= url('/src/view/auth/login/loginpage.php') ?>"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Login</a>
                    </li>
                    <li><a href="<?= url('/src/view/auth/register/register.php') ?>"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Register</a>
                    </li>
                </ul>
            </div>

            <div class="md:col-span-3">
                <h4 class="text-sm font-black tracking-tighter uppercase mb-4">Support</h4>
                <ul class="space-y-2">
                    <li><a href="#"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Help
                            Center</a></li>
                    <li><a href="#"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Privacy
                            Policy</a></li>
                    <li><a href="#"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Terms of
                            Service</a></li>
                    <li><a href="mailto:support@ncst.edu.ph"
                            class="text-xs font-medium text-black/60 hover:text-google-blue transition-colors">Contact
                            Support</a></li>
                </ul>
            </div>

            <div class="md:col-span-3">
                <h4 class="text-sm font-black tracking-tighter uppercase mb-4">Identity</h4>
                <div class="flex items-start gap-4">
                    <img src="<?= url('/public/images/qrcode.png') ?>" alt="QR Code"
                        class="w-24 h-24 rounded-xl border border-black/10">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-black/70">NCST Verification</p>
                        <p class="text-[10px] font-medium text-black/40">Scan to verify authenticity</p>
                        <div class="flex gap-1 mt-2">
                            <span
                                class="px-2 py-0.5 bg-google-blue/10 text-google-blue text-[10px] font-bold rounded-full">ISO
                                27001</span>
                            <span
                                class="px-2 py-0.5 bg-pixs-mint/10 text-pixs-mint text-[10px] font-bold rounded-full">GDPR</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="border-t border-black/10 mt-8 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-xs font-medium text-black/40">&copy; <?= date('Y') ?> NCST Violation Management System. All
                rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="#"
                    class="text-xs font-medium text-black/40 hover:text-google-blue transition-colors">Privacy</a>
                <a href="#" class="text-xs font-medium text-black/40 hover:text-google-blue transition-colors">Terms</a>
                <a href="#"
                    class="text-xs font-medium text-black/40 hover:text-google-blue transition-colors">Cookies</a>
            </div>
        </div>

    </div>
</footer>
</body>

</html>