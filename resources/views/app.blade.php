<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <script>
            (() => {
                let theme = 'system';
                try {
                    const stored = localStorage.getItem('theme');
                    theme = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
                } catch (error) {}
                const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const dark = theme === 'dark' || (theme === 'system' && systemDark);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.dataset.theme = theme;
                document.documentElement.dataset.resolvedTheme = dark ? 'dark' : 'light';
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
