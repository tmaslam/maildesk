{{-- Installable app (PWA): home-screen icon, standalone window, colours. --}}
<link rel="manifest" href="/manifest.webmanifest?v={{ @filemtime(public_path('manifest.webmanifest')) ?: 1 }}">
<meta name="theme-color" content="#f6f8fc">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="MailDesk">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
