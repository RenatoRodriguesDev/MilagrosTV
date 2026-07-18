<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar password — MilagrosTV</title>
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
            <h1 class="text-xl font-bold text-white mb-2">Recuperar password</h1>
            <p class="text-gray-400 text-sm mb-6">Insere o teu email e enviamos um link para definires uma nova password.</p>

            @if(session('status'))
                <div class="mb-4 p-3 bg-green-900/40 border border-green-700/50 rounded-lg text-green-300 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-900/40 border border-red-700/50 rounded-lg text-red-300 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-red-500 transition text-sm">
                </div>
                <button type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition text-sm">
                    Enviar link de recuperação
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                <a href="{{ route('login') }}" class="text-red-400 hover:text-red-300 transition">← Voltar ao login</a>
            </p>
        </div>
    </div>
</body>
</html>