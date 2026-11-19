<?php

class Database {
    public static function getConnection(): PDO {
        $host = 'localhost';
        $porta = '3306';
        $banco = 'biblioteca';
        $usuario = 'root';
        $senha = 'root'; // No Laragon padrão, a senha costuma ser vazia (''). Se der erro de acesso, deixe apenas ''

        try {
            $dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";
            $pdo = new PDO($dsn, $usuario, $senha);
            
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $pdo;
        } catch (PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
}
