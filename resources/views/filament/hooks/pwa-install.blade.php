<div x-data="{
    deferredPrompt: null,
    showInstallButton: false,
    init() {
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later.
            this.deferredPrompt = e;
            // Update UI notify the user they can install the PWA
            this.showInstallButton = true;
            console.log('PWA Install Prompt captured');
        });
        
        window.addEventListener('appinstalled', () => {
             this.showInstallButton = false;
             this.deferredPrompt = null;
             console.log('PWA Installed');
        });
    },
    async install() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log(`User response to the install prompt: ${outcome}`);
            this.deferredPrompt = null;
            this.showInstallButton = false;
        }
    }
}" x-show="showInstallButton" style="display: none;" class="px-6 py-3">
    <button @click="install()" type="button"
        class="flex w-full items-center gap-x-3 rounded-lg px-2 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5 bg-primary-600/10 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400 ring-1 ring-inset ring-primary-600/20 dark:ring-primary-400/30">
        <svg class="h-6 w-6 shrink-0 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        <span class="flex-1 text-left">
            Instalar App
        </span>
    </button>
</div>