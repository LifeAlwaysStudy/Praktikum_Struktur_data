<?php
require __DIR__ . '/includes/init.php';
require_login();

$pdo = db();
$err = '';
$pasien = null;
$pid = (int)($_GET['pasien_id'] ?? $_POST['pasien_id'] ?? 0);
if ($pid) {
    $st = $pdo->prepare('SELECT * FROM pasien WHERE id = ?');
    $st->execute([$pid]);
    $pasien = $st->fetch() ?: null;
}

// Cari pasien lewat NIK / no. RM / nama
$hasilCari = [];
$cari = trim($_GET['cari'] ?? '');
if (!$pasien && $cari !== '') {
    $st = $pdo->prepare('SELECT * FROM pasien WHERE nik = ? OR no_rm = ? OR nama LIKE ? LIMIT 20');
    $st->execute([$cari, $cari, '%' . $cari . '%']);
    $hasilCari = $st->fetchAll();
}

$poliList = $pdo->query('SELECT * FROM poli ORDER BY nama')->fetchAll();
$dokterList = $pdo->query('SELECT * FROM dokter ORDER BY nama')->fetchAll();
$penjaminOpsi = ['Umum', 'BPJS Kesehatan', 'Asuransi swasta'];

$form = ['poli_id' => 0, 'dokter_id' => 0, 'tanggal' => date('Y-m-d'), 'penjamin' => 'Umum', 'keluhan' => ''];

if ($pasien && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['poli_id'] = (int)($_POST['poli_id'] ?? 0);
    $form['dokter_id'] = (int)($_POST['dokter_id'] ?? 0);
    $form['tanggal'] = $_POST['tanggal'] ?? '';
    $form['penjamin'] = $_POST['penjamin'] ?? '';
    $form['keluhan'] = mb_substr(trim($_POST['keluhan'] ?? ''), 0, 255);

    $dk = $pdo->prepare('SELECT 1 FROM dokter WHERE id = ? AND poli_id = ?');
    $dk->execute([$form['dokter_id'], $form['poli_id']]);

    if (!$dk->fetch()) $err = 'Pilih poli dan dokter yang sesuai.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['tanggal']) || $form['tanggal'] < date('Y-m-d')) $err = 'Tanggal kunjungan harus hari ini atau setelahnya.';
    elseif (!in_array($form['penjamin'], $penjaminOpsi, true)) $err = 'Pilih penjamin.';
    else {
        $dup = $pdo->prepare("SELECT no_antrean FROM kunjungan WHERE pasien_id=? AND poli_id=? AND tanggal=? AND status<>'Batal'");
        $dup->execute([$pasien['id'], $form['poli_id'], $form['tanggal']]);
        if ($ada = $dup->fetch()) {
            $err = 'Pasien sudah terdaftar di poli ini pada tanggal tersebut (antrean nomor ' . $ada['no_antrean'] . ').';
        } else {
            // Nomor antrean per poli per tanggal; unique key mencegah dobel, ulangi jika bentrok
            for ($i = 0; $i < 5; $i++) {
                try {
                    $pdo->beginTransaction();
                    $n = $pdo->prepare('SELECT COALESCE(MAX(no_antrean),0)+1 FROM kunjungan WHERE poli_id=? AND tanggal=?');
                    $n->execute([$form['poli_id'], $form['tanggal']]);
                    $no = (int)$n->fetchColumn();
                    $pdo->prepare('INSERT INTO kunjungan (pasien_id,poli_id,dokter_id,tanggal,no_antrean,penjamin,keluhan,petugas_id) VALUES (?,?,?,?,?,?,?,?)')
                        ->execute([$pasien['id'], $form['poli_id'], $form['dokter_id'], $form['tanggal'], $no, $form['penjamin'], $form['keluhan'] ?: null, $_SESSION['user']['id']]);
                    $kid = (int)$pdo->lastInsertId();
                    $pdo->commit();
                    header('Location: tiket.php?id=' . $kid); exit;
                } catch (PDOException $ex) {
                    $pdo->rollBack();
                    if ($ex->getCode() !== '23000') throw $ex;
                }
            }
            $err = 'Gagal mengambil nomor antrean. Coba lagi.';
        }
    }
}

