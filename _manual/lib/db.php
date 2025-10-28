<?php
// UPDATE THESE:
const DB_DSN  = 'mysql:host=127.0.0.1;dbname=CLICKHUB_DB;charset=utf8mb4';
const DB_USER = 'CLICKHUB_DB';
const DB_PASS = 'FzU8EwCPDC37';

function db() : PDO {
  static $pdo;
  if (!$pdo) {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}
