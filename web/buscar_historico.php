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

// ============================================
// MAPEAMENTO IBGE -> ESTAÇÕES
// ============================================
$mapa_ibge_para_estacao = [
    '3514' => ['BARUERI', 'SAO PAULO - INTERLAGOS', 'SAO PAULO - MIRANTE'],
    '3515' => ['BARUERI', 'SAO PAULO - INTERLAGOS', 'SAO PAULO - MIRANTE'],
    '3512' => ['BERTIOGA', 'CACHOEIRA PAULISTA', 'IGUAPE', 'REGISTRO', 'SAO LUIS DO PARAITINGA', 'SAO MIGUEL ARCANJO', 'SAO SEBASTIAO', 'TAUBATE'],
    '3513' => ['BERTIOGA', 'CACHOEIRA PAULISTA', 'IGUAPE', 'REGISTRO', 'SAO LUIS DO PARAITINGA', 'SAO MIGUEL ARCANJO', 'SAO SEBASTIAO', 'TAUBATE'],
    '3501' => ['ARIRANHA', 'AVARE', 'BARRETOS', 'FRANCA', 'ITUVERAVA', 'JALES', 'JOSE BONIFACIO', 'VOTUPORANGA'],
    '3502' => ['ARIRANHA', 'AVARE', 'BARRETOS', 'FRANCA', 'ITUVERAVA', 'JALES', 'JOSE BONIFACIO', 'VOTUPORANGA'],
    '3505' => ['ARIRANHA', 'AVARE', 'BARRETOS', 'FRANCA', 'ITUVERAVA', 'JALES', 'JOSE BONIFACIO', 'VOTUPORANGA'],
    '3503' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3504' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3506' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3507' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3508' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3509' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3510' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
    '3511' => ['BARRA BONITA', 'BARRA DO TURVO', 'BAURU', 'BEBEDOURO', 'BRAGANCA PAULISTA', 'CAMPOS DO JORDAO', 'CASA BRANCA', 'DRACENA', 'IBITINGA', 'IPERO', 'ITAPEVA', 'ITAPIRA', 'LINS', 'MARILIA', 'OURINHOS', 'PIRACICABA', 'PRADOPOLIS', 'PRESIDENTE PRUDENTE', 'RANCHARIA', 'SAO CARLOS', 'SAO SIMAO', 'SOROCABA', 'TUPA', 'VALPARAISO'],
];

$nomes_regioes = [
    'Grande SP'      => ['3514', '3515'],
    'Litoral'        => ['3512', '3513'],
    'Interior Norte' => ['3501', '3502', '3505'],
    'Interior Oeste' => ['3503', '3504', '3506', '3507', '3508', '3509', '3510', '3511'],
];

// ============================================
// FILTROS
// ============================================
$regiao_ibge   = $_GET['regiao_ibge'] ?? '';
$cidade_filtro = $_GET['cidade'] ?? '';
$data_inicio   = $_GET['data_inicio'] ?? '';
$data_fim      = $_GET['data_fim'] ?? '';
$pagina        = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina    = 500;
$offset        = ($pagina - 1) * $por_pagina;

// ============================================
// LISTA DE CIDADES
// ============================================
$sql_cidades = "SELECT DISTINCT estacao FROM historico_chuvas WHERE estado = 'SP'";
if (!empty($regiao_ibge) && isset($mapa_ibge_para_estacao[$regiao_ibge])) {
    $lista = "'" . implode("','", array_map([$conexao, 'real_escape_string'], $mapa_ibge_para_estacao[$regiao_ibge])) . "'";
    $sql_cidades .= " AND estacao IN ($lista)";
}
$sql_cidades .= " ORDER BY estacao ASC";
$resultado_cidades = $conexao->query($sql_cidades);
$cidades = $resultado_cidades ? $resultado_cidades->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// DIAS DISPONÍVEIS (paginados)
// ============================================
$dias = [];
$total_dias = 0;
$total_paginas = 0;

