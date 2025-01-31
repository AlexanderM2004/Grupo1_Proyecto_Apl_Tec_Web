<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Whisper {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): ?int {
        $query = "INSERT INTO susurros (usuario_id, mensaje, nivel, susurro_padre_id) 
                 VALUES (:usuario_id, :mensaje, :nivel, :susurro_padre_id) 
                 RETURNING id";
        
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'usuario_id' => $data['usuario_id'],
                'mensaje' => $data['mensaje'],
                'nivel' => $data['nivel'] ?? 0,
                'susurro_padre_id' => $data['susurro_padre_id'] ?? null
            ]);
            
            $susurroId = $stmt->fetchColumn();
            
            // Si hay etiquetas, insertarlas
            if (!empty($data['etiquetas'])) {
                foreach ($data['etiquetas'] as $etiqueta) {
                    // Primero intentamos insertar la etiqueta si no existe
                    $stmt = $this->db->prepare("
                        INSERT INTO etiquetas (nombre)
                        VALUES (:nombre)
                        ON CONFLICT (nombre) DO UPDATE SET nombre = EXCLUDED.nombre
                        RETURNING id
                    ");
                    $stmt->execute(['nombre' => $etiqueta]);
                    $etiquetaId = $stmt->fetchColumn();
                    
                    // Luego creamos la relación
                    $stmt = $this->db->prepare("
                        INSERT INTO susurros_etiquetas (susurro_id, etiqueta_id)
                        VALUES (:susurro_id, :etiqueta_id)
                    ");
                    $stmt->execute([
                        'susurro_id' => $susurroId,
                        'etiqueta_id' => $etiquetaId
                    ]);
                }
            }
            
            $this->db->commit();
            return $susurroId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRecent(int $limit = 20, int $offset = 0): array {
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            LEFT JOIN susurros_etiquetas se ON s.id = se.susurro_id
            LEFT JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE s.susurro_padre_id IS NULL
            GROUP BY s.id, u.username, u.genero
            ORDER BY s.fecha_creacion DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTrending(int $hours = 2, int $limit = 20, int $offset = 0): array {
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            LEFT JOIN susurros_etiquetas se ON s.id = se.susurro_id
            LEFT JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE s.fecha_creacion >= NOW() - INTERVAL ':hours HOURS'
            AND s.susurro_padre_id IS NULL
            GROUP BY s.id, u.username, u.genero
            ORDER BY s.vistas DESC, s.fecha_creacion DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByTag(string $tag, int $limit = 20, int $offset = 0): array {
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            JOIN susurros_etiquetas se ON s.id = se.susurro_id
            JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE e.nombre = :tag
            GROUP BY s.id, u.username, u.genero
            ORDER BY s.fecha_creacion DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':tag', $tag);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReaction(int $id, string $type): bool {
        $validTypes = ['me_interesa', 'es_mentira', 'vistas'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Tipo de reacción inválido');
        }

        $query = "UPDATE susurros SET $type = $type + 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['id' => $id]);
    }

    public function getById(int $id): ?array {
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            LEFT JOIN susurros_etiquetas se ON s.id = se.susurro_id
            LEFT JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE s.id = :id
            GROUP BY s.id, u.username, u.genero
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Incrementar vistas automáticamente
            $this->updateReaction($id, 'vistas');
            return $result;
        }
        
        return null;
    }

    public function getByText(string $searchText, int $limit = 20, int $offset = 0): array {
        $searchText = '%' . strtolower($searchText) . '%';
        
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            LEFT JOIN susurros_etiquetas se ON s.id = se.susurro_id
            LEFT JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE LOWER(s.mensaje) LIKE :searchText
            OR EXISTS (
                SELECT 1 FROM etiquetas e2 
                JOIN susurros_etiquetas se2 ON e2.id = se2.etiqueta_id 
                WHERE se2.susurro_id = s.id 
                AND LOWER(e2.nombre) LIKE :searchText
            )
            GROUP BY s.id, u.username, u.genero
            ORDER BY s.fecha_creacion DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':searchText', $searchText);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllTags(): array {
        $query = "
            SELECT e.nombre, 
                   COUNT(DISTINCT se.susurro_id) as uso_count
            FROM etiquetas e
            JOIN susurros_etiquetas se ON e.id = se.etiqueta_id
            JOIN susurros s ON se.susurro_id = s.id
            WHERE s.fecha_creacion >= NOW() - INTERVAL '24 HOURS'
            GROUP BY e.nombre
            ORDER BY uso_count DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResponses(int $whisper_id, int $limit = 20, int $offset = 0): array {
        $query = "
            SELECT s.*, 
                   u.username, 
                   u.genero,
                   COUNT(DISTINCT sr.id) as respuestas_count,
                   CASE 
                       WHEN s.vistas > 0 THEN 
                           ROUND((s.me_interesa::float / s.vistas) * 100)
                       ELSE 0 
                   END as porcentaje_interes,
                   array_agg(DISTINCT e.nombre) as etiquetas
            FROM susurros s
            JOIN usuarios u ON s.usuario_id = u.id
            LEFT JOIN susurros sr ON sr.susurro_padre_id = s.id
            LEFT JOIN susurros_etiquetas se ON s.id = se.susurro_id
            LEFT JOIN etiquetas e ON se.etiqueta_id = e.id
            WHERE s.susurro_padre_id = :whisper_id
            GROUP BY s.id, u.username, u.genero
            ORDER BY s.fecha_creacion ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':whisper_id', $whisper_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalPages(int $limit, ?string $tag = null, ?string $searchText = null): int {
        $whereClause = "";
        $params = [];
        
        if ($tag) {
            $whereClause = "WHERE EXISTS (
                SELECT 1 FROM susurros_etiquetas se 
                JOIN etiquetas e ON se.etiqueta_id = e.id 
                WHERE se.susurro_id = s.id AND e.nombre = :tag
            )";
            $params[':tag'] = $tag;
        } elseif ($searchText) {
            $whereClause = "WHERE LOWER(s.mensaje) LIKE :searchText";
            $params[':searchText'] = '%' . strtolower($searchText) . '%';
        }

        $query = "
            SELECT CEIL(COUNT(DISTINCT s.id)::float / :limit) as total_pages
            FROM susurros s
            $whereClause
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (int) $stmt->fetchColumn();
    }
}