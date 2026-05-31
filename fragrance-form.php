<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$action = 'Add'; $error = ''; $success = '';
$f = ['id'=>0,'name'=>'','brand'=>'','collection_id'=>0,'description'=>'','price'=>'','image_url'=>'','video_url'=>'',
      'fragrance_type'=>'','concentration'=>'','gender'=>'unisex','longevity'=>'','sillage'=>'',
      'occasion'=>'','bottle_size'=>'','stock'=>0,'is_featured'=>0,'show_on_home'=>0,'classification'=>'',
      'season_summer'=>0,'season_spring'=>0,'season_fall'=>0,'season_winter'=>0,
      'time_day'=>0,'time_night'=>0];

$collections = $conn->query("SELECT id, name FROM collections ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_accords = $conn->query("SELECT id, name FROM accords ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$all_notes   = $conn->query("SELECT id, name FROM notes   ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$sel_accords = [];
$sel_notes   = [];

if (isset($_GET['id'])) {
    $fid = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM fragrances WHERE id=?");
    $stmt->bind_param('i', $fid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) { $f = array_merge($f, $row); $action = 'Edit'; }

    $ar = $conn->query("SELECT accord_id, strength FROM fragrance_accords WHERE fragrance_id=$fid");
    if ($ar) while ($r = $ar->fetch_assoc()) $sel_accords[$r['accord_id']] = $r['strength'];
    $nr = $conn->query("SELECT note_id, tier FROM fragrance_notes WHERE fragrance_id=$fid");
    if ($nr) while ($r = $nr->fetch_assoc()) $sel_notes[$r['note_id']] = $r['tier'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = intval($_POST['id'] ?? 0);
    $name           = sanitize($_POST['name'] ?? '');
    $brand          = sanitize($_POST['brand'] ?? '');
    $collection_id  = (isset($_POST['collection_id']) && intval($_POST['collection_id']) > 0)
                        ? intval($_POST['collection_id']) : null;
    $description    = sanitize($_POST['description'] ?? '');
    $price          = floatval($_POST['price'] ?? 0);
    $image_url      = sanitize($_POST['image_url'] ?? '');
    $video_url      = sanitize($_POST['video_url'] ?? '');
    $concentration  = sanitize($_POST['concentration'] ?? '');
    $gender         = in_array($_POST['gender'] ?? '', ['him','her','unisex']) ? $_POST['gender'] : 'unisex';
    $longevity      = sanitize($_POST['longevity'] ?? '');
    $sillage        = sanitize($_POST['sillage'] ?? '');
    $occasion       = sanitize($_POST['occasion'] ?? '');
    $bottle_size    = sanitize($_POST['bottle_size'] ?? '');
    $stock          = intval($_POST['stock'] ?? 0);
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $show_on_home   = isset($_POST['show_on_home']) ? 1 : 0;
    $allowed_class  = ['designer','niche','middle_eastern',''];
    $classification = in_array($_POST['classification'] ?? '', $allowed_class) ? ($_POST['classification'] ?? '') : '';
    $ss = isset($_POST['season_summer']) ? 1 : 0;
    $sp = isset($_POST['season_spring']) ? 1 : 0;
    $sf = isset($_POST['season_fall'])   ? 1 : 0;
    $sw = isset($_POST['season_winter']) ? 1 : 0;
    $td = isset($_POST['time_day'])      ? 1 : 0;
    $tn = isset($_POST['time_night'])    ? 1 : 0;
    $posted_accords = $_POST['accords'] ?? [];
    $posted_notes   = $_POST['notes']   ?? [];

    if (!$name || $price <= 0) {
        $error = 'Name and a valid price are required.';
    } else {
        // Auto-add classification column if it doesn't exist yet
        $col_chk = $conn->query("SHOW COLUMNS FROM fragrances LIKE 'classification'");
        if ($col_chk->num_rows === 0) {
            $conn->query("ALTER TABLE fragrances ADD COLUMN classification VARCHAR(40) DEFAULT '' AFTER is_featured");
        }
        $conn->query("ALTER TABLE fragrances ADD COLUMN IF NOT EXISTS show_on_home TINYINT(1) DEFAULT 0 AFTER is_featured");

        // NULL-safe collection_id for both INSERT and UPDATE
        $cid = $collection_id; // may be null

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE fragrances SET name=?,brand=?,collection_id=?,description=?,price=?,image_url=?,video_url=?,concentration=?,gender=?,longevity=?,sillage=?,occasion=?,bottle_size=?,stock=?,is_featured=?,show_on_home=?,classification=?,season_summer=?,season_spring=?,season_fall=?,season_winter=?,time_day=?,time_night=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param('ssissssssssssiisiiiiii',$name,$brand,$cid,$description,$price,$image_url,$video_url,$concentration,$gender,$longevity,$sillage,$occasion,$bottle_size,$stock,$is_featured,$show_on_home,$classification,$ss,$sp,$sf,$sw,$td,$tn,$id);
            if ($stmt->execute()) { $success='Fragrance updated.'; $fid=$id; }
            else $error='Update failed: '.$conn->error;
        } else {
            $stmt = $conn->prepare("INSERT INTO fragrances (name,brand,collection_id,description,price,image_url,video_url,concentration,gender,longevity,sillage,occasion,bottle_size,stock,is_featured,show_on_home,classification,season_summer,season_spring,season_fall,season_winter,time_day,time_night) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssissssssssssiiiisiiiii',$name,$brand,$cid,$description,$price,$image_url,$video_url,$concentration,$gender,$longevity,$sillage,$occasion,$bottle_size,$stock,$is_featured,$show_on_home,$classification,$ss,$sp,$sf,$sw,$td,$tn);
            if ($stmt->execute()) { $fid=$conn->insert_id; $id=$fid; $success='Fragrance added.'; }
            else $error='Insert failed: '.$conn->error;
        }

        if (!$error) {
            $conn->query("DELETE FROM fragrance_accords WHERE fragrance_id=$fid");
            $sa = $conn->prepare("INSERT IGNORE INTO fragrance_accords (fragrance_id,accord_id,strength) VALUES (?,?,?)");
            foreach ($posted_accords as $aid => $str) {
                $aid=intval($aid); $str=max(0,min(100,intval($str)));
                if ($aid>0){$sa->bind_param('iii',$fid,$aid,$str);$sa->execute();}
            }
        }
        if (!$error) {
            $conn->query("DELETE FROM fragrance_notes WHERE fragrance_id=$fid");
            $sn = $conn->prepare("INSERT IGNORE INTO fragrance_notes (fragrance_id,note_id,tier) VALUES (?,?,?)");
            foreach ($posted_notes as $nid => $tier) {
                $nid=intval($nid);
                if ($nid>0 && in_array($tier,['top','heart','base'])){$sn->bind_param('iis',$fid,$nid,$tier);$sn->execute();}
            }
        }
        if (!$error) { header('Location: '.BASE_URL.'/admin/fragrances.php'); exit; }
    }
    $f = array_merge($f,compact('id','name','brand','collection_id','description','price','image_url','video_url','concentration','gender','longevity','sillage','occasion','bottle_size','stock','is_featured','classification'));
    $f+=['season_summer'=>$ss,'season_spring'=>$sp,'season_fall'=>$sf,'season_winter'=>$sw,'time_day'=>$td,'time_night'=>$tn];
    foreach ($posted_accords as $aid=>$str) $sel_accords[intval($aid)]=intval($str);
    foreach ($posted_notes as $nid=>$t) $sel_notes[intval($nid)]=$t;
}

$page_title = $action.' Fragrance';
$admin_page  = 'fragrances';
include __DIR__.'/../header.php';
?>
<style>
.img-upload-widget{display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;}
.img-upload-drop{width:120px;height:120px;border:2px dashed var(--border-light);border-radius:var(--radius-sm);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;background:var(--bg-secondary);transition:border-color .2s;flex-shrink:0;}
.img-upload-drop:hover{border-color:var(--cta-bg);}
.img-upload-drop img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
.img-upload-drop .drop-ph{text-align:center;padding:.5rem;pointer-events:none;z-index:1;}
.img-upload-drop .drop-ph i{font-size:1.4rem;color:var(--text-muted);display:block;margin-bottom:.35rem;}
.img-upload-drop .drop-ph span{font-size:.68rem;font-family:Montserrat;color:var(--text-muted);line-height:1.3;}
.img-upload-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.img-upload-right{flex:1;min-width:200px;display:flex;flex-direction:column;gap:.45rem;}
.upload-status{font-size:.74rem;font-family:Montserrat;color:var(--text-muted);}
.upload-status.uploading{color:var(--cta-bg);}
.upload-status.done{color:#4caf7d;}
.upload-status.fail{color:#e05c5c;}
.upload-or{font-size:.7rem;font-family:Montserrat;color:var(--text-muted);text-align:center;}
</style>
<div class="admin-wrapper">
  <?php include __DIR__.'/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title"><?php echo $action; ?> Fragrance</h1>
      <a href="<?php echo BASE_URL; ?>/admin/fragrances.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
    <?php if($error):?><div class="admin-alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <?php if($success):?><div class="admin-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success);?></div><?php endif;?>

    <form method="POST">
      <input type="hidden" name="id" value="<?php echo intval($f['id']);?>">
      <div class="admin-form-card">

        <div class="admin-form-grid">

          <div class="form-group">
            <label class="form-label">Name *</label>
            <input class="form-input" name="name" required value="<?php echo htmlspecialchars($f['name']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Brand</label>
            <input class="form-input" name="brand" value="<?php echo htmlspecialchars($f['brand']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Collection</label>
            <select class="form-select" name="collection_id">
              <option value="0">— None —</option>
              <?php foreach($collections as $col):?>
                <option value="<?php echo $col['id'];?>" <?php echo $col['id']==$f['collection_id']?'selected':'';?>><?php echo htmlspecialchars($col['name']);?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender">
              <?php foreach(['him'=>'For Him','her'=>'For Her','unisex'=>'Unisex'] as $v=>$l):?>
                <option value="<?php echo $v;?>" <?php echo $f['gender']==$v?'selected':'';?>><?php echo $l;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Classification</label>
            <select class="form-select" name="classification">
              <option value="">— Not specified —</option>
              <?php foreach(['designer'=>'Designer','niche'=>'Niche','middle_eastern'=>'Middle Eastern'] as $v=>$l):?>
                <option value="<?php echo $v;?>" <?php echo ($f['classification']??'')===$v?'selected':'';?>><?php echo $l;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Price (DZD) *</label>
            <input class="form-input" type="number" step="0.01" name="price" required value="<?php echo htmlspecialchars($f['price']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Stock</label>
            <input class="form-input" type="number" name="stock" value="<?php echo intval($f['stock']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Concentration</label>
            <select class="form-select" name="concentration">
              <option value="">— Select —</option>
              <?php foreach(['Parfum','Extrait de Parfum','Eau de Parfum (EDP)','Eau de Toilette (EDT)','Eau de Cologne (EDC)','Eau Fraîche'] as $c):?>
                <option value="<?php echo $c;?>" <?php echo $f['concentration']===$c?'selected':'';?>><?php echo $c;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Longevity</label>
            <select class="form-select" name="longevity">
              <option value="">— Select —</option>
              <?php foreach(['1-3 hours (Weak)','3-5 hours (Moderate)','6-8 hours (Long Lasting)','8-12 hours (Very Long Lasting)','12+ hours (Eternal)'] as $l):?>
                <option value="<?php echo $l;?>" <?php echo $f['longevity']===$l?'selected':'';?>><?php echo $l;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Sillage</label>
            <select class="form-select" name="sillage">
              <option value="">— Select —</option>
              <?php foreach(['Intimate','Soft','Moderate','Strong','Enormous'] as $s):?>
                <option value="<?php echo $s;?>" <?php echo $f['sillage']===$s?'selected':'';?>><?php echo $s;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Bottle Size</label>
            <input class="form-input" name="bottle_size" placeholder="e.g. 100ml" value="<?php echo htmlspecialchars($f['bottle_size']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Occasion</label>
            <input class="form-input" name="occasion" placeholder="e.g. Date night, Office" value="<?php echo htmlspecialchars($f['occasion']);?>">
          </div>

          <!-- Image Upload -->
          <div class="form-group admin-form-full">
            <label class="form-label">Fragrance Image</label>
            <div class="img-upload-widget">
              <div class="img-upload-drop" id="frag-drop">
                <?php if(!empty($f['image_url'])):?>
                  <img src="<?php echo BASE_URL.'/'.htmlspecialchars($f['image_url']);?>" id="frag-preview" alt="">
                <?php else:?>
                  <img src="" id="frag-preview" alt="" style="display:none;">
                  <div class="drop-ph"><i class="fa-solid fa-cloud-arrow-up"></i><span>Click or drag &amp; drop image here</span></div>
                <?php endif;?>
                <input type="file" accept="image/*" id="frag-file" aria-label="Upload fragrance image">
              </div>
              <div class="img-upload-right">
                <span class="upload-status" id="frag-status"><?php echo !empty($f['image_url'])?'✓ Image set':'No image yet';?></span>
                <span class="upload-or">— or paste a path / URL —</span>
                <input class="form-input" name="image_url" id="frag-url"
                  placeholder="images/uploads/my-fragrance.jpg"
                  value="<?php echo htmlspecialchars($f['image_url']);?>">
                <span class="form-hint">Upload directly from your PC, or type a relative path.</span>
              </div>
            </div>
          </div>

          <!-- Video URL -->
          <div class="form-group admin-form-full">
            <label class="form-label">Video URL <span class="form-hint" style="margin:0 0 0 .4em;">(YouTube / direct link)</span></label>
            <input class="form-input" name="video_url" placeholder="https://youtube.com/watch?v=..." value="<?php echo htmlspecialchars($f['video_url']);?>">
          </div>

          <!-- Description -->
          <div class="form-group admin-form-full">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description" rows="4"><?php echo htmlspecialchars($f['description']);?></textarea>
          </div>

        </div><!-- /.admin-form-grid -->

        <!-- Season & Time -->
        <div style="margin-top:1.5rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <p class="form-label" style="margin-bottom:.75rem;">Season &amp; Time</p>
          <div style="display:flex;flex-wrap:wrap;gap:.6rem 1.5rem;">
            <?php foreach(['season_summer'=>'☀️ Summer','season_spring'=>'🌸 Spring','season_fall'=>'🍂 Fall','season_winter'=>'❄️ Winter','time_day'=>'🌤 Day','time_night'=>'🌙 Night'] as $k=>$l):?>
              <label style="display:flex;align-items:center;gap:.4em;font-family:Montserrat;font-size:.82rem;cursor:pointer;">
                <input type="checkbox" name="<?php echo $k;?>" value="1" <?php echo !empty($f[$k])?'checked':'';?> style="accent-color:var(--cta-bg);">
                <?php echo $l;?>
              </label>
            <?php endforeach;?>
            <label style="display:flex;align-items:center;gap:.4em;font-family:Montserrat;font-size:.82rem;cursor:pointer;">
              <input type="checkbox" name="is_featured" value="1" <?php echo !empty($f['is_featured'])?'checked':'';?> style="accent-color:var(--cta-bg);">
              ⭐ Featured
            </label>
            <label style="display:flex;align-items:center;gap:.4em;font-family:Montserrat;font-size:.82rem;cursor:pointer;">
              <input type="checkbox" name="show_on_home" value="1" <?php echo !empty($f['show_on_home'])?'checked':'';?> style="accent-color:var(--cta-bg);">
              🏠 Show on Home Page
            </label>
          </div>
        </div>

        <!-- Accords -->
        <div style="margin-top:1.5rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <p class="form-label" style="margin-bottom:.75rem;">Accords <span class="form-hint" style="margin:0 0 0 .4em;">(check to enable, set strength 1–100)</span></p>
          <?php if(empty($all_accords)):?>
            <p class="form-hint">No accords yet. <a href="<?php echo BASE_URL;?>/admin/accords.php">Add accords first →</a></p>
          <?php else:?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.5rem .75rem;">
              <?php foreach($all_accords as $ac): $sid=$ac['id']; $enabled=isset($sel_accords[$sid]);?>
                <div style="display:flex;align-items:center;gap:.5em;font-family:Montserrat;font-size:.82rem;">
                  <input type="checkbox" id="ac_<?php echo $sid;?>" style="accent-color:var(--cta-bg);"
                    onchange="document.getElementById('str_<?php echo $sid;?>').disabled=!this.checked;"
                    <?php echo $enabled?'checked':'';?>>
                  <label for="ac_<?php echo $sid;?>" style="flex:1;cursor:pointer;"><?php echo htmlspecialchars($ac['name']);?></label>
                  <input type="range" min="1" max="100" style="width:60px;" id="str_<?php echo $sid;?>"
                    name="accords[<?php echo $sid;?>]"
                    value="<?php echo $enabled?intval($sel_accords[$sid]):50;?>"
                    <?php echo $enabled?'':'disabled';?>>
                </div>
              <?php endforeach;?>
            </div>
          <?php endif;?>
        </div>

        <!-- Notes -->
        <div style="margin-top:1.5rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <p class="form-label" style="margin-bottom:.75rem;">Notes Pyramid <span class="form-hint" style="margin:0 0 0 .4em;">(select tier for each note)</span></p>
          <?php if(empty($all_notes)):?>
            <p class="form-hint">No notes yet. <a href="<?php echo BASE_URL;?>/admin/notes.php">Add notes first →</a></p>
          <?php else:?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.4rem .75rem;">
              <?php foreach($all_notes as $nt): $nid=$nt['id']; $cur=$sel_notes[$nid]??'';?>
                <div style="display:flex;align-items:center;gap:.5em;font-family:Montserrat;font-size:.82rem;">
                  <span style="flex:1;"><?php echo htmlspecialchars($nt['name']);?></span>
                  <select name="notes[<?php echo $nid;?>]" class="form-select" style="padding:.3em .5em;font-size:.78rem;width:auto;">
                    <option value="">—</option>
                    <option value="top"   <?php echo $cur==='top'  ?'selected':'';?>>Top</option>
                    <option value="heart" <?php echo $cur==='heart'?'selected':'';?>>Heart</option>
                    <option value="base"  <?php echo $cur==='base' ?'selected':'';?>>Base</option>
                  </select>
                </div>
              <?php endforeach;?>
            </div>
          <?php endif;?>
        </div>

        <div class="form-actions" style="margin-top:1.5rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> <?php echo $action;?> Fragrance</button>
          <a href="<?php echo BASE_URL;?>/admin/fragrances.php" class="btn-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
(function(){
  const UPLOAD_URL = '<?php echo BASE_URL;?>/admin/upload.php';
  function initWidget(dropId, fileId, previewId, statusId, urlId){
    const drop    = document.getElementById(dropId);
    const fileIn  = document.getElementById(fileId);
    const preview = document.getElementById(previewId);
    const status  = document.getElementById(statusId);
    const urlIn   = document.getElementById(urlId);
    if(!drop) return;

    urlIn.addEventListener('input', function(){
      const v = this.value.trim();
      if(v){ preview.src = v.startsWith('http') ? v : '<?php echo BASE_URL;?>/'+v; preview.style.display='block'; hidePh(); }
    });
    fileIn.addEventListener('change', function(){ if(this.files[0]) upload(this.files[0]); });
    drop.addEventListener('dragover', function(e){ e.preventDefault(); this.style.borderColor='var(--cta-bg)'; });
    drop.addEventListener('dragleave', function(){ this.style.borderColor=''; });
    drop.addEventListener('drop', function(e){
      e.preventDefault(); this.style.borderColor='';
      if(e.dataTransfer.files[0]) upload(e.dataTransfer.files[0]);
    });

    function hidePh(){ const ph=drop.querySelector('.drop-ph'); if(ph) ph.style.display='none'; }
    function upload(file){
      status.textContent='Uploading…'; status.className='upload-status uploading';
      const reader=new FileReader();
      reader.onload=function(e){ preview.src=e.target.result; preview.style.display='block'; hidePh(); };
      reader.readAsDataURL(file);
      const fd=new FormData(); fd.append('image',file);
      fetch(UPLOAD_URL,{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{
          if(d.success){ urlIn.value=d.url; status.textContent='✓ Saved: '+d.url; status.className='upload-status done'; }
          else { status.textContent='✗ '+(d.error||'Upload failed'); status.className='upload-status fail'; }
        })
        .catch(()=>{ status.textContent='✗ Network error'; status.className='upload-status fail'; });
    }
  }
  initWidget('frag-drop','frag-file','frag-preview','frag-status','frag-url');
})();
</script>
<?php include __DIR__.'/../footer.php'; ?>