if (!empty($cidade_filtro)) {
    $where = "estado = 'SP' AND estacao = '" . $conexao->real_escape_string($cidade_filtro) . "'";
    if (!empty($data_inicio)) $where .= " AND data_registro >= '" . $conexao->real_escape_string($data_inicio) . "'";
    if (!empty($data_fim))    $where .= " AND data_registro <= '" . $conexao->real_escape_string($data_fim) . "'";

    $r_count = $conexao->query("SELECT COUNT(DISTINCT data_registro) as t FROM historico_chuvas WHERE $where");
    $total_dias = $r_count ? $r_count->fetch_assoc()['t'] : 0;
    $total_paginas = ceil($total_dias / $por_pagina);

    $sql_dias = "SELECT data_registro,
                        COUNT(*) as total_horas,
                        SUM(CASE WHEN chuva_mm >= 10 THEN 1 ELSE 0 END) as criticos,
                        SUM(CASE WHEN chuva_mm > 0 AND chuva_mm < 10 THEN 1 ELSE 0 END) as chuvas,
                        SUM(CASE WHEN chuva_mm = 0 THEN 1 ELSE 0 END) as normais,
                        MAX(chuva_mm) as max_chuva
                 FROM historico_chuvas
                 WHERE $where
                 GROUP BY data_registro
                 ORDER BY data_registro DESC
                 LIMIT $por_pagina OFFSET $offset";
    $r_dias = $conexao->query($sql_dias);
    if ($r_dias) $dias = $r_dias->fetch_all(MYSQLI_ASSOC);
}

