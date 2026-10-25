<?php
require __DIR__ . '/includes/init.php';
require_login();

$err = [];
$v = ['nama'=>'','nik'=>'','tgl_lahir'=>'','jk'=>'Laki-laki','telepon'=>'','alamat'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $hp = preg_replace('/[\s-]/', '', $v['telepon']);

    if (mb_strlen($v['nama']) < 3) $err['nama'] = 'Isi nama lengkap, minimal 3 huruf.';
    if (!preg_match('/^\d{16}$/', $v['nik'])) $err['nik'] = 'NIK harus 16 digit angka.';
    $d = DateTime::createFromFormat('Y-m-d', $v['tgl_lahir']);
    if (!$d || $d->format('Y-m-d') !== $v['tgl_lahir'] || $v['tgl_lahir'] > date('Y-m-d')) $err['tgl_lahir'] = 'Tanggal lahir tidak valid.';
    if (!in_array($v['jk'], ['Laki-laki', 'Perempuan'], true)) $err['jk'] = 'Pilih jenis kelamin.';
    if (!preg_match('/^(\+62|62|0)8\d{7,11}$/', $hp)) $err['telepon'] = 'Nomor HP tidak valid, contoh 081234567890.';
    if (mb_strlen($v['alamat']) < 5) $err['alamat'] = 'Isi alamat lengkap.';

    if (!$err) {
        $cek = db()->prepare('SELECT id, nama FROM pasien WHERE nik = ?');
        $cek->execute([$v['nik']]);
        if ($ada = $cek->fetch()) {
            $err['nik'] = 'NIK sudah terdaftar atas nama ' . $ada['nama'] . '.';
            $v['_ada'] = $ada['id'];
        }
    }
    if (!$err) {
        $pdo = db();
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO pasien (nik,nama,tgl_lahir,jk,telepon,alamat) VALUES (?,?,?,?,?,?)')
            ->execute([$v['nik'], $v['nama'], $v['tgl_lahir'], $v['jk'], $hp, $v['alamat']]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE pasien SET no_rm = ? WHERE id = ?')->execute(['RM-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT), $id]);
        $pdo->commit();
        flash('Pasien baru tersimpan. Lanjutkan pendaftaran kunjungan.');
        header('Location: daftar.php?pasien_id=' . $id); exit;
    }
}

function fe(array $err, string $k): string { return isset($err[$k]) ? '<div class="fe">' . e($err[$k]) . '</div>' : ''; }
$judul = 'Pasien baru';
require __DIR__ . '/includes/header.php';
?>
<h1>Pasien baru</h1>
<form method="post" class="card" novalidate>
  <?= csrf_field() ?>
  <div class="grid">
    <div class="full"><label for="nama">Nama lengkap</label><input id="nama" name="nama" value="<?= e($v['nama']) ?>" required><?= fe($err,'nama') ?></div>
    <div><label for="nik">NIK (16 digit)</label><input id="nik" name="nik" value="<?= e($v['nik']) ?>" maxlength="16" inputmode="numeric" required>
      <?= fe($err,'nik') ?><?php if (!empty($v['_ada'])): ?><a href="daftar.php?pasien_id=<?= (int)$v['_ada'] ?>">Daftarkan pasien ini</a><?php endif; ?></div>
    <div><label for="tgl_lahir">Tanggal lahir</label><input id="tgl_lahir" type="date" name="tgl_lahir" value="<?= e($v['tgl_lahir']) ?>" max="<?= date('Y-m-d') ?>" required><?= fe($err,'tgl_lahir') ?></div>
    <div><label for="jk">Jenis kelamin</label><select id="jk" name="jk">
      <?php foreach (['Laki-laki','Perempuan'] as $j): ?><option <?= $v['jk']===$j?'selected':'' ?>><?= $j ?></option><?php endforeach; ?></select></div>
    <div><label for="telepon">No. telepon</label><input id="telepon" name="telepon" type="tel" value="<?= e($v['telepon']) ?>" required><?= fe($err,'telepon') ?></div>
    <div class="full"><label for="alamat">Alamat</label><textarea id="alamat" name="alamat" required><?= e($v['alamat']) ?></textarea><?= fe($err,'alamat') ?></div>
  </div>
  <button class="btn" style="margin-top:14px">Simpan dan lanjut daftar kunjungan</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
