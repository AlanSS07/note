/**
 * Retorna o total vendido de um produto do estoque
 */
function totalVendidoEstoque($pdo, $id_estoque) {
    $stmt = $pdo->prepare("SELECT SUM(quantidade_vendida) FROM vendas_estoque WHERE id_estoque = :id");
    $stmt->execute([':id' => $id_estoque]);
    return (int)$stmt->fetchColumn();
}

/**
 * Retorna o restante disponível de um produto do estoque
 */
function restanteEstoque($pdo, $id_estoque) {
    $stmt = $pdo->prepare("SELECT quantidade FROM estoque WHERE id_estoque = :id");
    $stmt->execute([':id' => $id_estoque]);
    $qtd_total = (int)$stmt->fetchColumn();
    $vendido = totalVendidoEstoque($pdo, $id_estoque);
    return $qtd_total - $vendido;
}

/**
 * Retorna o histórico de vendas de um produto do estoque
 */
function historicoVendasEstoque($pdo, $id_estoque) {
    $stmt = $pdo->prepare("SELECT quantidade_vendida, data_venda FROM vendas_estoque WHERE id_estoque = :id ORDER BY data_venda DESC");
    $stmt->execute([':id' => $id_estoque]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
<?php
/**
 * Biblioteca de Funções Utilitárias do Sistema
 * Nota: Não incluímos session.php aqui para permitir o uso em scripts variados.
 */

/**
 * Trata strings para o padrão do sistema (Maiúsculas e sem espaços inúteis)
 * @param string|null $texto
 * @return string
 */
function padronizarTexto($texto) {
    if ($texto === null) {
        return '';
    }
    // trim remove espaços extras no início e fim
    // mb_strtoupper garante que caracteres como 'maçã' virem 'MAÇÃ'
    return mb_strtoupper(trim($texto), 'UTF-8');
}

/**
 * Formata valores monetários para exibição
 */
function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

/**
 * Formata data do banco (Y-m-d) para brasileiro (d/m/Y)
 */
function formatarDataBR($data) {
    if (!$data) return '---';
    return date('d/m/Y', strtotime($data));
}
?>