$nome_regiao = 'Todo o Estado';
if (!empty($regiao_ibge)) {
    foreach ($nomes_regioes as $reg => $codigos) {
        if (in_array($regiao_ibge, $codigos)) { $nome_regiao = $reg; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Pluvial - Pluvit</title>
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
        body:not(.dark-mode) .bg-slate-900\/60,
        body:not(.dark-mode) .bg-slate-950\/40,
        body:not(.dark-mode) .bg-slate-800 { background-color: #f8fafc !important; }
        .scroll-custom::-webkit-scrollbar { width: 6px; }
        .scroll-custom::-webkit-scrollbar-track { background: transparent; }
        .scroll-custom::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        .dia-expandido { background: rgba(37, 99, 235, 0.08); }
        .spinner { border: 2px solid #334155; border-top: 2px solid #3b82f6; border-radius: 50%; width: 16px; height: 16px; animation: spin 0.8s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body id="body" class="dark-mode antialiased min-h-screen flex flex-col md:flex-row">

<button id="toggleTheme" class="fixed top-4 right-4 bg-slate-800 hover:bg-slate-700 px-3 py-2 rounded-lg shadow text-sm z-50 transition-all">☀️</button>

<!-- SIDEBAR -->
<div class="w-full md:w-64 bg-custom-card border-r border-slate-800 flex flex-col md:h-screen md:sticky md:top-0 shadow-xl z-20">
    <div class="p-6 border-b border-slate-800 flex items-center">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center shadow-lg">
            <i class="fas fa-user text-white"></i>
        </div>
        <div class="ml-3">
            <h2 class="font-bold text-slate-200 text-sm leading-tight"><?php echo htmlspecialchars($usuario_nome); ?></h2>
            <span class="text-xs text-slate-400 font-medium">Engenheiro</span>
        </div>
    </div>
    <nav class="p-4 flex-grow space-y-1">
        <a href="menu.html" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-home mr-4 text-slate-400 w-5 text-center"></i><span class="font-medium text-sm">Início</span>
        </a>
        <a href="buscar_historico.php" class="flex items-center p-3 bg-slate-800 text-blue-400 rounded-xl transition-all">
            <i class="fas fa-map-marked-alt mr-4 text-blue-500 w-5 text-center"></i><span class="font-semibold text-sm">Histórico Pluvial</span>
        </a>
        <a href="movimentacao.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-exchange-alt mr-4 text-green-500 w-5 text-center"></i><span class="font-medium text-sm">Movimentações</span>
        </a>
        <a href="usuarios.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-users mr-4 text-orange-500 w-5 text-center"></i><span class="font-medium text-sm">Usuários</span>
        </a>
    </nav>
    <div class="p-4 border-t border-slate-800">
        <a href="logout.php" class="flex items-center p-3 text-red-400 hover:bg-red-950/20 rounded-xl transition-all">
            <i class="fas fa-sign-out-alt mr-4 w-5 text-center"></i><span class="font-semibold text-sm">Sair</span>
        </a>
    </div>
</div>

<!-- CONTEÚDO -->
<div class="flex-grow p-6 md:p-8 space-y-6 max-w-[1600px] w-full mx-auto">

    <header class="flex items-center justify-between border-b border-slate-800 pb-5">
        <h1 class="text-2xl font-bold text-slate-100 flex items-center">
            <i class="fas fa-cloud-showers-heavy text-blue-500 mr-3"></i>
            Monitoramento Pluvial de São Paulo
        </h1>
        <?php if (!empty($regiao_ibge) || !empty($cidade_filtro) || !empty($data_inicio)): ?>
            <a href="buscar_historico.php" class="text-xs font-semibold text-purple-400 hover:text-purple-300 flex items-center">
                <i class="fas fa-times-circle mr-1"></i> Limpar Filtros
            </a>
        <?php endif; ?>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- COLUNA 1: MAPA + BOTÕES DE REGIÃO -->
        <div class="lg:col-span-4 bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Mapa de São Paulo</h2>
            <div class="flex justify-center items-center bg-slate-950/40 rounded-xl p-4 border border-slate-800/60">
                <object id="mapa-container" data="mapa_sp.svg" type="image/svg+xml" class="w-full max-w-[420px] h-auto">
                    Seu navegador não suporta SVG.
                </object>
            </div>
            <div class="mt-3 text-center">
                <span class="text-[10px] font-semibold text-slate-500 uppercase">Região: </span>
                <span class="text-sm font-bold text-blue-400"><?php echo htmlspecialchars($nome_regiao); ?></span>
            </div>

            <!-- BOTÕES DAS 4 REGIÕES -->
            <div class="grid grid-cols-2 gap-2 mt-4">
                <?php
                $botoes = ['3514' => 'Grande SP', '3512' => 'Litoral', '3501' => 'Interior Norte', '3503' => 'Interior Oeste'];
                foreach ($botoes as $cod => $label):
                ?>
                    <a href="?regiao_ibge=<?php echo urlencode($cod); ?>"
                       class="text-[11px] text-center py-2.5 px-2 rounded-lg border transition-all
                              <?php echo ($regiao_ibge == $cod)
                                    ? 'bg-blue-600 border-blue-500 text-white font-bold'
                                    : 'bg-slate-800/50 border-slate-700 text-slate-400 hover:bg-slate-700'; ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- COLUNA 2: LISTA DE CIDADES -->
        <div class="lg:col-span-3 bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg flex flex-col">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cidades / Estações</h2>
            <p class="text-[10px] text-slate-500 mb-3 leading-tight">
                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                O INMET não possui estação em todas as cidades. As <?php echo count($cidades); ?> listadas possuem dados disponíveis (2020-2026).
            </p>

            <!-- CAMPO DE BUSCA -->
            <input type="text" id="busca-cidade" placeholder="🔍 Buscar cidade..."
                   class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 mb-3 focus:border-blue-500 focus:outline-none">

            <div class="overflow-y-auto scroll-custom flex-grow" style="max-height: 600px;" id="lista-cidades">
                <?php if (empty($cidades)): ?>
                    <p class="text-xs text-slate-500 text-center py-4">Nenhuma cidade encontrada.</p>
                <?php else: ?>
                    <?php foreach ($cidades as $cid): $nome = $cid['estacao']; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['cidade' => $nome, 'pagina' => 1])); ?>"
                           data-nome="<?php echo strtolower(htmlspecialchars($nome)); ?>"
                           class="cidade-item block text-xs py-2 px-3 mb-1 rounded-lg transition-all
                                  <?php echo ($cidade_filtro == $nome)
                                        ? 'bg-blue-600 text-white font-bold'
                                        : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200'; ?>">
                            <i class="fas fa-map-pin mr-2 text-[10px]"></i><?php echo htmlspecialchars($nome); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLUNA 3: FILTROS + TABELA DE DIAS -->
        <div class="lg:col-span-5 space-y-4">

            <!-- FILTRO DE PERÍODO -->
            <div class="bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                    <i class="fas fa-filter text-purple-500 mr-2"></i>Filtro de Período
                </h2>
                <form method="GET" class="grid grid-cols-2 gap-3">
                    <input type="hidden" name="regiao_ibge" value="<?php echo htmlspecialchars($regiao_ibge); ?>">
                    <input type="hidden" name="cidade" value="<?php echo htmlspecialchars($cidade_filtro); ?>">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Data Início</label>
                        <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>"
                               class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Data Fim</label>
                        <input type="date" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>"
                               class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="col-span-2 flex gap-2">
                        <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg text-xs transition-all">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                        <a href="?regiao_ibge=<?php echo urlencode($regiao_ibge); ?>&cidade=<?php echo urlencode($cidade_filtro); ?>"
                           class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-lg text-xs transition-all">
                            <i class="fas fa-eraser mr-1"></i> Limpar
                        </a>
                    </div>
                </form>

                <?php if (!empty($cidade_filtro) && !empty($data_inicio) && !empty($data_fim)): ?>
                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <a href="gerar_csv.php?<?php echo http_build_query($_GET); ?>"
                           class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-xs text-center transition-all">
                            <i class="fas fa-file-csv mr-1"></i> Baixar CSV
                        </a>
                        <a href="gerar_relatorio.php?<?php echo http_build_query($_GET); ?>" target="_blank"
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-xs text-center transition-all">
                            <i class="fas fa-chart-line mr-1"></i> Baixar Relatório
                        </a>
                    </div>
                <?php elseif (!empty($cidade_filtro)): ?>
                    <p class="text-[10px] text-amber-400 mt-3 text-center">
                        <i class="fas fa-info-circle mr-1"></i>Escolha um período para liberar os botões de download.
                    </p>
                <?php endif; ?>
            </div>

            <!-- TABELA DE DIAS EXPANSÍVEIS -->
            <div class="bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center">
                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                    Registros por Dia
                    <span class="ml-2 text-[10px] font-normal text-slate-500 normal-case">
                        (<?php echo $total_dias; ?> dias encontrados)
                    </span>
                </h3>

                <div class="overflow-y-auto scroll-custom rounded-xl border border-slate-800" style="max-height: 550px;">
                    <?php if (empty($dias)): ?>
                        <p class="p-12 text-center text-slate-500 text-xs">
                            <i class="fas fa-folder-open text-2xl mb-2 block text-slate-700"></i>
                            <?php if (empty($cidade_filtro)): ?>
                                Selecione uma região no mapa ou uma cidade na lista.
                            <?php else: ?>
                                Nenhum registro encontrado para esta cidade.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($dias as $dia): ?>
                            <div class="dia-container border-b border-slate-800/60" data-data="<?php echo $dia['data_registro']; ?>">
                                <button type="button" class="dia-header w-full flex items-center justify-between p-3 hover:bg-slate-800/40 transition-all text-left">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-chevron-right text-slate-500 text-[10px] transition-transform icone-seta"></i>
                                        <span class="text-sm font-bold text-slate-100">
                                            📅 <?php echo date('d/m/Y', strtotime($dia['data_registro'])); ?>
                                        </span>
                                        <span class="text-[10px] text-slate-500">(<?php echo $dia['total_horas']; ?> horas)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if ($dia['criticos'] > 0): ?>
                                            <span class="text-[9px] px-2 py-0.5 bg-red-950/50 text-red-400 rounded-full border border-red-900/60 font-bold">
                                                <?php echo $dia['criticos']; ?> críticos
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($dia['chuvas'] > 0): ?>
                                            <span class="text-[9px] px-2 py-0.5 bg-amber-950/50 text-amber-400 rounded-full border border-amber-900/60 font-bold">
                                                <?php echo $dia['chuvas']; ?> chuvas
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-[10px] font-bold text-blue-400">
                                            Max: <?php echo number_format($dia['max_chuva'], 1, ',', ''); ?>mm
                                        </span>
                                    </div>
                                </button>
                                <div class="dia-conteudo hidden bg-slate-900/40 px-3 pb-3"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- PAGINAÇÃO -->
                <?php if ($total_paginas > 1): ?>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-800">
                        <div class="text-[10px] text-slate-500">
                            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                            (<?php echo $total_dias; ?> dias)
                        </div>
                        <div class="flex gap-2">
                            <?php if ($pagina > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>"
                                   class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-1.5 px-3 rounded-lg text-[10px] transition-all">
                                    <i class="fas fa-chevron-left mr-1"></i> Anterior
                                </a>
                            <?php endif; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>"
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded-lg text-[10px] transition-all">
                                    Próxima <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<!-- BLOCO 2 (JavaScript) VEM ABAIXO -->
 <!-- ============================================ -->
<!-- SCRIPTS                                       -->
<!-- ============================================ -->
<script>
// ============================================
// DARK MODE
// ============================================
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

// ============================================
// BUSCA NA LISTA DE CIDADES (filtro em tempo real)
// ============================================
var campoBusca = document.getElementById('busca-cidade');
if (campoBusca) {
    campoBusca.addEventListener('input', function() {
        var termo = this.value.toLowerCase().trim();
        document.querySelectorAll('.cidade-item').forEach(function(item) {
            var nome = item.getAttribute('data-nome');
            item.style.display = (nome.indexOf(termo) !== -1) ? 'block' : 'none';
        });
    });
}

// ============================================
// MAPA CLICÁVEL (classe C35xx -> código IBGE)
// ============================================
var urlParams = new URLSearchParams(window.location.search);
var regiaoAtual = urlParams.get('regiao_ibge') || '';

var mapa = document.getElementById('mapa-container');

if (mapa) {
    mapa.addEventListener('load', function() {
        try {
            var svgInterno = mapa.contentDocument;
            if (!svgInterno) return;

            var regioes = svgInterno.querySelectorAll('path[class^="C"]');
            if (regioes.length === 0) {
                regioes = svgInterno.querySelectorAll('path');
            }

            function pintar(el, cor) {
                el.style.setProperty('fill', cor, 'important');
                el.setAttribute('fill', cor);
            }
            function limpar(el) {
                el.style.removeProperty('fill');
                el.removeAttribute('fill');
            }
            function extrairCodigo(classAttr) {
                var match = classAttr.match(/C(\d+)/);
                return match ? match[1] : '';
            }

            regioes.forEach(function(regiao) {
                var codigo = extrairCodigo(regiao.getAttribute('class') || '');
                regiao.style.cursor = 'pointer';

                // Destaque inicial da região ativa
                if (codigo === regiaoAtual) {
                    pintar(regiao, '#2563eb');
                    regiao.style.setProperty('stroke', '#60a5fa', 'important');
                    regiao.style.setProperty('stroke-width', '2', 'important');
                }

                // Hover
                regiao.addEventListener('mouseover', function() {
                    if (extrairCodigo(this.getAttribute('class')) !== regiaoAtual) {
                        pintar(this, '#60a5fa');
                    }
                });
                regiao.addEventListener('mouseout', function() {
                    if (extrairCodigo(this.getAttribute('class')) !== regiaoAtual) {
                        limpar(this);
                    }
                });

                // Clique
                regiao.addEventListener('click', function() {
                    var codigoClicado = extrairCodigo(this.getAttribute('class'));
                    if (!codigoClicado) return;

                    regioes.forEach(function(r) {
                        limpar(r);
                        r.style.removeProperty('stroke');
                        r.style.removeProperty('stroke-width');
                    });
                    pintar(this, '#2563eb');
                    this.style.setProperty('stroke', '#60a5fa', 'important');
                    this.style.setProperty('stroke-width', '2', 'important');

                    setTimeout(function() {
                        window.location.href = "?regiao_ibge=" + encodeURIComponent(codigoClicado);
                    }, 250);
                });
            });
        } catch(e) {
            console.log('Mapa não pôde ser carregado:', e);
        }
    });
}

// ============================================
// TABELA EXPANSÍVEL — AJAX POR DIA
// ============================================
document.querySelectorAll('.dia-header').forEach(function(header) {
    header.addEventListener('click', function() {
        var container = this.closest('.dia-container');
        var conteudo  = container.querySelector('.dia-conteudo');
        var seta      = container.querySelector('.icone-seta');
        var data      = container.getAttribute('data-data');

        // Se já está aberto, fecha
        if (!conteudo.classList.contains('hidden')) {
            conteudo.classList.add('hidden');
            seta.style.transform = 'rotate(0deg)';
            container.classList.remove('dia-expandido');
            return;
        }

        // Se já foi carregado antes, só reabre
        if (conteudo.getAttribute('data-carregado') === '1') {
            conteudo.classList.remove('hidden');
            seta.style.transform = 'rotate(90deg)';
            container.classList.add('dia-expandido');
            return;
        }

        // Marca como aberto e mostra spinner
        conteudo.classList.remove('hidden');
        seta.style.transform = 'rotate(90deg)';
        container.classList.add('dia-expandido');
        conteudo.innerHTML = '<div class="flex justify-center py-4"><div class="spinner"></div></div>';

        // Pega a cidade atual da URL
        var params = new URLSearchParams(window.location.search);
        var cidade = params.get('cidade') || '';

        // Faz requisição AJAX
        fetch('api_dia.php?cidade=' + encodeURIComponent(cidade) + '&data=' + encodeURIComponent(data))
            .then(function(r) { return r.json(); })
            .then(function(horas) {
                if (!horas || horas.length === 0) {
                    conteudo.innerHTML = '<p class="text-xs text-slate-500 py-3 text-center">Nenhum registro para este dia.</p>';
                    conteudo.setAttribute('data-carregado', '1');
                    return;
                }

                var html = '<table class="w-full text-left">';
                html += '<thead><tr class="text-[9px] uppercase text-slate-500 border-b border-slate-800">';
                html += '<th class="py-1 pl-8">Hora</th><th class="py-1">Chuva</th><th class="py-1 text-center">Status</th>';
                html += '</tr></thead><tbody>';

                horas.forEach(function(h) {
                    var chuva = parseFloat(h.chuva_mm);
                    var badge = '';
                    if (chuva >= 10.0) {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-red-950/50 text-red-400 rounded-full border border-red-900/60 font-bold">⚠️ Crítico</span>';
                    } else if (chuva > 0) {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-amber-950/50 text-amber-400 rounded-full border border-amber-900/60 font-bold">🌧️ Chuva</span>';
                    } else {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-green-950/50 text-green-400 rounded-full border border-green-900/60 font-bold">✓ Normal</span>';
                    }
                    html += '<tr class="border-b border-slate-800/40 text-[11px]">';
                    html += '<td class="py-1.5 pl-8 text-slate-400">' + h.hora_registro + '</td>';
                    html += '<td class="py-1.5 font-bold text-blue-400">' + chuva.toFixed(1).replace('.', ',') + ' mm</td>';
                    html += '<td class="py-1.5 text-center">' + badge + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                conteudo.innerHTML = html;
                conteudo.setAttribute('data-carregado', '1');
            })
            .catch(function(err) {
                conteudo.innerHTML = '<p class="text-xs text-red-400 py-3 text-center">Erro ao carregar dados.</p>';
                console.log(err);
            });
    });
});
</script>

</body>
</html>