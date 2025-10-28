<?php
require_once __DIR__.'/lib/db.php';
try {
  db()->query('SELECT 1');
  echo "<b>✅ Database connection OK!</b>";
} catch (Throwable $e) {
  echo "<b>❌ Connection failed:</b> " . $e->getMessage();
}
