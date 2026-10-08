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
// FILTROS
// ============================================
$cidade      = $_GET['cidade']      ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim    = $_GET['data_fim']    ?? '';
$regiao_ibge = $_GET['regiao_ibge'] ?? '';

if (empty($cidade) || empty($data_inicio) || empty($data_fim)) {
    die("Filtros insuficientes. Escolha cidade + período.");
}

$cidade_esc = $conexao->real_escape_string($cidade);
$inicio_esc = $conexao->real_escape_string($data_inicio);
$fim_esc    = $conexao->real_escape_string($data_fim);

// ============================================
// GRAVA LOG DE AUDITORIA
// ============================================
$ip_log = $_SERVER['REMOTE_ADDR'] ?? '';
$local_log = $cidade . (!empty($regiao_ibge) ? ' (' . $regiao_ibge . ')' : '');
$periodo_log = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));

$stmt_log = $conexao->prepare("INSERT INTO logs_download (usuario, data_hora, tipo, fonte, local, periodo, ip) VALUES (?, NOW(), 'RELATORIO', 'INMET', ?, ?, ?)");
$stmt_log->bind_param("ssss", $usuario_nome, $local_log, $periodo_log, $ip_log);
$stmt_log->execute();
$stmt_log->close();

// ============================================
// BUSCA OS DADOS
// ============================================
$sql = "SELECT data_registro, hora_registro, chuva_mm
        FROM historico_chuvas
        WHERE estado = 'SP'
          AND estacao = '$cidade_esc'
          AND data_registro >= '$inicio_esc'
          AND data_registro <= '$fim_esc'
        ORDER BY data_registro ASC, hora_registro ASC";

$resultado = $conexao->query($sql);

$dados = [];
$total_chuva = 0;
$total_criticos = 0; $total_chuvas = 0; $total_normais = 0;
$max_chuva = 0; $max_chuva_data = '';
$por_dia = [];

if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $chuva = floatval($linha['chuva_mm']);
        $data  = $linha['data_registro'];

        $dados[] = ['data' => $data, 'hora' => $linha['hora_registro'], 'chuva' => $chuva];
        $total_chuva += $chuva;

        if ($chuva >= 10.0)      $total_criticos++;
        elseif ($chuva > 0)      $total_chuvas++;
        else                     $total_normais++;

        if ($chuva > $max_chuva) {
            $max_chuva = $chuva;
            $max_chuva_data = date('d/m/Y', strtotime($data)) . ' às ' . $linha['hora_registro'];
        }

        if (!isset($por_dia[$data])) $por_dia[$data] = 0;
        $por_dia[$data] += $chuva;
    }
}

$total_registros = count($dados);
$media_chuva = $total_registros > 0 ? $total_chuva / $total_registros : 0;

$labels_dia = [];
$valores_dia = [];
foreach ($por_dia as $data => $total) {
    $labels_dia[] = date('d/m/Y', strtotime($data));
    $valores_dia[] = round($total, 2);
}

