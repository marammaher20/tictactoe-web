<?php
// انسخي هالملف باسم db.php وعدّلي القيم حسب إعداداتك
function getConnection(): PDO
{
    $host = 'localhost';
    $dbname = 'tictactoe';
    $user = 'root';
    $pass = '';

    return new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}