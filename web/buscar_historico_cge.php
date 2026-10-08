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
// MAPEAMENTO: ESTAÇÃO -> ZONA
// ============================================
$estacao_para_zona = [
    'Jaçanã / Tremembé'      => 'Norte',
    'Santana'                => 'Norte',
    'Vl. Maria / Guilherme'  => 'Norte',
    'Casa Verde'             => 'Norte',
    'Freguesia do Ó'         => 'Norte',
    'Perus'                  => 'Norte',
    'Pirituba / Jaraguá'     => 'Norte',
    'Bom Retiro (COMDEC)'    => 'Norte',
    'Itaquera'               => 'Leste',
    'Itaim Paulista'         => 'Leste',
    'Guaianazes'             => 'Leste',
    'São Mateus'             => 'Leste',
    'Cidade Tiradentes'      => 'Leste',
    'Penha'                  => 'Leste',
    'Ermelino Matarazzo'     => 'Leste',
    'Sapopemba'              => 'Leste',
    'Aricanduva / Vl. Formosa' => 'Leste',
    'Móoca'                  => 'Leste',
    'Santo Amaro'            => 'Sul',
    'Jabaquara'              => 'Sul',
    'Campo Limpo'            => 'Sul',
    'Capela do Socorro'      => 'Sul',
    'Cidade Ademar'          => 'Sul',
    'M Boi Mirim'            => 'Sul',
    'Parelheiros'            => 'Sul',
    'Vila Mariana'           => 'Sul',
    'Ipiranga'               => 'Sul',
    'Lapa'                   => 'Oeste',
    'Pinheiros'              => 'Oeste',
    'Butantã'                => 'Oeste',
    'Sé'                     => 'Centro',
    'Consolação (CGE)'       => 'Centro',
    'Carandirú'              => 'Centro',
];

$medias_zonas = [
    'ZN'    => ['nome' => 'Média Zona Norte',    'zona' => 'Norte'],
    'ZL'    => ['nome' => 'Média Zona Leste',    'zona' => 'Leste'],
    'ZS'    => ['nome' => 'Média Zona Sul',      'zona' => 'Sul'],
    'ZO'    => ['nome' => 'Média Zona Oeste',    'zona' => 'Oeste'],
    'ZC'    => ['nome' => 'Média Zona Centro',   'zona' => 'Centro'],
    'TOTAL' => ['nome' => 'Média Total Capital', 'zona' => null],
];

// ============================================
// FILTROS
// ============================================
$zona_selecionada    = $_GET['zona'] ?? '';
$estacao_selecionada = $_GET['estacao'] ?? '';
$media_selecionada   = $_GET['media'] ?? '';
$data_inicio         = $_GET['data_inicio'] ?? '';
$data_fim            = $_GET['data_fim'] ?? '';
$pagina              = max(1, intval($_GET['pagina'] ?? 1));
$por_pagina          = 100;
$offset              = ($pagina - 1) * $por_pagina;

// ============================================
// LISTA DE ESTAÇÕES DA ZONA
// ============================================
$estacoes_da_zona = [];
if (!empty($zona_selecionada)) {
    foreach ($estacao_para_zona as $est => $zona) {
        if ($zona === $zona_selecionada) {
            $estacoes_da_zona[] = $est;
        }
    }
}

// ============================================
// QUERY DOS DADOS
// ============================================
$where = "1=1";
$filtro_ativo = false;

if (!empty($estacao_selecionada)) {
    $where .= " AND estacao = '" . $conexao->real_escape_string($estacao_selecionada) . "'";
    $filtro_ativo = true;
} elseif (!empty($media_selecionada) && isset($medias_zonas[$media_selecionada])) {
    $where .= " AND media_zona = '" . $conexao->real_escape_string($media_selecionada) . "'";
    $filtro_ativo = true;
} elseif (!empty($zona_selecionada) && !empty($estacoes_da_zona)) {
    $lista = "'" . implode("','", array_map([$conexao, 'real_escape_string'], $estacoes_da_zona)) . "'";
    $where .= " AND estacao IN ($lista)";
    $filtro_ativo = true;
}

