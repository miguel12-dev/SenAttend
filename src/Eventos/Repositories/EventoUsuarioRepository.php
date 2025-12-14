<?php

namespace App\Eventos\Repositories;

use App\Database\Connection;
use PDO;

/**
 * Repositorio para gestión de usuarios del módulo de eventos
 * Principio de Responsabilidad Única: Solo maneja operaciones de BD para usuarios de eventos
 */
class EventoUsuarioRepository
{
    /**
     * Busca un usuario por su email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = Connection::prepare(
            "SELECT id, email, password_hash, nombre, rol, activo, created_at 
             FROM eventos_usuarios 
             WHERE email = :email AND activo = TRUE"
        );
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Busca un usuario por su ID
     */
    public function findById(int $id): ?array
    {
        $stmt = Connection::prepare(
            "SELECT id, email, nombre, rol, activo, created_at 
             FROM eventos_usuarios 
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Crea un nuevo usuario
     */
    public function create(array $data): int
    {
        $stmt = Connection::prepare(
            "INSERT INTO eventos_usuarios (email, password_hash, nombre, rol, activo)
             VALUES (:email, :password_hash, :nombre, :rol, :activo)"
        );
        
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'nombre' => $data['nombre'],
            'rol' => $data['rol'] ?? 'administrativo',
            'activo' => $data['activo'] ?? true
        ]);
        
        return (int) Connection::lastInsertId();
    }

    /**
     * Actualiza un usuario existente
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        foreach (['email', 'nombre', 'rol', 'activo'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (isset($data['password_hash'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = $data['password_hash'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE eventos_usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = Connection::prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Obtiene todos los usuarios activos
     */
    public function findAll(): array
    {
        $stmt = Connection::query(
            "SELECT id, email, nombre, rol, activo, created_at 
             FROM eventos_usuarios 
             ORDER BY nombre ASC"
        );
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

