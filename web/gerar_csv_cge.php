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

$estacao_para_zona = [
    'Jaçanã / Tremembé' => 'Norte', 'Santana' => 'Norte', 'Vl. Maria / Guilherme' => 'Norte',
    'Casa Verde' => 'Norte', 'Freguesia do Ó' => 'Norte', 'Perus' => 'Norte',
    'Pirituba / Jaraguá' => 'Norte', 'Bom Retiro (COMDEC)' => 'Norte',
    'Itaquera' => 'Leste', 'Itaim Paulista' => 'Leste', 'Guaianazes' => 'Leste',
    'São Mateus' => 'Leste', 'Cidade Tiradentes' => 'Leste', 'Penha' => 'Leste',
    'Ermelino Matarazzo' => 'Leste', 'Sapopemba' => 'Leste',
    'Aricanduva / Vl. Formosa' => 'Leste', 'Móoca' => 'Leste',
    'Santo Amaro' => 'Sul', 'Jabaquara' => 'Sul', 'Campo Limpo' => 'Sul',
    'Capela do Socorro' => 'Sul', 'Cidade Ademar' => 'Sul', 'M Boi Mirim' => 'Sul',
    'Parelheiros' => 'Sul', 'Vila Mariana' => 'Sul', 'Ipiranga' => 'Sul',
    'Lapa' => 'Oeste', 'Pinheiros' => 'Oeste', 'Butantã' => 'Oeste',
    'Sé' => 'Centro', 'Consolação (CGE)' => 'Centro', 'Carandirú' => 'Centro',
];

$zona_selecionada    = $_GET['zona'] ?? '';
$estacao_selecionada = $_GET['estacao'] ?? '';
$media_selecionada   = $_GET['media'] ?? '';
$data_inicio         = $_GET['data_inicio'] ?? '';
$data_fim            = $_GET['data_fim'] ?? '';

if (empty($data_inicio) || empty($data_fim)) {
    die("Escolha um período (data início e fim) antes de baixar.");
}

$where = "1=1";
if (!empty($estacao_selecionada)) {
    $where .= " AND estacao = '" . $conexao->real_escape_string($estacao_selecionada) . "'";
} elseif (!empty($media_selecionada)) {
    $where .= " AND media_zona = '" . $conexao->real_escape_string($media_selecionada) . "'";
} elseif (!empty($zona_selecionada)) {
    $est = [];
    foreach ($estacao_para_zona as $e => $z) if ($z === $zona_selecionada) $est[] = $e;
    if (!empty($est)) {
        $lista = "'" . implode("','", array_map([$conexao, 'real_escape_string'], $est)) . "'";
        $where .= " AND estacao IN ($lista)";
    }
}
$where .= " AND data_registro >= '" . $conexao->real_escape_string($data_inicio) . "'";
$where .= " AND data_registro <= '" . $conexao->real_escape_string($data_fim) . "'";

// ============================================
// GRAVA LOG DE AUDITORIA
// ============================================
$ip_log = $_SERVER['REMOTE_ADDR'] ?? '';

$local_log = 'Capital de SP';
if (!empty($estacao_selecionada)) $local_log = $estacao_selecionada;
elseif (!empty($media_selecionada)) $local_log = 'Média Zona ' . $media_selecionada;
elseif (!empty($zona_selecionada)) $local_log = 'Zona ' . $zona_selecionada;

$periodo_log = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));

$stmt_log = $conexao->prepare("INSERT INTO logs_download (usuario, data_hora, tipo, fonte, local, periodo, ip) VALUES (?, NOW(), 'CSV', 'CGE', ?, ?, ?)");
$stmt_log->bind_param("ssss", $usuario_nome, $local_log, $periodo_log, $ip_log);
$stmt_log->execute();
$stmt_log->close();

// ============================================
// NOME DO ARQUIVO
// ============================================
$sufixo = 'capital';
if (!empty($estacao_selecionada)) $sufixo = preg_replace('/[^A-Za-z0-9]/', '_', $estacao_selecionada);
elseif (!empty($media_selecionada)) $sufixo = 'media_' . $media_selecionada;
elseif (!empty($zona_selecionada)) $sufixo = 'zona_' . preg_replace('/[^A-Za-z0-9]/', '_', $zona_selecionada);

$nome_arquivo = "cge_{$sufixo}_{$data_inicio}_a_{$data_fim}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

$saida = fopen('php://output', 'w');
fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($saida, ['Data', 'Chuva (mm)', 'Codigo', 'Estacao', 'Zona', 'Status'], ';');

$sql = "SELECT data_registro, chuva_mm, codigo, estacao, media_zona
        FROM historico_chuvas_cge
        WHERE $where
        ORDER BY data_registro DESC, estacao ASC";

$resultado = $conexao->query($sql);

if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $chuva = floatval($linha['chuva_mm']);
        if ($chuva >= 10.0)      $status = 'Critico';
        elseif ($chuva > 0)      $status = 'Chuva';
        else                     $status = 'Normal';

        $zona = $linha['media_zona'];
        if (empty($zona) && isset($estacao_para_zona[$linha['estacao']])) {
            $zona = $estacao_para_zona[$linha['estacao']];
        }

        fputcsv($saida, [
            date('d/m/Y', strtotime($linha['data_registro'])),
            number_format($chuva, 1, ',', ''),
            $linha['codigo'],
            $linha['estacao'],
            $zona,
            $status
        ], ';');
    }
}

fclose($saida);
exit;