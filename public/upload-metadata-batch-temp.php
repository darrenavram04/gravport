<?php
/**
 * TEMPORARY batch metadata XML uploader.
 * DELETE THIS FILE after use.
 *
 * Access: /upload-metadata-batch-temp.php?token=gravport2026
 */

define('SECRET_TOKEN', 'gravport2026');
define('GEOPORTAL_ROOT', dirname(__DIR__));

if (($_GET['token'] ?? '') !== SECRET_TOKEN) {
    http_response_code(403);
    die('403 Forbidden');
}

// ── DB: read from .env, fall back to defaults ────────────────────────────────
$cfg = ['host' => '127.0.0.1', 'port' => 5432, 'dbname' => 'geoportal', 'user' => 'geoportal_user', 'pass' => ''];

$envFile = GEOPORTAL_ROOT . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, '"\'');
        match ($k) {
            'database.default.hostname' => $cfg['host']   = $v,
            'database.default.port'     => $cfg['port']   = (int)$v,
            'database.default.database' => $cfg['dbname'] = $v,
            'database.default.username' => $cfg['user']   = $v,
            'database.default.password' => $cfg['pass']   = $v,
            default => null,
        };
    }
}

$provinceMap = ['Jakarta' => 'DKI Jakarta', 'Yogyakarta' => 'DI Yogyakarta'];
$jenisMap    = ['Terestris' => 'Terestrial', 'Airborne' => 'Airborne'];

$results = [];
$connected = false;
$pdo = null;

