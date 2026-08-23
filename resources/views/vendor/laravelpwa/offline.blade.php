<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#c74717">
    <title>Sin conexión | {{ config('app.name', 'CepreUNA') }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Trebuchet MS", "Gill Sans", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: grid;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            padding:
                max(1.25rem, env(safe-area-inset-top))
                max(1.25rem, env(safe-area-inset-right))
                max(1.25rem, env(safe-area-inset-bottom))
                max(1.25rem, env(safe-area-inset-left));
            place-items: center;
            color: #263442;
            background:
                radial-gradient(circle at 12% 10%, rgba(199, 71, 23, 0.2), transparent 19rem),
                radial-gradient(circle at 88% 90%, rgba(11, 93, 135, 0.14), transparent 22rem),
                #f4f6f8;
        }

        main {
            width: min(100%, 31rem);
            padding: clamp(1.5rem, 7vw, 2.75rem);
            text-align: center;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #dce3e9;
            border-radius: 18px;
            box-shadow: 0 22px 60px rgba(24, 39, 56, 0.16);
        }

        img {
            width: 78px;
            height: 78px;
            padding: 8px;
            object-fit: contain;
            background: #fff1eb;
            border-radius: 20px;
        }

        .eyebrow {
            margin: 1.25rem 0 0.35rem;
            color: #c74717;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: clamp(1.8rem, 8vw, 2.4rem);
            line-height: 1.08;
        }

        p {
            margin: 1rem auto 1.5rem;
            color: #657384;
            line-height: 1.6;
        }

        button {
            min-height: 46px;
            padding: 0.75rem 1.2rem;
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            background: #c74717;
            border: 0;
            border-radius: 9px;
            box-shadow: 0 7px 18px rgba(199, 71, 23, 0.24);
        }

        button:focus-visible {
            outline: 3px solid rgba(199, 71, 23, 0.26);
            outline-offset: 3px;
        }

        #status {
            min-height: 1.25rem;
            margin: 0.9rem 0 0;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>
    <main>
        <img src="/images/icons/icon-192x192.png" alt="Logo de CepreUNA">
        <div class="eyebrow">Modo sin conexión</div>
        <h1>Volvamos a conectarnos</h1>
        <p>No pudimos llegar a CepreUNA. Revise su conexión a internet y vuelva a intentarlo.</p>
        <button type="button" onclick="retryConnection()">Intentar nuevamente</button>
        <p id="status" role="status" aria-live="polite"></p>
    </main>
    <script>
        function retryConnection() {
            const status = document.getElementById("status");
            status.textContent = "Comprobando conexión...";
            window.location.reload();
        }

        window.addEventListener("online", retryConnection, { once: true });
    </script>
</body>
</html>
