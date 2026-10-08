<?php
// ============================================
// API: Retorna os dias de um mês (CGE)
// ============================================
header('Content-Type: application/json; charset=utf-8');

$servidor = "localhost"; $usuario = "root"; $senha = ""; $banco = "pluvit";
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    echo json_encode([]);
    exit;
}

// ============================================
// MAPEAMENTO: ESTAÇÃO -> ZONA (igual ao arquivo principal)
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

$zona    = $_GET['zona'] ?? '';
$estacao = $_GET['estacao'] ?? '';
$media   = $_GET['media'] ?? '';
$mes     = $_GET['mes'] ?? '';

if (empty($mes)) {
    echo json_encode([]);
    exit;
}

$mes_esc = $conexao->real_escape_string($mes);
$where = "DATE_FORMAT(data_registro, '%Y-%m') = '$mes_esc'";

if (!empty($estacao)) {
    $where .= " AND estacao = '" . $conexao->real_escape_string($estacao) . "'";
} elseif (!empty($media)) {
    $where .= " AND media_zona = '" . $conexao->real_escape_string($media) . "'";
} elseif (!empty($zona)) {
    // Filtra por todas as estações daquela zona
    $estacoes_da_zona = [];
    foreach ($estacao_para_zona as $est => $z) {
        if ($z === $zona) $estacoes_da_zona[] = $est;
    }
    if (!empty($estacoes_da_zona)) {
        $lista = "'" . implode("','", array_map([$conexao, 'real_escape_string'], $estacoes_da_zona)) . "'";
        $where .= " AND estacao IN ($lista)";
    }
}

$sql = "SELECT 
            DAY(data_registro) as dia,
            DATE_FORMAT(data_registro, '%d/%m/%Y') as data_br,
            chuva_mm,
            estacao
        FROM historico_chuvas_cge
        WHERE $where
        ORDER BY data_registro ASC, estacao ASC";

$resultado = $conexao->query($sql);
$dias = [];
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $dias[] = $row;
    }
}

echo json_encode($dias);