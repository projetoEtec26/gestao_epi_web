<?php
$itemFile = 'C:/xampp/htdocs/gestao_epi_api_7/models/ItemEntrega.php';
$itemContent = file_get_contents($itemFile);

if (strpos($itemContent, 'findByEntregaIds') === false) {
    $newMethod = '
    public function findByEntregaIds(array $entregaIds): array {
        if (empty($entregaIds)) {
            return [];
        }
        $inQuery = implode(",", array_fill(0, count($entregaIds), "?"));
        $sql = "SELECT i.*, e.epi_nome, e.epi_ca, e.epi_validade_uso_dias, e.epi_fabricante, e.epi_modelo, e.epi_origem_preco, e.epi_localizacao
                FROM itens_entrega i 
                LEFT JOIN epis e ON i.epi_id = e.epi_id
                WHERE i.entr_id IN (" . $inQuery . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($entregaIds));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $row["item_epi_nome_snapshot"] = $row["item_epi_nome_snapshot"] ?? ($row["epi_nome"] ?? "EPI");
            $row["item_epi_fabricante_snapshot"] = $row["item_epi_fabricante_snapshot"] ?? ($row["epi_fabricante"] ?? "NÃO REGISTRADO");
            $row["item_epi_ca_snapshot"] = $row["item_epi_ca_snapshot"] ?? ($row["epi_ca"] ?? "NÃO REGISTRADO");
            $row["item_epi_validade_ca_snapshot"] = $row["item_epi_validade_ca_snapshot"] ?? "NÃO REGISTRADO";
            $row["item_epi_vida_util_snapshot"] = $row["item_epi_vida_util_snapshot"] ?? ($row["epi_validade_uso_dias"] ?? "NÃO REGISTRADO");
            $row["item_epi_valor_snapshot"] = $row["item_epi_valor_snapshot"] ?? "0.00";
            $row["item_epi_descricao_snapshot"] = $row["item_epi_descricao_snapshot"] ?? "NÃO REGISTRADO";
            $row["item_epi_modelo_snapshot"] = $row["item_epi_modelo_snapshot"] ?? ($row["epi_modelo"] ?? "NÃO REGISTRADO");
            $row["item_epi_origem_preco_snapshot"] = $row["item_epi_origem_preco_snapshot"] ?? ($row["epi_origem_preco"] ?? "NÃO REGISTRADO");
            $row["item_epi_localizacao_snapshot"] = $row["item_epi_localizacao_snapshot"] ?? ($row["epi_localizacao"] ?? "NÃO REGISTRADO");

            $result[$row["entr_id"]][] = $row;
        }
        return $result;
    }
';
    $target = 'public function findByEntregaId';
    $itemContent = str_replace($target, $newMethod . '    ' . $target, $itemContent);
    file_put_contents($itemFile, $itemContent);
    echo "ItemEntrega.php atualizado com sucesso!\n";
} else {
    echo "ItemEntrega.php já possui findByEntregaIds.\n";
}

$ctrlFile = 'C:/xampp/htdocs/gestao_epi_api_7/controllers/EntregasController.php';
$ctrlContent = file_get_contents($ctrlFile);

$oldCode = '$entregas = $this->entregaModel->findAll();
        foreach ($entregas as &$entrega) {
            $entrega["itens"] = $this->itemModel->findByEntregaId((int)$entrega["entr_id"]);
        }';

$newCode = '$entregas = $this->entregaModel->findAll();
        if (!empty($entregas)) {
            $ids = array_column($entregas, "entr_id");
            $itensGrouped = $this->itemModel->findByEntregaIds($ids);
            foreach ($entregas as &$entrega) {
                $entrega["itens"] = $itensGrouped[$entrega["entr_id"]] ?? [];
            }
        }';

if (strpos($ctrlContent, 'findByEntregaIds') === false) {
    $ctrlContent = str_replace($oldCode, $newCode, $ctrlContent);
    file_put_contents($ctrlFile, $ctrlContent);
    echo "EntregasController.php atualizado com sucesso!\n";
} else {
    echo "EntregasController.php já utiliza busca otimizada.\n";
}