// ── Handle upload ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['xmlfiles'])) {
    try {
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $connected = true;
    } catch (PDOException $e) {
        $results[] = ['file' => 'DB', 'status' => 'ERROR', 'msg' => $e->getMessage()];
    }

    if ($connected) {
        $files = $_FILES['xmlfiles'];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'SKIP', 'msg' => 'Upload error'];
                continue;
            }

            $basename = basename($files['name'][$i], '.xml');
            if (!str_starts_with($basename, 'Metadata_')) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'SKIP', 'msg' => 'Nama file tidak sesuai format'];
                continue;
            }

            $withoutPrefix = substr($basename, strlen('Metadata_'));
            $firstUs = strpos($withoutPrefix, '_');
            if ($firstUs === false) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'SKIP', 'msg' => 'Tidak bisa parse jenis_data'];
                continue;
            }
            $jenisRaw = substr($withoutPrefix, 0, $firstUs);
            $rest     = substr($withoutPrefix, $firstUs + 1);

            if (!preg_match('/^(.+)_Level_(\d+)$/', $rest, $m)) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'SKIP', 'msg' => 'Tidak bisa parse level'];
                continue;
            }
            $provinsiRaw = $m[1];
            $levelNum    = $m[2];

            $jenisData = $jenisMap[$jenisRaw]       ?? $jenisRaw;
            $provinsi  = $provinceMap[$provinsiRaw] ?? $provinsiRaw;
            $levelData = "Level {$levelNum}";
            $metaLevel = $levelNum === '1' ? 'level1' : 'level2';
            $rawXml    = file_get_contents($files['tmp_name'][$i]);

            // Parse XML fields
            try {
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->loadXML($rawXml);
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
                $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');

                $emails = [];
                $nodes = $xpath->query('//gmd:contact//gmd:electronicMailAddress/gco:CharacterString');
                if ($nodes) foreach ($nodes as $n) { $v = trim($n->textContent); if ($v !== '') $emails[] = $v; }

                $role = trim((string)$xpath->evaluate('string((//gmd:identificationInfo//gmd:pointOfContact//gmd:CI_RoleCode/@codeListValue)[1])'));
                if ($role === '') $role = trim((string)$xpath->evaluate('string((//gmd:contact//gmd:CI_RoleCode/@codeListValue)[1])'));

                $data = [
                    'file_identifier'       => trim((string)$xpath->evaluate('string(//gmd:fileIdentifier/gco:CharacterString)')),
                    'parent_identifier'     => trim((string)$xpath->evaluate('string(//gmd:parentIdentifier/gco:CharacterString)')),
                    'hierarchy_level_name'  => trim((string)$xpath->evaluate('string(//gmd:hierarchyLevelName/gco:CharacterString)')),
                    'metadata_date'         => trim((string)$xpath->evaluate('string(//gmd:dateStamp/gco:Date)')),
                    'language_code'         => trim((string)$xpath->evaluate('string((//gmd:language/gmd:LanguageCode/@codeListValue)[1])')),
                    'character_set'         => trim((string)$xpath->evaluate('string((//gmd:characterSet//gmd:MD_CharacterSetCode/@codeListValue)[1])')),
                    'title'                 => trim((string)$xpath->evaluate('string((//gmd:identificationInfo//gmd:citation//gmd:title/gco:CharacterString)[1])')),
                    'abstract'              => trim((string)$xpath->evaluate('string((//gmd:identificationInfo//gmd:abstract/gco:CharacterString)[1])')),
                    'individual_name'       => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:individualName/gco:CharacterString)[1])')),
                    'organisation_name'     => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:organisationName/gco:CharacterString)[1])')),
                    'position_name'         => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:positionName/gco:CharacterString)[1])')),
                    'voice'                 => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:voice/gco:CharacterString)[1])')),
                    'delivery_point'        => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:deliveryPoint/gco:CharacterString)[1])')),
                    'city'                  => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:city/gco:CharacterString)[1])')),
                    'administrative_area'   => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:administrativeArea/gco:CharacterString)[1])')),
                    'postal_code'           => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:postalCode/gco:CharacterString)[1])')),
                    'country'               => trim((string)$xpath->evaluate('string((//gmd:contact//gmd:country/gco:CharacterString)[1])')),
                    'emails'                => array_values(array_unique($emails)),
                    'contact_role'          => $role,
                ];
            } catch (Throwable $e) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'ERROR', 'msg' => 'Parse XML: ' . $e->getMessage()];
                continue;
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO geoportal.dataset_metadata_xml (
                        jenis_data, provinsi, level_data,
                        metadata_level, source_path, file_identifier, parent_identifier,
                        hierarchy_level_name, metadata_date, language_code, character_set,
                        title, abstract, individual_name, organisation_name, position_name,
                        voice, delivery_point, city, administrative_area, postal_code,
                        country, emails_json, contact_role, raw_xml, imported_at
                    ) VALUES (
                        :jenis_data, :provinsi, :level_data,
                        :metadata_level, :source_path, :file_identifier, :parent_identifier,
                        :hierarchy_level_name, NULLIF(:metadata_date,'')::date, :language_code, :character_set,
                        :title, :abstract, :individual_name, :organisation_name, :position_name,
                        :voice, :delivery_point, :city, :administrative_area, :postal_code,
                        :country, :emails_json::jsonb, :contact_role, :raw_xml::xml, now()
                    )
                    ON CONFLICT (jenis_data, provinsi, level_data) DO UPDATE SET
                        metadata_level=EXCLUDED.metadata_level, source_path=EXCLUDED.source_path,
                        file_identifier=EXCLUDED.file_identifier, parent_identifier=EXCLUDED.parent_identifier,
                        hierarchy_level_name=EXCLUDED.hierarchy_level_name, metadata_date=EXCLUDED.metadata_date,
                        language_code=EXCLUDED.language_code, character_set=EXCLUDED.character_set,
                        title=EXCLUDED.title, abstract=EXCLUDED.abstract,
                        individual_name=EXCLUDED.individual_name, organisation_name=EXCLUDED.organisation_name,
                        position_name=EXCLUDED.position_name, voice=EXCLUDED.voice,
                        delivery_point=EXCLUDED.delivery_point, city=EXCLUDED.city,
                        administrative_area=EXCLUDED.administrative_area, postal_code=EXCLUDED.postal_code,
                        country=EXCLUDED.country, emails_json=EXCLUDED.emails_json,
                        contact_role=EXCLUDED.contact_role, raw_xml=EXCLUDED.raw_xml, imported_at=now()
                ");
                $stmt->execute([
                    ':jenis_data'           => $jenisData,
                    ':provinsi'             => $provinsi,
                    ':level_data'           => $levelData,
                    ':metadata_level'       => $metaLevel,
                    ':source_path'          => $files['name'][$i],
                    ':file_identifier'      => $data['file_identifier'],
                    ':parent_identifier'    => $data['parent_identifier'],
                    ':hierarchy_level_name' => $data['hierarchy_level_name'],
                    ':metadata_date'        => $data['metadata_date'],
                    ':language_code'        => $data['language_code'],
                    ':character_set'        => $data['character_set'],
                    ':title'                => $data['title'],
                    ':abstract'             => $data['abstract'],
                    ':individual_name'      => $data['individual_name'],
                    ':organisation_name'    => $data['organisation_name'],
                    ':position_name'        => $data['position_name'],
                    ':voice'                => $data['voice'],
                    ':delivery_point'       => $data['delivery_point'],
                    ':city'                 => $data['city'],
                    ':administrative_area'  => $data['administrative_area'],
                    ':postal_code'          => $data['postal_code'],
                    ':country'              => $data['country'],
                    ':emails_json'          => json_encode($data['emails'], JSON_UNESCAPED_UNICODE),
                    ':contact_role'         => $data['contact_role'],
                    ':raw_xml'              => $rawXml,
                ]);
                $results[] = ['file' => $files['name'][$i], 'status' => 'OK', 'msg' => "{$jenisData} | {$provinsi} | {$levelData}"];
            } catch (PDOException $e) {
                $results[] = ['file' => $files['name'][$i], 'status' => 'ERROR', 'msg' => $e->getMessage()];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Batch Upload Metadata XML — TEMP</title>
<style>
  body{font-family:monospace;max-width:860px;margin:40px auto;padding:0 20px;background:#111;color:#eee}
  h2{color:#f90}
  .warn{background:#7a1;color:#fff;padding:10px 16px;border-radius:6px;margin-bottom:20px}
  .warn b{color:#ff0}
  form{background:#1e1e1e;padding:20px;border-radius:8px}
  label{display:block;margin-bottom:8px;color:#aaa}
  input[type=file]{color:#eee;width:100%;margin-bottom:16px}
  button{background:#c07000;color:#fff;border:0;padding:10px 28px;border-radius:6px;cursor:pointer;font-size:1rem}
  button:hover{background:#e08800}
  table{width:100%;border-collapse:collapse;margin-top:24px}
  th,td{text-align:left;padding:6px 10px;border-bottom:1px solid #333}
  th{color:#aaa;font-size:.85rem}
  .ok{color:#4c4}
  .error{color:#f44}
  .skip{color:#fa0}
  .summary{margin-top:16px;font-size:1.1rem}
</style>
</head>
<body>
<h2>⚠ Batch Upload Metadata XML (TEMPORARY)</h2>
<div class="warn"><b>HAPUS FILE INI SETELAH DIGUNAKAN:</b><br>
<code>rm /var/www/geoportal/public/upload-metadata-batch-temp.php</code></div>

<?php if (!empty($results)): ?>
  <?php $ok = count(array_filter($results, fn($r) => $r['status'] === 'OK')); ?>
  <?php $err = count(array_filter($results, fn($r) => $r['status'] === 'ERROR')); ?>
  <div class="summary">
    ✅ <?= $ok ?> berhasil &nbsp;|&nbsp;
    ❌ <?= $err ?> error &nbsp;|&nbsp;
    ⚠ <?= count($results) - $ok - $err ?> dilewati
  </div>
  <table>
    <tr><th>File</th><th>Status</th><th>Keterangan</th></tr>
    <?php foreach ($results as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['file']) ?></td>
      <td class="<?= strtolower($r['status']) ?>"><?= $r['status'] ?></td>
      <td><?= htmlspecialchars($r['msg']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <label>Pilih semua 28 file XML (bisa multi-select):</label>
  <input type="file" name="xmlfiles[]" accept=".xml" multiple required>
  <button type="submit">Upload &amp; Import</button>
</form>
</body>
</html>
