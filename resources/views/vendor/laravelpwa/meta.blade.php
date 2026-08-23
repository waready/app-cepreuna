<!-- Web Application Manifest -->
<link rel="manifest" href="{{ route('laravelpwa.manifest') }}">
<meta name="theme-color" content="{{ $config['theme_color'] }}">

<!-- Android PWA metadata -->
<meta name="mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="application-name" content="{{ $config['short_name'] }}">
<link rel="icon" sizes="{{ data_get(end($config['icons']), 'sizes') }}" href="{{ data_get(end($config['icons']), 'src') }}">

<!-- iOS PWA metadata -->
<meta name="apple-mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $config['status_bar'] }}">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
<link rel="apple-touch-icon" href="{{ data_get(end($config['icons']), 'src') }}">

<link href="{{ $config['splash']['640x1136'] }}" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['750x1334'] }}" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1242x2208'] }}" media="(device-width: 621px) and (device-height: 1104px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1125x2436'] }}" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['828x1792'] }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1242x2688'] }}" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1536x2048'] }}" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1668x2224'] }}" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['1668x2388'] }}" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="{{ $config['splash']['2048x2732'] }}" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">

<meta name="msapplication-TileColor" content="{{ $config['background_color'] }}">
<meta name="msapplication-TileImage" content="{{ data_get(end($config['icons']), 'src') }}">

<script>
    (() => {
        if (!("serviceWorker" in navigator)) {
            return;
        }

        const pwaVersion = "2026.08.22.1";
        const currentCache = "cepreuna-pwa-v3";
        const cacheVersionKey = "cepreuna-pwa-cache-version";
        const reloadKey = `cepreuna-pwa-reloaded-${pwaVersion}`;
        const hadController = Boolean(navigator.serviceWorker.controller);

        const clearLegacyCachesOnce = async () => {
            if (!("caches" in window)) {
                return;
            }

            try {
                if (localStorage.getItem(cacheVersionKey) === pwaVersion) {
                    return;
                }
            } catch (error) {
                // Storage may be unavailable in private browsing; cache cleanup can still continue.
            }

            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith("pwa-") || cacheName.startsWith("cepreuna-pwa-"))
                    .filter((cacheName) => cacheName !== currentCache)
                    .map((cacheName) => caches.delete(cacheName))
            );

            try {
                localStorage.setItem(cacheVersionKey, pwaVersion);
            } catch (error) {
                // The service worker activation also removes legacy caches.
            }
        };

        navigator.serviceWorker.addEventListener("controllerchange", () => {
            if (!hadController || sessionStorage.getItem(reloadKey)) {
                return;
            }

            sessionStorage.setItem(reloadKey, "1");
            window.location.reload();
        });

        window.addEventListener("load", async () => {
            try {
                await clearLegacyCachesOnce();
                const registration = await navigator.serviceWorker.register(`/serviceworker.js?v=${pwaVersion}`, {
                    scope: "/",
                    updateViaCache: "none",
                });
                await registration.update();
                if (registration.waiting) {
                    registration.waiting.postMessage({ type: "SKIP_WAITING" });
                }
            } catch (error) {
                console.warn("No se pudo actualizar el modo PWA.", error);
            }
        });
    })();
</script>
