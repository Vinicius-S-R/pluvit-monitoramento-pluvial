<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

// ============================================
// CONEXÃO COM O BANCO
// ============================================
$servidor = "localhost"; $usuario = "root"; $senha = ""; $banco = "pluvit";
$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) die("Falha: " . $conexao->connect_error);

// Busca os logs
$sql = "SELECT * FROM logs_download ORDER BY data_hora DESC LIMIT 500";
$resultado = $conexao->query($sql);
$logs = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
$total = count($logs);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Pluvit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; transition: all 0.3s ease; }
        body.dark-mode { background-color: #0f172a; color: #e5e7eb; }
        body.dark-mode .bg-custom-card { background-color: #1e293b; }
        body.dark-mode .sidebar-item:hover { background-color: rgba(255,255,255,0.06); }
        body:not(.dark-mode) { background-color: #f1f5f9; color: #1e293b; }
        body:not(.dark-mode) .bg-custom-card { background-color: #ffffff; border-color: #e2e8f0; }
        body:not(.dark-mode) .text-slate-100,
        body:not(.dark-mode) .text-slate-200,
        body:not(.dark-mode) .text-slate-300,
        body:not(.dark-mode) .text-slate-400,
        body:not(.dark-mode) .text-slate-500 { color: #334155 !important; }
        body:not(.dark-mode) .border-slate-800 { border-color: #e2e8f0 !important; }
        body:not(.dark-mode) .bg-slate-900\/60 { background-color: #f8fafc !important; }
        .scroll-custom::-webkit-scrollbar { width: 6px; }
        .scroll-custom::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    </style>
</head>
<body id="body" class="dark-mode antialiased min-h-screen flex flex-col md:flex-row">

<button id="toggleTheme" class="fixed top-4 right-4 bg-slate-800 hover:bg-slate-700 px-3 py-2 rounded-lg shadow text-sm z-50 transition-all">☀️</button>

<!-- SIDEBAR -->
<div class="w-full md:w-64 bg-custom-card border-r border-slate-800 flex flex-col md:h-screen md:sticky md:top-0 shadow-xl z-20">
    <div class="p-6 border-b border-slate-800 flex items-center">
        <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center shadow-lg">
            <i class="fas fa-user text-white"></i>
        </div>
        <div class="ml-3">
            <h2 class="font-bold text-slate-200 text-sm leading-tight"><?php echo htmlspecialchars($usuario_nome); ?></h2>
            <span class="text-xs text-slate-400 font-medium">Engenheiro</span>
        </div>
    </div>
    <nav class="p-4 flex-grow space-y-1">
        <a href="menu.html" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-home mr-4 text-slate-400 w-5 text-center"></i>
            <span class="font-medium text-sm">Início</span>
        </a>
        <a href="buscar_historico.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-satellite-dish mr-4 text-blue-500 w-5 text-center"></i>
            <span class="font-medium text-sm">Histórico INMET</span>
        </a>
        <a href="buscar_historico_cge.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-city mr-4 text-purple-500 w-5 text-center"></i>
            <span class="font-medium text-sm">Histórico CGE</span>
        </a>
        <a href="movimentacao.php" class="flex items-center p-3 bg-slate-800 text-green-400 rounded-xl transition-all">
            <i class="fas fa-history mr-4 text-green-500 w-5 text-center"></i>
            <span class="font-semibold text-sm">Movimentações</span>
        </a>
        <a href="usuarios.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-users mr-4 text-orange-500 w-5 text-center"></i>
            <span class="font-medium text-sm">Usuários</span>
        </a>
    </nav>
    <div class="p-4 border-t border-slate-800">
        <a href="logout.php" class="flex items-center p-3 text-red-400 hover:bg-red-950/20 rounded-xl transition-all">
            <i class="fas fa-sign-out-alt mr-4 w-5 text-center"></i>
            <span class="font-semibold text-sm">Sair</span>
        </a>
    </div>
</div>

<!-- CONTEÚDO -->
<div class="flex-grow p-6 md:p-8 max-w-[1400px] w-full mx-auto">

    <header class="border-b border-slate-800 pb-5 mb-6">
        <h1 class="text-2xl font-bold text-slate-100 flex items-center">
            <i class="fas fa-history text-green-500 mr-3"></i>
            Movimentações — Quem baixou o quê
        </h1>
        <p class="text-xs text-slate-500 mt-1">
            <i class="fas fa-info-circle mr-1"></i>
            Histórico de downloads (CSV e Relatórios) do INMET e CGE
        </p>
    </header>

    <!-- TABELA -->
    <div class="bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
            <i class="fas fa-list text-green-500 mr-2"></i>
            Registros
            <span class="ml-2 text-[10px] font-normal text-slate-500">
                (<?php echo $total; ?> downloads registrados)
            </span>
        </h3>

        <div class="overflow-x-auto rounded-xl border border-slate-800 scroll-custom" style="max-height: 700px;">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-slate-900 z-10">
                    <tr class="text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-800">
                        <th class="p-3">Data / Hora</th>
                        <th class="p-3">Usuário</th>
                        <th class="p-3">O que baixou</th>
                        <th class="p-3">Fonte</th>
                        <th class="p-3">Local / Filtro</th>
                        <th class="p-3">Período</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-xs">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-500">
                                <i class="fas fa-folder-open text-2xl mb-2 block text-slate-700"></i>
                                Nenhum download registrado ainda.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="p-3 text-slate-100 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($log['data_hora'])); ?>
                                </td>
                                <td class="p-3 text-slate-300 font-medium">
                                    <?php echo htmlspecialchars($log['usuario']); ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($log['tipo'] === 'CSV'): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 bg-green-950/50 text-green-400 text-[10px] font-bold rounded-full border border-green-900/60">
                                            📄 CSV
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 bg-blue-950/50 text-blue-400 text-[10px] font-bold rounded-full border border-blue-900/60">
                                            📊 Relatório
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($log['fonte'] === 'INMET'): ?>
                                        <span class="text-blue-400 font-bold text-[11px]">🛰️ INMET</span>
                                    <?php else: ?>
                                        <span class="text-purple-400 font-bold text-[11px]">🏙️ CGE</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-slate-400 text-[11px]">
                                    <?php echo htmlspecialchars($log['local'] ?: 'Todas as regiões'); ?>
                                </td>
                                <td class="p-3 text-slate-500 text-[10px] whitespace-nowrap">
                                    <?php echo htmlspecialchars($log['periodo'] ?: '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
var bodyEl = document.getElementById('body');
var btnTheme = document.getElementById('toggleTheme');
if (localStorage.getItem('tema') === 'light') {
    bodyEl.classList.remove('dark-mode');
    btnTheme.textContent = '🌙';
} else {
    bodyEl.classList.add('dark-mode');
    btnTheme.textContent = '☀️';
}
btnTheme.addEventListener('click', function() {
    bodyEl.classList.toggle('dark-mode');
    var isDark = bodyEl.classList.contains('dark-mode');
    btnTheme.textContent = isDark ? '☀️' : '🌙';
    localStorage.setItem('tema', isDark ? 'dark' : 'light');
});
</script>

</body>
</html>