$judul = 'Daftar kunjungan';
require __DIR__ . '/includes/header.php';
?>
<h1>Daftar kunjungan</h1>

<?php if (!$pasien): ?>
<form method="get" class="card">
  <label for="cari">Cari pasien (NIK, no. RM, atau nama)</label>
  <div class="inline"><input id="cari" name="cari" value="<?= e($cari) ?>" autofocus><button class="btn">Cari</button></div>
  <?php if ($cari !== '' && !$hasilCari): ?>
    <p class="muted">Pasien tidak ditemukan. <a href="pasien_baru.php">Tambah sebagai pasien baru</a></p>
  <?php endif; ?>
  <?php foreach ($hasilCari as $r): ?>
    <div class="pick"><span><b><?= e($r['nama']) ?></b> <small><?= e($r['no_rm']) ?> · <?= e($r['nik']) ?></small></span>
    <a class="btn small" href="daftar.php?pasien_id=<?= $r['id'] ?>">Pilih</a></div>
  <?php endforeach; ?>
  <p class="muted" style="margin-bottom:0">Belum pernah berobat? <a href="pasien_baru.php">Daftarkan pasien baru</a></p>
</form>

<?php else: ?>
<div class="card pasien-ringkas">
  <b><?= e($pasien['nama']) ?></b> <span class="muted"><?= e($pasien['no_rm']) ?> · <?= e($pasien['jk']) ?> · lahir <?= tgl_id($pasien['tgl_lahir']) ?></span>
  <a href="daftar.php" class="small">Ganti pasien</a>
</div>
<form method="post" class="card" novalidate>
  <?= csrf_field() ?><input type="hidden" name="pasien_id" value="<?= (int)$pasien['id'] ?>">
  <?php if ($err): ?><div class="msg err"><?= e($err) ?></div><?php endif; ?>
  <div class="grid">
    <div><label for="poli">Poli</label><select id="poli" name="poli_id" required>
      <option value="">Pilih poli</option>
      <?php foreach ($poliList as $po): ?><option value="<?= $po['id'] ?>" <?= $form['poli_id']==$po['id']?'selected':'' ?>><?= e($po['nama']) ?></option><?php endforeach; ?></select></div>
    <div><label for="dokter">Dokter</label><select id="dokter" name="dokter_id" required>
      <option value="">Pilih dokter</option>
      <?php foreach ($dokterList as $d): ?><option value="<?= $d['id'] ?>" data-poli="<?= $d['poli_id'] ?>" <?= $form['dokter_id']==$d['id']?'selected':'' ?>><?= e($d['nama']) ?></option><?php endforeach; ?></select></div>
    <div><label for="tanggal">Tanggal kunjungan</label><input id="tanggal" type="date" name="tanggal" value="<?= e($form['tanggal']) ?>" min="<?= date('Y-m-d') ?>" required></div>
    <div><label for="penjamin">Penjamin</label><select id="penjamin" name="penjamin">
      <?php foreach ($penjaminOpsi as $p): ?><option <?= $form['penjamin']===$p?'selected':'' ?>><?= $p ?></option><?php endforeach; ?></select></div>
    <div class="full"><label for="keluhan">Keluhan singkat (opsional)</label><input id="keluhan" name="keluhan" value="<?= e($form['keluhan']) ?>" maxlength="255"></div>
  </div>
  <button class="btn" style="margin-top:14px">Daftar dan ambil antrean</button>
</form>
<script>
const poli=document.getElementById('poli'),dok=document.getElementById('dokter');
function filterDokter(){
  let ok=false;
  for(const o of dok.options){
    if(!o.value){continue}
    const show=o.dataset.poli===poli.value;
    o.hidden=!show;o.disabled=!show;
    if(show&&o.selected)ok=true;
  }
  if(!ok)dok.value='';
}
poli.addEventListener('change',filterDokter);filterDokter();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