if (!empty($data_inicio)) $where .= " AND data_registro >= '" . $conexao->real_escape_string($data_inicio) . "'";
if (!empty($data_fim))    $where .= " AND data_registro <= '" . $conexao->real_escape_string($data_fim) . "'";

// ============================================
// MESES DISPONÍVEIS
// ============================================
$meses = [];
$total_meses = 0;
$total_paginas = 0;

if ($filtro_ativo) {
    $r = $conexao->query("SELECT COUNT(DISTINCT DATE_FORMAT(data_registro, '%Y-%m')) as t FROM historico_chuvas_cge WHERE $where");
    $total_meses = $r ? $r->fetch_assoc()['t'] : 0;
    $total_paginas = ceil($total_meses / $por_pagina);

    $sql_meses = "SELECT 
                    DATE_FORMAT(data_registro, '%Y-%m') as mes,
                    COUNT(*) as total_dias,
                    SUM(CASE WHEN chuva_mm >= 10 THEN 1 ELSE 0 END) as criticos,
                    SUM(CASE WHEN chuva_mm > 0 AND chuva_mm < 10 THEN 1 ELSE 0 END) as chuvas,
                    MAX(chuva_mm) as max_chuva,
                    SUM(chuva_mm) as total_chuva
                  FROM historico_chuvas_cge 
                  WHERE $where 
                  GROUP BY mes 
                  ORDER BY mes DESC 
                  LIMIT $por_pagina OFFSET $offset";
    $r = $conexao->query($sql_meses);
    if ($r) $meses = $r->fetch_all(MYSQLI_ASSOC);
}

