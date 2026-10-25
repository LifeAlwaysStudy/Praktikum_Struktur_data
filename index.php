<?php
require __DIR__ . '/includes/init.php';
require_login();

$hari = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['Dipanggil', 'Selesai', 'Batal'], true)) {
        db()->prepare('UPDATE kunjungan SET status = ? WHERE id = ?')->execute([$status, (int)$_POST['id']]);
        flash('Status antrean diubah menjadi ' . $status . '.');
    }
    header('Location: index.php' . (!empty($_GET['poli']) ? '?poli=' . (int)$_GET['poli'] : '')); exit;
}

$poliList = db()->query('SELECT * FROM poli ORDER BY nama')->fetchAll();
$fp = (int)($_GET['poli'] ?? 0);

$sql = 'SELECT k.*, p.nama AS pasien, p.no_rm, po.kode, po.nama AS poli, d.nama AS dokter
        FROM kunjungan k JOIN pasien p ON p.id=k.pasien_id JOIN poli po ON po.id=k.poli_id JOIN dokter d ON d.id=k.dokter_id
        WHERE k.tanggal = ?' . ($fp ? ' AND k.poli_id = ?' : '') . ' ORDER BY po.nama, k.no_antrean';
$st = db()->prepare($sql);
$st->execute($fp ? [$hari, $fp] : [$hari]);
$rows = $st->fetchAll();

$cnt = ['Menunggu' => 0, 'Dipanggil' => 0, 'Selesai' => 0, 'Batal' => 0];
foreach ($rows as $r) $cnt[$r['status']]++;

$judul = 'Antrean hari ini';
require __DIR__ . '/includes/header.php';
?>
<h1>Antrean hari ini <small class="muted"><?= tgl_id($hari) ?></small></h1>
<div class="stats">
  <?php foreach ($cnt as $k => $v): ?><div class="stat"><b><?= $v ?></b><span><?= $k ?></span></div><?php endforeach; ?>
</div>
<form method="get" class="inline">
  <select name="poli" onchange="this.form.submit()" aria-label="Filter poli">
    <option value="0">Semua poli</option>
    <?php foreach ($poliList as $po): ?><option value="<?= $po['id'] ?>" <?= $fp==$po['id']?'selected':'' ?>><?= e($po['nama']) ?></option><?php endforeach; ?>
  </select>
  <a class="btn" href="daftar.php">Daftarkan pasien</a>
</form>
<div class="card tablewrap"><table>
  <tr><th>Antrean</th><th>Pasien</th><th>Poli / Dokter</th><th>Penjamin</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr class="<?= in_array($r['status'],['Selesai','Batal'])?'done':'' ?>">
    <td class="q"><?= kode_antrean($r['kode'], (int)$r['no_antrean']) ?></td>
    <td><b><?= e($r['pasien']) ?></b><br><small><?= e($r['no_rm']) ?></small></td>
    <td><?= e($r['poli']) ?><br><small><?= e($r['dokter']) ?></small></td>
    <td><?= e($r['penjamin']) ?></td>
    <td><span class="badge s-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
    <td class="act">
      <a class="btn small ghost" href="tiket.php?id=<?= $r['id'] ?>" target="_blank">Tiket</a>
      <?php if (in_array($r['status'], ['Menunggu','Dipanggil'])): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <?php if ($r['status']==='Menunggu'): ?><button name="status" value="Dipanggil" class="btn small">Panggil</button><?php endif; ?>
        <button name="status" value="Selesai" class="btn small ghost">Selesai</button>
        <button name="status" value="Batal" class="btn small ghost danger" onclick="return confirm('Batalkan antrean ini?')">Batal</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="empty">Belum ada pendaftaran hari ini.</td></tr><?php endif; ?>
</table></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
