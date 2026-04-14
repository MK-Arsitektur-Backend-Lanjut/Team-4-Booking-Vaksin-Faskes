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
    window.onload = function () {
        SwaggerUIBundle({
            url: "{{ route('docs.openapi') }}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            docExpansion: 'list',
            persistAuthorization: true,
        });
    };
</script>
</body>
</html>
