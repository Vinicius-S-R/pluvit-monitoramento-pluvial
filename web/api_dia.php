<?php
// ============================================
// API: Retorna as 24 horas de um dia específico
// ============================================
header('Content-Type: application/json; charset=utf-8');

$servidor = "localhost"; $usuario = "root"; $senha = ""; $banco = "pluvit";
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    echo json_encode([]);
    exit;
}

$cidade = $_GET['cidade'] ?? '';
$data   = $_GET['data']   ?? '';

if (empty($cidade) || empty($data)) {
    echo json_encode([]);
    exit;
}

$cidade_esc = $conexao->real_escape_string($cidade);
$data_esc   = $conexao->real_escape_string($data);

$sql = "SELECT hora_registro, chuva_mm
        FROM historico_chuvas
        WHERE estado = 'SP'
          AND estacao = '$cidade_esc'
          AND data_registro = '$data_esc'
        ORDER BY hora_registro ASC";

$resultado = $conexao->query($sql);
$horas = [];
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $horas[] = $row;
    }
}

echo json_encode($horas);