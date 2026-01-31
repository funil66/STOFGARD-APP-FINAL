import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    darkMode: 'class', // Enable class-based dark mode (required for .dark selector)
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