$inicio_br = date('d/m/Y', strtotime($data_inicio));
$fim_br    = date('d/m/Y', strtotime($data_fim));
$gerado_em = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório INMET - <?php echo htmlspecialchars($cidade); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f7fa; color: #1e293b; padding: 30px; line-height: 1.5; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
        .header { background: linear-gradient(135deg, #2563eb, #1e40af); color: #fff; padding: 30px; }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 13px; }
        .resumo { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 30px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .card-resumo { background: #fff; padding: 20px; border-radius: 12px; border-left: 4px solid #2563eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .card-resumo.critico { border-left-color: #dc2626; }
        .card-resumo.chuva { border-left-color: #f59e0b; }
        .card-resumo.normal { border-left-color: #16a34a; }
        .card-resumo .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 6px; }
        .card-resumo .valor { font-size: 22px; font-weight: 700; color: #1e293b; }
        .card-resumo .valor.pequeno { font-size: 14px; }
        .secao { padding: 30px; }
        .secao h2 { font-size: 16px; color: #334155; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        .graficos-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .grafico-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .grafico-box h3 { font-size: 13px; color: #64748b; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead { background: #f1f5f9; }
        th { padding: 12px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: 700; border-bottom: 2px solid #cbd5e1; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        tr:hover { background: #f8fafc; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge.critico { background: #fee2e2; color: #991b1b; }
        .badge.chuva   { background: #fef3c7; color: #92400e; }
        .badge.normal  { background: #dcfce7; color: #166534; }
        .rodape { padding: 20px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #64748b; }
        @media print { body { padding: 0; background: #fff; } .container { box-shadow: none; border-radius: 0; } .no-print { display: none !important; } .graficos-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Relatório Pluvial INMET — <?php echo htmlspecialchars($cidade); ?></h1>
        <p>Período: <?php echo $inicio_br; ?> até <?php echo $fim_br; ?> | Gerado em <?php echo $gerado_em; ?></p>
    </div>
    <div class="resumo">
        <div class="card-resumo"><div class="label">Total de Registros</div><div class="valor"><?php echo number_format($total_registros, 0, ',', '.'); ?></div></div>
        <div class="card-resumo"><div class="label">Volume Total</div><div class="valor"><?php echo number_format($total_chuva, 1, ',', '.'); ?> mm</div></div>
        <div class="card-resumo"><div class="label">Média por Hora</div><div class="valor"><?php echo number_format($media_chuva, 2, ',', '.'); ?> mm</div></div>
        <div class="card-resumo"><div class="label">Pico Máximo</div><div class="valor pequeno"><?php echo number_format($max_chuva, 1, ',', '.'); ?> mm<br><?php echo $max_chuva_data; ?></div></div>
    </div>
    <div class="secao">
        <h2>📈 Análise Gráfica</h2>
        <div class="graficos-grid">
            <div class="grafico-box"><h3>Chuva ao Longo do Tempo (mm por dia)</h3><canvas id="graficoLinhaBarras" height="120"></canvas></div>
            <div class="grafico-box"><h3>Distribuição dos Registros</h3><canvas id="graficoPizza" height="200"></canvas></div>
        </div>
    </div>
    <div class="secao" style="padding-top: 0;">
        <h2>📋 Detalhamento dos Registros (<?php echo $total_registros; ?>)</h2>
        <table>
            <thead><tr><th>Data</th><th>Hora</th><th>Chuva (mm)</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($dados as $d): ?>
                    <?php
                    $chuva = $d['chuva'];
                    if ($chuva >= 10.0)      { $status = 'Critico'; $classe = 'critico'; }
                    elseif ($chuva > 0)      { $status = 'Chuva';   $classe = 'chuva'; }
                    else                     { $status = 'Normal';  $classe = 'normal'; }
                    ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($d['data'])); ?></td>
                        <td><?php echo htmlspecialchars($d['hora']); ?></td>
                        <td><strong><?php echo number_format($chuva, 1, ',', '.'); ?> mm</strong></td>
                        <td><span class="badge <?php echo $classe; ?>"><?php echo $status; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="rodape">Relatório gerado automaticamente pelo Sistema Pluvit • Dados do INMET (2020–2026)</div>
</div>
<div class="no-print" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px;">🖨️ Imprimir / Salvar como PDF</button>
</div>
<script>
var ctx1 = document.getElementById('graficoLinhaBarras').getContext('2d');
new Chart(ctx1, {
    data: {
        labels: <?php echo json_encode($labels_dia); ?>,
        datasets: [
            { type: 'bar', label: 'Chuva por dia (mm)', data: <?php echo json_encode($valores_dia); ?>, backgroundColor: 'rgba(37, 99, 235, 0.5)', borderColor: '#2563eb', borderWidth: 1, borderRadius: 4, order: 2 },
            { type: 'line', label: 'Tendência', data: <?php echo json_encode($valores_dia); ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', borderWidth: 2, tension: 0.3, pointRadius: 2, pointBackgroundColor: '#f59e0b', fill: true, order: 1 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } }, tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' mm'; } } } },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Chuva (mm)', font: { size: 11 } }, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 0 }, title: { display: true, text: 'Data', font: { size: 11 } } } }
    }
});
var ctx2 = document.getElementById('graficoPizza').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Normal', 'Chuva', 'Crítico'],
        datasets: [{ data: [<?php echo $total_normais; ?>, <?php echo $total_chuvas; ?>, <?php echo $total_criticos; ?>], backgroundColor: ['#94a3b8', '#3b82f6', '#f97316'], borderColor: '#fff', borderWidth: 3 }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 12 } }, tooltip: { callbacks: { label: function(ctx) { var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0); var perc = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0; return ctx.label + ': ' + ctx.parsed + ' (' + perc + '%)'; } } } }
    }
});
</script>
</body>
</html>