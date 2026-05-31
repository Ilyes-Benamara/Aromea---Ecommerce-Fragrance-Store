<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$action = 'Add'; $error = ''; $success = '';
$pk = ['id'=>0,'name'=>'','slug'=>'','description'=>'','image_url'=>'','discount_pct'=>0];
$sel_frags = [];

$all_frags = $conn->query("SELECT id, name, brand FROM fragrances ORDER BY brand, name")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['id'])) {
    $pid = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM packs WHERE id=?");
    $stmt->bind_param('i', $pid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) { $pk = $row; $action = 'Edit'; }
    $fr = $conn->query("SELECT fragrance_id, sort_order FROM pack_fragrances WHERE pack_id=$pid ORDER BY sort_order");
    if ($fr) while ($r = $fr->fetch_assoc()) $sel_frags[$r['fragrance_id']] = $r['sort_order'];
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id          = intval($_POST['id'] ?? 0);
    $name        = sanitize($_POST['name'] ?? '');
    $slug        = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'));
    $description = sanitize($_POST['description'] ?? '');
    $image_url   = sanitize($_POST['image_url'] ?? '');
    $discount    = floatval($_POST['discount_pct'] ?? 0);
    $frag_ids    = array_map('intval', $_POST['frag_ids'] ?? []);

    if (!$name) { $error = 'Name required.'; }
    elseif ($id > 0) {
        $stmt = $conn->prepare("UPDATE packs SET name=?,slug=?,description=?,image_url=?,discount_pct=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param('ssssdi',$name,$slug,$description,$image_url,$discount,$id);
        if ($stmt->execute()) { $success = 'Pack updated.'; $pid = $id; }
        else $error = 'Update failed.';
    } else {
        $stmt = $conn->prepare("INSERT INTO packs (name,slug,description,image_url,discount_pct) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssssd',$name,$slug,$description,$image_url,$discount);
        if ($stmt->execute()) { $pid = $conn->insert_id; $id = $pid; $success = 'Pack created.'; }
        else $error = 'Insert failed.';
    }

    if (!$error) {
        $conn->query("DELETE FROM pack_fragrances WHERE pack_id=$pid");
        $ins = $conn->prepare("INSERT IGNORE INTO pack_fragrances (pack_id, fragrance_id, sort_order) VALUES (?,?,?)");
        foreach ($frag_ids as $i => $fid) {
            if ($fid > 0) { $ins->bind_param('iii', $pid, $fid, $i); $ins->execute(); }
        }
        header('Location: ' . BASE_URL . '/admin/packs.php'); exit;
    }
    $pk = compact('id','name','slug','description','image_url');
    $pk['discount_pct'] = $discount;
    $sel_frags = array_flip($frag_ids);
}

$page_title = $action . ' Pack';
$admin_page  = 'packs';
include __DIR__ . '/../header.php';
?>
<style>
.img-upload-widget{display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;}
.img-upload-drop{width:110px;height:110px;border:2px dashed var(--border-light);border-radius:var(--radius-sm);display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;background:var(--bg-secondary);transition:border-color .2s;flex-shrink:0;}
.img-upload-drop:hover{border-color:var(--cta-bg);}
.img-upload-drop img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
.img-upload-drop .drop-ph{text-align:center;padding:.4rem;pointer-events:none;z-index:1;}
.img-upload-drop .drop-ph i{font-size:1.3rem;color:var(--text-muted);display:block;margin-bottom:.3rem;}
.img-upload-drop .drop-ph span{font-size:.67rem;font-family:Montserrat;color:var(--text-muted);line-height:1.3;}
.img-upload-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.img-upload-right{flex:1;min-width:180px;display:flex;flex-direction:column;gap:.45rem;}
.upload-status{font-size:.74rem;font-family:Montserrat;color:var(--text-muted);}
.upload-status.uploading{color:var(--cta-bg);}
.upload-status.done{color:#4caf7d;}
.upload-status.fail{color:#e05c5c;}
.upload-or{font-size:.7rem;font-family:Montserrat;color:var(--text-muted);text-align:center;}
</style>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title"><?php echo $action;?> Pack</h1>
      <a href="<?php echo BASE_URL;?>/admin/packs.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
    <?php if($error):?><div class="admin-alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <?php if($success):?><div class="admin-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success);?></div><?php endif;?>
    <form method="POST">
      <input type="hidden" name="id" value="<?php echo intval($pk['id']);?>">
      <div class="admin-form-card">
        <div class="admin-form-grid">

          <div class="form-group">
            <label class="form-label">Pack Name *</label>
            <input class="form-input" name="name" required value="<?php echo htmlspecialchars($pk['name']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Discount %</label>
            <input class="form-input" type="number" step="0.1" min="0" max="100" name="discount_pct" value="<?php echo htmlspecialchars($pk['discount_pct']);?>">
          </div>

          <div class="form-group admin-form-full">
            <label class="form-label">Pack Image</label>
            <div class="img-upload-widget">
              <div class="img-upload-drop" id="pack-drop">
                <?php if(!empty($pk['image_url'])):?>
                  <img src="<?php echo BASE_URL.'/'.htmlspecialchars($pk['image_url']);?>" id="pack-preview" alt="">
                <?php else:?>
                  <img src="" id="pack-preview" alt="" style="display:none;">
                  <div class="drop-ph"><i class="fa-solid fa-cloud-arrow-up"></i><span>Click or drag image here</span></div>
                <?php endif;?>
                <input type="file" accept="image/*" id="pack-file" aria-label="Upload pack image">
              </div>
              <div class="img-upload-right">
                <span class="upload-status" id="pack-status"><?php echo !empty($pk['image_url'])?'✓ Image set':'No image yet';?></span>
                <span class="upload-or">— or paste a path / URL —</span>
                <input class="form-input" name="image_url" id="pack-url"
                  placeholder="images/uploads/my-pack.jpg"
                  value="<?php echo htmlspecialchars($pk['image_url']);?>">
                <span class="form-hint">Upload from your PC, or enter a path manually.</span>
              </div>
            </div>
          </div>

          <div class="form-group admin-form-full">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description"><?php echo htmlspecialchars($pk['description']);?></textarea>
          </div>

        </div>

        <div style="margin-top:1.25rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <p class="form-label" style="margin-bottom:.75rem;">Select Fragrances for this Pack</p>
          <?php if(empty($all_frags)):?>
            <p class="form-hint">No fragrances yet.</p>
          <?php else:?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.4rem .75rem;max-height:340px;overflow-y:auto;border:1.5px solid var(--border-light);border-radius:var(--radius-sm);padding:.75rem;">
              <?php foreach($all_frags as $fr):?>
                <label style="display:flex;align-items:center;gap:.5em;font-family:Montserrat;font-size:.82rem;cursor:pointer;">
                  <input type="checkbox" name="frag_ids[]" value="<?php echo $fr['id'];?>"
                    style="accent-color:var(--cta-bg);"
                    <?php echo isset($sel_frags[$fr['id']])?'checked':'';?>>
                  <span><?php echo htmlspecialchars($fr['brand']?$fr['brand'].' — '.$fr['name']:$fr['name']);?></span>
                </label>
              <?php endforeach;?>
            </div>
          <?php endif;?>
        </div>

        <div class="form-actions" style="margin-top:1.25rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> <?php echo $action;?> Pack</button>
          <a href="<?php echo BASE_URL;?>/admin/packs.php" class="btn-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
(function(){
  const UPLOAD_URL='<?php echo BASE_URL;?>/admin/upload.php';
  const drop=document.getElementById('pack-drop'),fileIn=document.getElementById('pack-file'),
        preview=document.getElementById('pack-preview'),status=document.getElementById('pack-status'),
        urlIn=document.getElementById('pack-url');
  if(!drop) return;
  urlIn.addEventListener('input',function(){
    const v=this.value.trim();
    if(v){preview.src=v.startsWith('http')?v:'<?php echo BASE_URL;?>/'+v;preview.style.display='block';hidePh();}
  });
  fileIn.addEventListener('change',function(){if(this.files[0])upload(this.files[0]);});
  drop.addEventListener('dragover',function(e){e.preventDefault();this.style.borderColor='var(--cta-bg)';});
  drop.addEventListener('dragleave',function(){this.style.borderColor='';});
  drop.addEventListener('drop',function(e){e.preventDefault();this.style.borderColor='';if(e.dataTransfer.files[0])upload(e.dataTransfer.files[0]);});
  function hidePh(){const ph=drop.querySelector('.drop-ph');if(ph)ph.style.display='none';}
  function upload(file){
    status.textContent='Uploading…';status.className='upload-status uploading';
    const r=new FileReader();r.onload=function(e){preview.src=e.target.result;preview.style.display='block';hidePh();};r.readAsDataURL(file);
    const fd=new FormData();fd.append('image',file);
    fetch(UPLOAD_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
      if(d.success){urlIn.value=d.url;status.textContent='✓ Saved: '+d.url;status.className='upload-status done';}
      else{status.textContent='✗ '+(d.error||'Upload failed');status.className='upload-status fail';}
    }).catch(()=>{status.textContent='✗ Network error';status.className='upload-status fail';});
  }
})();
</script>
<?php include __DIR__ . '/../footer.php'; ?>