$titulo_selecao = 'Selecione uma zona no mapa';
if (!empty($estacao_selecionada)) {
    $titulo_selecao = $estacao_selecionada;
} elseif (!empty($media_selecionada) && isset($medias_zonas[$media_selecionada])) {
    $titulo_selecao = $medias_zonas[$media_selecionada]['nome'];
} elseif (!empty($zona_selecionada)) {
    $titulo_selecao = "Zona {$zona_selecionada} (todas as estações)";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Pluvial CGE - Pluvit</title>
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
        .spinner { border: 2px solid #334155; border-top: 2px solid #a855f7; border-radius: 50%; width: 16px; height: 16px; animation: spin 0.8s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .mes-expandido { background: rgba(168, 85, 247, 0.08); }
    </style>
</head>
<body id="body" class="dark-mode antialiased min-h-screen flex flex-col md:flex-row">

<button id="toggleTheme" class="fixed top-4 right-4 bg-slate-800 hover:bg-slate-700 px-3 py-2 rounded-lg shadow text-sm z-50 transition-all">☀️</button>

<!-- SIDEBAR -->
<div class="w-full md:w-64 bg-custom-card border-r border-slate-800 flex flex-col md:h-screen md:sticky md:top-0 shadow-xl z-20">
    <div class="p-6 border-b border-slate-800 flex items-center">
        <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center shadow-lg">
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
        <a href="buscar_historico_cge.php" class="flex items-center p-3 bg-slate-800 text-purple-400 rounded-xl transition-all">
            <i class="fas fa-city mr-4 text-purple-500 w-5 text-center"></i>
            <span class="font-semibold text-sm">Histórico CGE</span>
        </a>
        <a href="movimentacao.php" class="flex items-center p-3 text-slate-400 sidebar-item rounded-xl transition-all">
            <i class="fas fa-exchange-alt mr-4 text-green-500 w-5 text-center"></i>
            <span class="font-medium text-sm">Movimentações</span>
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
<div class="flex-grow p-6 md:p-8 space-y-6 max-w-[1600px] w-full mx-auto">

    <header class="flex items-center justify-between border-b border-slate-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center">
                <i class="fas fa-city text-purple-500 mr-3"></i>
                Monitoramento Pluvial da Capital (CGE)
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                Fonte: CGE-SP · Clique numa zona do mapa para ver as estações
            </p>
        </div>
        <?php if (!empty($zona_selecionada) || !empty($estacao_selecionada) || !empty($media_selecionada) || !empty($data_inicio)): ?>
            <a href="buscar_historico_cge.php" class="text-xs font-semibold text-purple-400 hover:text-purple-300 flex items-center">
                <i class="fas fa-times-circle mr-1"></i> Limpar Filtros
            </a>
        <?php endif; ?>
    </header>

    <!-- AVISO SOBRE GRANULARIDADE -->
    <div class="bg-purple-950/30 border border-purple-900/50 rounded-xl p-4 flex items-start gap-3">
        <i class="fas fa-calendar-day text-purple-400 text-xl mt-0.5"></i>
        <div class="flex-1">
            <h3 class="text-sm font-bold text-purple-300 mb-1">
                ⚠️ Atenção: dados medidos por DIA (não por hora)
            </h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Diferente do INMET (que mede a chuva <strong>a cada hora</strong>), o CGE-SP disponibiliza apenas o <strong>total acumulado por dia</strong>.
                Os valores exibidos representam <strong>quanto choveu no dia inteiro</strong>, não em um horário específico.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- COLUNA 1: MAPA -->
        <div class="lg:col-span-4 bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Mapa da Capital</h2>
            <div class="flex justify-center items-center bg-slate-950/40 rounded-xl p-3 border border-slate-800/60">
                <object id="mapa-container" data="mapa_capital_zonas.svg" type="image/svg+xml" class="w-full max-w-[420px] h-auto">
                    Seu navegador não suporta SVG.
                </object>
            </div>
            <div class="mt-3 text-center">
                <span class="text-[10px] font-semibold text-slate-500 uppercase">Selecionado: </span>
                <span class="text-sm font-bold text-purple-400"><?php echo htmlspecialchars($titulo_selecao); ?></span>
            </div>

            <div class="grid grid-cols-3 gap-1 mt-4">
                <?php foreach (['Norte','Leste','Sul','Oeste','Centro'] as $z): ?>
                    <a href="?zona=<?php echo urlencode($z); ?>"
                       class="text-[10px] text-center py-2 px-1 rounded-lg border transition-all
                              <?php echo ($zona_selecionada == $z)
                                    ? 'bg-purple-600 border-purple-500 text-white font-bold'
                                    : 'bg-slate-800/50 border-slate-700 text-slate-400 hover:bg-slate-700'; ?>">
                        <?php echo $z; ?>
                    </a>
                <?php endforeach; ?>
                <a href="buscar_historico_cge.php"
                   class="text-[10px] text-center py-2 px-1 rounded-lg border bg-slate-900 border-slate-700 text-slate-500 hover:bg-slate-800">
                    Limpar
                </a>
            </div>
        </div>

        <!-- COLUNA 2: ESTAÇÕES -->
        <div class="lg:col-span-3 bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg flex flex-col">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                <?php if ($zona_selecionada): ?>
                    Estações da Zona <?php echo htmlspecialchars($zona_selecionada); ?>
                <?php else: ?>
                    Estações / Bairros
                <?php endif; ?>
            </h2>
            <p class="text-[10px] text-purple-400 mb-3">
                <i class="fas fa-calendar-day mr-1"></i>
                Dados diários (não horários)
            </p>

            <?php if (empty($zona_selecionada) && empty($media_selecionada)): ?>
                <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">
                    <i class="fas fa-info-circle text-purple-400 mr-1"></i>
                    Clique em uma <strong>zona do mapa</strong> para ver as estações disponíveis.
                </p>
                <div class="mt-3 pt-3 border-t border-slate-800">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Médias Gerais</h3>
                    <a href="?media=TOTAL"
                       class="block text-xs py-2 px-3 rounded-lg bg-slate-800/50 border border-slate-700 text-slate-400 hover:bg-slate-700 text-center">
                        📊 Média Total da Capital
                    </a>
                </div>
            <?php else: ?>
                <input type="text" id="busca-estacao" placeholder="🔍 Buscar estação..."
                       class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 mb-3 focus:border-purple-500 focus:outline-none">

                <div class="overflow-y-auto scroll-custom flex-grow" style="max-height: 420px;">
                    <?php if (!empty($zona_selecionada)): ?>
                        <?php if (empty($estacoes_da_zona)): ?>
                            <p class="text-xs text-slate-500 text-center py-4">Nenhuma estação nesta zona.</p>
                        <?php else: ?>
                            <?php foreach ($estacoes_da_zona as $est): ?>
                                <a href="?zona=<?php echo urlencode($zona_selecionada); ?>&estacao=<?php echo urlencode($est); ?>"
                                   data-nome="<?php echo strtolower(htmlspecialchars($est)); ?>"
                                   class="estacao-item block text-xs py-2 px-3 mb-1 rounded-lg transition-all
                                          <?php echo ($estacao_selecionada == $est)
                                                ? 'bg-purple-600 text-white font-bold'
                                                : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200'; ?>">
                                    <i class="fas fa-map-pin mr-2 text-[10px]"></i>
                                    <?php echo htmlspecialchars($est); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php
                    if (!empty($zona_selecionada)):
                        $codigo_media = array_search($zona_selecionada, array_column($medias_zonas, 'zona'));
                        if ($codigo_media !== false):
                    ?>
                        <div class="mt-3 pt-3 border-t border-slate-800">
                            <a href="?zona=<?php echo urlencode($zona_selecionada); ?>&media=<?php echo urlencode($codigo_media); ?>"
                               class="block text-xs py-2 px-3 rounded-lg transition-all
                                      <?php echo ($media_selecionada == $codigo_media)
                                            ? 'bg-purple-600 text-white font-bold'
                                            : 'bg-slate-800/50 border border-slate-700 text-slate-400 hover:bg-slate-700'; ?>">
                                📊 Média da Zona <?php echo htmlspecialchars($zona_selecionada); ?> (<?php echo $codigo_media; ?>)
                            </a>
                        </div>
                    <?php endif; endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- COLUNA 3: FILTROS + TABELA -->
        <div class="lg:col-span-5 space-y-4">

            <div class="bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
                    <i class="fas fa-filter text-purple-500 mr-2"></i>Filtro de Período
                </h2>
                <form method="GET" class="grid grid-cols-2 gap-3">
                    <input type="hidden" name="zona" value="<?php echo htmlspecialchars($zona_selecionada); ?>">
                    <input type="hidden" name="estacao" value="<?php echo htmlspecialchars($estacao_selecionada); ?>">
                    <input type="hidden" name="media" value="<?php echo htmlspecialchars($media_selecionada); ?>">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Data Início</label>
                        <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>"
                               class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Data Fim</label>
                        <input type="date" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>"
                               class="w-full bg-slate-900/60 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 focus:border-purple-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg text-xs transition-all">
                            <i class="fas fa-search mr-1"></i> Buscar
                        </button>
                    </div>
                </form>

                <?php if ($filtro_ativo && !empty($data_inicio) && !empty($data_fim)): ?>
                    <div class="grid grid-cols-2 gap-2 mt-3">
                        <a href="gerar_csv_cge.php?<?php echo http_build_query($_GET); ?>"
                           class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-xs text-center transition-all">
                            <i class="fas fa-file-csv mr-1"></i> Baixar CSV
                        </a>
                        <a href="gerar_relatorio_cge.php?<?php echo http_build_query($_GET); ?>" target="_blank"
                           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-xs text-center transition-all">
                            <i class="fas fa-chart-line mr-1"></i> Baixar Relatório
                        </a>
                    </div>
                <?php elseif ($filtro_ativo): ?>
                    <p class="text-[10px] text-amber-400 mt-3 text-center">
                        <i class="fas fa-info-circle mr-1"></i>Escolha um período para liberar os botões de download.
                    </p>
                <?php endif; ?>
            </div>

            <div class="bg-custom-card border border-slate-800 p-5 rounded-2xl shadow-lg">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>
                    Registros por Mês
                    <span class="ml-2 text-[10px] font-normal text-slate-500 normal-case">
                        (<?php echo $total_meses; ?> meses · <strong class="text-purple-400">chuva acumulada por dia</strong>)
                    </span>
                </h3>

                <div class="overflow-y-auto scroll-custom rounded-xl border border-slate-800" style="max-height: 500px;">
                    <?php if (empty($meses)): ?>
                        <p class="p-12 text-center text-slate-500 text-xs">
                            <i class="fas fa-folder-open text-2xl mb-2 block text-slate-700"></i>
                            <?php if (!$filtro_ativo): ?>
                                Selecione uma zona no mapa.
                            <?php else: ?>
                                Nenhum registro encontrado.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($meses as $mes):
                            $ano_mes = $mes['mes'];
                            $ano = substr($ano_mes, 0, 4);
                            $m = substr($ano_mes, 5, 2);
                            $nomes_meses = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
                            $nome_mes = $nomes_meses[$m] . ' ' . $ano;
                        ?>
                            <div class="mes-container border-b border-slate-800/60" data-mes="<?php echo $ano_mes; ?>">
                                <button type="button" class="mes-header w-full flex items-center justify-between p-3 hover:bg-slate-800/40 transition-all text-left">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-chevron-right text-slate-500 text-[10px] transition-transform icone-seta"></i>
                                        <span class="text-sm font-bold text-slate-100">📅 <?php echo $nome_mes; ?></span>
                                        <span class="text-[10px] text-slate-500">(<?php echo $mes['total_dias']; ?> dias)</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if ($mes['criticos'] > 0): ?>
                                            <span class="text-[9px] px-2 py-0.5 bg-red-950/50 text-red-400 rounded-full border border-red-900/60 font-bold">
                                                <?php echo $mes['criticos']; ?> críticos
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-[10px] font-bold text-purple-400">
                                            Total: <?php echo number_format($mes['total_chuva'], 1, ',', ''); ?>mm
                                        </span>
                                    </div>
                                </button>
                                <div class="mes-conteudo hidden bg-slate-900/40 px-3 pb-3"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-800">
                        <div class="text-[10px] text-slate-500">
                            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                        </div>
                        <div class="flex gap-2">
                            <?php if ($pagina > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>"
                                   class="bg-slate-700 hover:bg-slate-600 text-white font-bold py-1.5 px-3 rounded-lg text-[10px]">
                                    <i class="fas fa-chevron-left mr-1"></i> Anterior
                                </a>
                            <?php endif; ?>
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>"
                                   class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1.5 px-3 rounded-lg text-[10px]">
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

<script>
// DARK MODE
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

// BUSCA NA LISTA
var campoBusca = document.getElementById('busca-estacao');
if (campoBusca) {
    campoBusca.addEventListener('input', function() {
        var termo = this.value.toLowerCase().trim();
        document.querySelectorAll('.estacao-item').forEach(function(item) {
            var nome = item.getAttribute('data-nome');
            item.style.display = (nome.indexOf(termo) !== -1) ? 'block' : 'none';
        });
    });
}

// MAPA
var urlParams = new URLSearchParams(window.location.search);
var zonaAtual = urlParams.get('zona') || '';
var mapa = document.getElementById('mapa-container');

if (mapa) {
    mapa.addEventListener('load', function() {
        try {
            var svgInterno = mapa.contentDocument;
            if (!svgInterno) return;
            var zonas = svgInterno.querySelectorAll('path.zona');

            function pintar(el, cor) {
                el.style.setProperty('fill', cor, 'important');
                el.setAttribute('fill', cor);
            }
            function limpar(el) {
                el.style.removeProperty('fill');
                el.removeAttribute('fill');
            }

            zonas.forEach(function(zona) {
                var regiao = zona.getAttribute('data-regiao') || '';
                zona.style.cursor = 'pointer';

                if (regiao === zonaAtual) {
                    pintar(zona, '#9333ea');
                    zona.style.setProperty('stroke', '#c084fc', 'important');
                    zona.style.setProperty('stroke-width', '2.5', 'important');
                }

                zona.addEventListener('mouseover', function() {
                    if (this.getAttribute('data-regiao') !== zonaAtual) pintar(this, '#a855f7');
                });
                zona.addEventListener('mouseout', function() {
                    if (this.getAttribute('data-regiao') !== zonaAtual) limpar(this);
                });
                zona.addEventListener('click', function() {
                    var regiaoClicada = this.getAttribute('data-regiao');
                    if (!regiaoClicada) return;
                    zonas.forEach(function(z) {
                        limpar(z);
                        z.style.removeProperty('stroke');
                        z.style.removeProperty('stroke-width');
                    });
                    pintar(this, '#9333ea');
                    this.style.setProperty('stroke', '#c084fc', 'important');
                    this.style.setProperty('stroke-width', '2.5', 'important');
                    setTimeout(function() {
                        window.location.href = "?zona=" + encodeURIComponent(regiaoClicada);
                    }, 250);
                });
            });
        } catch(e) { console.log('Erro mapa:', e); }
    });
}

// TABELA EXPANSÍVEL
document.querySelectorAll('.mes-header').forEach(function(header) {
    header.addEventListener('click', function() {
        var container = this.closest('.mes-container');
        var conteudo  = container.querySelector('.mes-conteudo');
        var seta      = container.querySelector('.icone-seta');
        var mes       = container.getAttribute('data-mes');

        if (!conteudo.classList.contains('hidden')) {
            conteudo.classList.add('hidden');
            seta.style.transform = 'rotate(0deg)';
            container.classList.remove('mes-expandido');
            return;
        }

        if (conteudo.getAttribute('data-carregado') === '1') {
            conteudo.classList.remove('hidden');
            seta.style.transform = 'rotate(90deg)';
            container.classList.add('mes-expandido');
            return;
        }

        conteudo.classList.remove('hidden');
        seta.style.transform = 'rotate(90deg)';
        container.classList.add('mes-expandido');
        conteudo.innerHTML = '<div class="flex justify-center py-4"><div class="spinner"></div></div>';

        var params = new URLSearchParams(window.location.search);
        var zona = params.get('zona') || '';
        var estacao = params.get('estacao') || '';
        var media = params.get('media') || '';

        fetch('api_mes_cge.php?zona=' + encodeURIComponent(zona) + 
              '&estacao=' + encodeURIComponent(estacao) +
              '&media=' + encodeURIComponent(media) + 
              '&mes=' + encodeURIComponent(mes))
            .then(function(r) { return r.json(); })
            .then(function(dias) {
                if (!dias || dias.length === 0) {
                    conteudo.innerHTML = '<p class="text-xs text-slate-500 py-3 text-center">Nenhum registro para este mês.</p>';
                    conteudo.setAttribute('data-carregado', '1');
                    return;
                }

                var html = '<table class="w-full text-left">';
                html += '<thead><tr class="text-[9px] uppercase text-slate-500 border-b border-slate-800">';
                html += '<th class="py-1 pl-8">Dia</th><th class="py-1">Chuva</th><th class="py-1 text-center">Status</th>';
                html += '</tr></thead><tbody>';

                dias.forEach(function(d) {
                    var chuva = parseFloat(d.chuva_mm);
                    var badge = '';
                    if (chuva >= 10.0) {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-red-950/50 text-red-400 rounded-full border border-red-900/60 font-bold">⚠️ Crítico</span>';
                    } else if (chuva > 0) {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-amber-950/50 text-amber-400 rounded-full border border-amber-900/60 font-bold">🌧️ Chuva</span>';
                    } else {
                        badge = '<span class="text-[9px] px-2 py-0.5 bg-green-950/50 text-green-400 rounded-full border border-green-900/60 font-bold">✓ Normal</span>';
                    }
                    html += '<tr class="border-b border-slate-800/40 text-[11px]">';
                    html += '<td class="py-1.5 pl-8 text-slate-400">' + d.dia + '</td>';
                    html += '<td class="py-1.5 font-bold text-purple-400">' + chuva.toFixed(1).replace('.', ',') + ' mm</td>';
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