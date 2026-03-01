<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>

<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">
                    Área do Cliente </h1>
                <span
                    class="bg-green-100 text-green-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded border border-green-400">
                    Acesso Seguro
                </span>
            </div>
        </header>

        <main class="flex-1 max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 w-full">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Bem-vindo(a)!</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500 mx-auto">
                            Aqui você acompanha suas Ordens de Serviço, Orçamentos e realiza pagamentos pendentes.
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul role="list" class="divide-y divide-gray-200">
                        <li>
                            <div class="px-4 py-4 flex items-center sm:px-6">
                                <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div class="truncate">
                                        <div class="flex text-sm">
                                            <p class="font-medium text-blue-600 truncate">Ordem de Serviço #00123</p>
                                            <p class="ml-1 flex-shrink-0 font-normal text-gray-500">em Andamento</p>
                                        </div>
                                        <div class="mt-2 flex">
                                            <div class="flex items-center text-sm text-gray-500">
                                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"
                                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                    fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <p>Atualizado hoje</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex-shrink-0 sm:mt-0 sm:ml-5">
                                        <div class="flex overflow-hidden -space-x-1">
                                            <a href="#"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                Baixar PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>

</html>