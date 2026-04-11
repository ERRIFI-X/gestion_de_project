<?php

require_once __DIR__ . '/../models/Sql.php';

class Pack
{
    private $sql;

    public function __construct()
    {
        $this->sql = new Sql();
    }

    // ─────────────────────────────────────────────
    //  PACKS CRUD
    // ─────────────────────────────────────────────

    /** GET all packs — includes services */
    public function getAll()
    {
        $query  = "SELECT * FROM packs ORDER BY created_at DESC";
        $packs  = $this->sql->getAll($query);

        // Attach services to each pack
        foreach ($packs as &$pack) {
            $pack['services'] = $this->getServicesByPack((int)$pack['id']);
        }

        return $packs;
    }

    /** GET single pack — with its services */
    public function show($id)
    {
        $pack = $this->sql->getId(
            "SELECT * FROM packs WHERE id = :id",
            ['id' => (int)$id]
        );

        if ($pack) {
            $pack['services'] = $this->getServicesByPack((int)$pack['id']);
        }

        return $pack;
    }

    /** POST create pack (optionally with services array) */
    public function store($data)
    {
        $pdo = $this->sql->getPdo();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO packs (name, description)
                 VALUES (:name, :description)"
            );
            $stmt->execute([
                ':name'        => htmlspecialchars($data['name'] ?? 'Pack'),
                ':description' => htmlspecialchars($data['description'] ?? ''),
            ]);
            $packId = $pdo->lastInsertId();

            // Optionally create services in same request
            if (!empty($data['services']) && is_array($data['services'])) {
                foreach ($data['services'] as $service) {
                    $this->insertService($pdo, $packId, $service);
                }
            }

            $pdo->commit();
            return ['success' => true, 'id' => $packId];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** PUT update pack */
    public function update($id, $data)
    {
        $result = $this->sql->update(
            "UPDATE packs SET
                name        = :name,
                description = :description
             WHERE id = :id",
            [
                ':id'          => (int)$id,
                ':name'        => htmlspecialchars($data['name'] ?? 'Pack'),
                ':description' => htmlspecialchars($data['description'] ?? ''),
            ]
        );
        return ['success' => $result];
    }

    /** DELETE pack (cascade deletes its services) */
    public function delete($id)
    {
        return ['success' => $this->sql->delete("DELETE FROM packs WHERE id = :id", ['id' => (int)$id])];
    }

    // ─────────────────────────────────────────────
    //  PACK SERVICES CRUD
    // ─────────────────────────────────────────────

    /** GET all services belonging to a pack */
    public function getServicesByPack($pack_id)
    {
        return $this->sql->getAll(
            "SELECT * FROM pack_services WHERE pack_id = :pack_id ORDER BY id ASC",
            [':pack_id' => (int)$pack_id]
        );
    }

    /** GET single pack service */
    public function showService($service_id)
    {
        return $this->sql->getId(
            "SELECT * FROM pack_services WHERE id = :id",
            ['id' => (int)$service_id]
        );
    }

    /** POST add service to a pack */
    public function storeService($pack_id, $data)
    {
        $pdo  = $this->sql->getPdo();
        $id   = $this->insertService($pdo, $pack_id, $data);
        return ['success' => (bool)$id, 'id' => $id];
    }

    /** PUT update a pack service */
    public function updateService($service_id, $data)
    {
        $result = $this->sql->update(
            "UPDATE pack_services SET
                name        = :name,
                description = :description,
                price       = :price
             WHERE id = :id",
            [
                ':id'          => (int)$service_id,
                ':name'        => htmlspecialchars($data['name']),
                ':description' => htmlspecialchars($data['description'] ?? ''),
                ':price'       => (float)($data['price'] ?? 0),
            ]
        );
        return ['success' => $result];
    }

    /** DELETE a single service from a pack */
    public function deleteService($service_id)
    {
        return ['success' => $this->sql->delete(
            "DELETE FROM pack_services WHERE id = :id",
            ['id' => (int)$service_id]
        )];
    }

    // ─────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function insertService($pdo, $pack_id, $data)
    {
        $stmt = $pdo->prepare(
            "INSERT INTO pack_services (pack_id, name, description, price)
             VALUES (:pack_id, :name, :description, :price)"
        );
        $stmt->execute([
            ':pack_id'     => (int)$pack_id,
            ':name'        => htmlspecialchars($data['name']),
            ':description' => htmlspecialchars($data['description'] ?? ''),
            ':price'       => (float)($data['price'] ?? 0),
        ]);
        return $pdo->lastInsertId();
    }
}
