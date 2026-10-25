<?php
require __DIR__ . '/includes/init.php';
require_login();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $st = db()->prepare('SELECT * FROM pasien WHERE nama LIKE ? OR nik LIKE ? OR no_rm LIKE ? ORDER BY id DESC LIMIT 100');
    $like = '%' . $q . '%';
    $st->execute([$like, $like, $like]);
} else {
    $st = db()->query('SELECT * FROM pasien ORDER BY id DESC LIMIT 100');
}
$rows = $st->fetchAll();

$judul = 'Data pasien';
require __DIR__ . '/includes/header.php';
?>
<h1>Data pasien</h1>
<form method="get" class="inline">
  <input name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIK, atau no. RM" aria-label="Cari pasien">
  <button class="btn ghost">Cari</button>
  <a class="btn" href="pasien_baru.php">Pasien baru</a>
</form>
<div class="card tablewrap"><table>
  <tr><th>No. RM</th><th>Nama</th><th>NIK</th><th>Lahir</th><th>Telepon</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr><td><?= e($r['no_rm']) ?></td><td><b><?= e($r['nama']) ?></b><br><small><?= e($r['jk']) ?></small></td>
    <td><?= e($r['nik']) ?></td><td><?= tgl_id($r['tgl_lahir']) ?></td><td><?= e($r['telepon']) ?></td>
    <td><a class="btn small" href="daftar.php?pasien_id=<?= $r['id'] ?>">Daftarkan</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="empty"><?= $q!=='' ? 'Pasien tidak ditemukan.' : 'Belum ada data pasien.' ?> <a href="pasien_baru.php">Tambah pasien baru</a></td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
