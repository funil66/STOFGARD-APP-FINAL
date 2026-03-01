<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Expirado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center font-sans"
    style="font-family: Inter, sans-serif;">
    <div class="text-center max-w-md mx-auto px-4">
        <div class="text-6xl mb-4">⏱️</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Link Expirado ou Inválido</h1>
        <p class="text-gray-500 mb-6">
            {{ session('erro') ?? 'Este link já foi utilizado ou expirou.' }}
        </p>
        <p class="text-gray-500 text-sm">
            Entre em contato com a empresa para solicitar um novo link de acesso via WhatsApp.
        </p>
    </div>
</body>

</html>