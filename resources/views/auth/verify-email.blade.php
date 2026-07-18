<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica o teu email — MilagrosTV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>* { font-family: 'Inter', sans-serif; } body { background-color: #0a0a0a; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2">
                <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center font-black text-white text-lg">M</div>
                <span class="font-black text-2xl tracking-tight text-white">MilagrosTV</span>
            </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-2xl p-8">
            <div class="text-center mb-6">
                <div class="w-14 h-14 bg-blue-600/20 border border-blue-500/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-white mb-2">Verifica o teu email</h1>
                <p class="text-gray-400 text-sm">Enviámos um link de verificação para o teu email. Clica no link para ativar a conta.</p>
            </div>

            @if(session('resent'))
                <div class="mb-4 p-3 bg-green-900/40 border border-green-700/50 rounded-lg text-green-300 text-sm text-center">
                    Email reenviado com sucesso!
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit"
                    class="w-full bg-white/10 hover:bg-white/20 border border-white/10 text-white font-semibold py-3 rounded-lg transition text-sm">
                    Reenviar email de verificação
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-gray-500 hover:text-gray-300 text-sm py-2 transition">
                    Sair da conta
                </button>
            </form>
        </div>
    </div>
</body>
</html>