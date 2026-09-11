<?php
require_once 'C:/xampp/htdocs/gestao_epi_api_7/config/database.php';
use Config\Database;

try {
    $db = Database::getConnection();
    $sqlItens = "SELECT 
                    i.item_id, i.entr_id, i.epi_id, e.entr_data_entrega, e.entr_status, e.entr_motivo,
                    COALESCE(i.item_epi_nome_snapshot, ep.epi_nome) as epi_nome,
                    COALESCE(i.item_epi_fabricante_snapshot, ep.epi_fabricante) as epi_fabricante,
                    COALESCE(i.item_epi_modelo_snapshot, ep.epi_modelo) as epi_modelo,
                    COALESCE(i.item_epi_ca_snapshot, ep.epi_ca) as epi_ca,
                    COALESCE(i.item_epi_validade_ca_snapshot, ep.epi_vencimento_ca) as epi_vencimento_ca,
                    COALESCE(i.item_epi_vida_util_snapshot, ep.epi_vida_util) as epi_vida_util,
                    i.item_quantidade, i.item_tamanho, i.item_status, i.item_data_devolucao,
                    COALESCE(i.item_motivo_entrega, e.entr_motivo) as item_motivo_entrega,
                    f.fun_nome, f.fun_cpf, f.fun_esocial, f.fun_departamento, f.fun_cargo,
                    u.usu_login,
                    i.item_devolucao_motivo, i.item_devolucao_condicao, i.item_devolucao_destino, i.item_devolucao_obs,
                    i.item_devolucao_vinculo_entrega_id AS entr_id_substituicao, i.item_devolucao_vinculo_item_id AS item_id_substituido, i.item_devolucao_tipo_operacao
                 FROM itens_entrega i
                 JOIN entrega_epis e ON i.entr_id = e.entr_id
                 LEFT JOIN funcionarios f ON e.fun_id = f.fun_id
                 LEFT JOIN epis ep ON i.epi_id = ep.epi_id
                 LEFT JOIN usuarios u ON e.usu_id = u.usu_id
                 LIMIT 5";

    $stmt = $db->query($sqlItens);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "SUCCESS! Returned " . count($results) . " items.\n";
    if (!empty($results)) {
        echo "Sample item keys: " . implode(", ", array_keys($results[0])) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
