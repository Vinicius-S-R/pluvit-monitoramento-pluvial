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

$stmt_log = $conexao->prepare("INSERT INTO logs_download (usuario, data_hora, tipo, fonte, local, periodo, ip) VALUES (?, NOW(), 'CSV', 'INMET', ?, ?, ?)");
$stmt_log->bind_param("ssss", $usuario_nome, $local_log, $periodo_log, $ip_log);
$stmt_log->execute();
$stmt_log->close();

// ============================================
// NOME DO ARQUIVO
// ============================================
$nome_arquivo = "chuvas_" . preg_replace('/[^A-Za-z0-9]/', '_', $cidade) . "_{$data_inicio}_a_{$data_fim}.csv";

// Headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Abre saída
$saida = fopen('php://output', 'w');

// BOM UTF-8
fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Cabeçalho
fputcsv($saida, ['Data', 'Hora', 'Chuva (mm)', 'Estacao', 'Status'], ';');

// Query
$sql = "SELECT data_registro, hora_registro, chuva_mm, estacao
        FROM historico_chuvas
        WHERE estado = 'SP'
          AND estacao = '$cidade_esc'
          AND data_registro >= '$inicio_esc'
          AND data_registro <= '$fim_esc'
        ORDER BY data_registro DESC, hora_registro DESC";

$resultado = $conexao->query($sql);

if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $chuva = floatval($linha['chuva_mm']);
        if ($chuva >= 10.0)      $status = 'Critico';
        elseif ($chuva > 0.0)    $status = 'Chuva';
        else                     $status = 'Normal';

        fputcsv($saida, [
            date('d/m/Y', strtotime($linha['data_registro'])),
            $linha['hora_registro'],
            number_format($chuva, 1, ',', ''),
            $linha['estacao'],
            $status
        ], ';');
    }
}

fclose($saida);
exit;