<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
    // Helper to initialize ReDoc (fallback) into the #swagger-ui container
    function initReDoc() {
        var container = document.getElementById('swagger-ui');
        if (!container) return;
        container.innerHTML = '<redoc spec-url="/docs/openapi.yaml"></redoc>';
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/redoc@next/bundles/redoc.standalone.js';
        document.body.appendChild(s);
    }

    window.onload = function () {
        // Use a relative path so Swagger UI fetches the spec from the same origin
        try {
            if (typeof SwaggerUIBundle !== 'undefined') {
                SwaggerUIBundle({
                    url: '/docs/openapi.yaml',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    docExpansion: 'list',
                    persistAuthorization: true,
                    // Ensure API requests from the UI ask for JSON so Laravel returns JSON errors
                    requestInterceptor: (req) => {
                        req.headers = req.headers || {};
                        req.headers['Accept'] = 'application/json';
                        return req;
                    },
                });
            } else {
                // Swagger bundle not available; fallback to ReDoc
                initReDoc();
            }
        } catch (err) {
            // If initialization fails, fallback to ReDoc
            console.error('Swagger UI failed to initialize, falling back to ReDoc:', err);
            initReDoc();
        }
    };
</script>
</body>
</html>
