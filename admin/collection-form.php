<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$action = 'Add'; $error = ''; $success = '';
$col = ['id'=>0,'name'=>'','slug'=>'','description'=>'','image_url'=>'','category'=>'other',
        'is_featured'=>0,'show_on_home'=>0,'home_order'=>0,'sale_price'=>''];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM collections WHERE id=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) { $col = $row; $action = 'Edit'; }
}

$posted_id = isset($_POST['id']) ? intval($_POST['id']) : -1;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id          = $posted_id;
    $name        = sanitize($_POST['name'] ?? '');
    $slug        = sanitize(strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$_POST['name']??''), '-')));
    $description = sanitize($_POST['description'] ?? '');
    $image_url   = sanitize($_POST['image_url'] ?? '');
    $category    = in_array($_POST['category']??'', ['designer','niche','arabic','other']) ? $_POST['category'] : 'other';
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
    $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
    $home_order   = intval($_POST['home_order'] ?? 0);
    $sale_price   = $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : null;
    $selected_frags = isset($_POST['fragrance_ids']) && is_array($_POST['fragrance_ids'])
                    ? array_map('intval', $_POST['fragrance_ids']) : [];

    if (!$name) {
        $error = 'Name is required.';
    } elseif ($sale_price === null) {
        $error = 'Collection price is required.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE collections SET name=?,slug=?,description=?,image_url=?,category=?,is_featured=?,show_on_home=?,home_order=?,sale_price=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sssssiiidi',$name,$slug,$description,$image_url,$category,$is_featured,$show_on_home,$home_order,$sale_price,$id);
            $ok = $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO collections (name,slug,description,image_url,category,is_featured,show_on_home,home_order,sale_price) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssiiid',$name,$slug,$description,$image_url,$category,$is_featured,$show_on_home,$home_order,$sale_price);
            $ok = $stmt->execute();
            if ($ok) $id = $stmt->insert_id;
        }

        if ($ok && $id > 0) {
            // Sync fragrance membership: clear current, then re-assign selected.
            $conn->query("UPDATE fragrances SET collection_id=NULL WHERE collection_id=$id");
            if (!empty($selected_frags)) {
                $in = implode(',', $selected_frags);
                $conn->query("UPDATE fragrances SET collection_id=$id WHERE id IN ($in)");
            }
            if ($posted_id === 0) { header('Location: ' . BASE_URL . '/admin/collections.php'); exit; }
            $success = 'Saved.';
        } else {
            $error = 'Save failed.';
        }
    }
    $col = compact('id','name','slug','description','image_url','category','is_featured','show_on_home','home_order','sale_price');
}

// Fragrance picker data
$all_frags = [];
$res = $conn->query("SELECT id, name, brand, price FROM fragrances ORDER BY brand ASC, name ASC");
if ($res) while ($r = $res->fetch_assoc()) $all_frags[] = $r;

$current_ids = [];
if (!empty($col['id'])) {
    $rs = $conn->query("SELECT id FROM fragrances WHERE collection_id=" . intval($col['id']));
    if ($rs) while ($r = $rs->fetch_assoc()) $current_ids[] = (int)$r['id'];
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fragrance_ids']) && is_array($_POST['fragrance_ids'])) {
    $current_ids = array_map('intval', $_POST['fragrance_ids']);
}

$page_title = $action . ' Collection';
$admin_page = 'collections';
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
      <h1 class="admin-page-title"><?php echo $action; ?> Collection</h1>
      <a href="<?php echo BASE_URL; ?>/admin/collections.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
    <?php if($error):?><div class="admin-alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error);?></div><?php endif;?>
    <?php if($success):?><div class="admin-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success);?></div><?php endif;?>
    <form method="POST">
      <input type="hidden" name="id" value="<?php echo intval($col['id']);?>">
      <div class="admin-form-card">
        <div class="admin-form-grid">

          <div class="form-group">
            <label class="form-label">Name *</label>
            <input class="form-input" name="name" required value="<?php echo htmlspecialchars($col['name']);?>">
          </div>

          <div class="form-group">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
              <?php foreach(['designer'=>'Designer','niche'=>'Niche','arabic'=>'Arabic','other'=>'Other'] as $v=>$l):?>
                <option value="<?php echo $v;?>" <?php echo ($col['category']==$v)?'selected':'';?>><?php echo $l;?></option>
              <?php endforeach;?>
            </select>
          </div>

          <div class="form-group admin-form-full">
            <label class="form-label">Collection Image</label>
            <div class="img-upload-widget">
              <div class="img-upload-drop" id="col-drop">
                <?php if(!empty($col['image_url'])):?>
                  <img src="<?php echo BASE_URL.'/'.htmlspecialchars($col['image_url']);?>" id="col-preview" alt="">
                <?php else:?>
                  <img src="" id="col-preview" alt="" style="display:none;">
                  <div class="drop-ph"><i class="fa-solid fa-cloud-arrow-up"></i><span>Click or drag image here</span></div>
                <?php endif;?>
                <input type="file" accept="image/*" id="col-file" aria-label="Upload collection image">
              </div>
              <div class="img-upload-right">
                <span class="upload-status" id="col-status"><?php echo !empty($col['image_url'])?'✓ Image set':'No image yet';?></span>
                <span class="upload-or">— or paste a path / URL —</span>
                <input class="form-input" name="image_url" id="col-url"
                  placeholder="images/uploads/my-collection.jpg"
                  value="<?php echo htmlspecialchars($col['image_url']);?>">
                <span class="form-hint">Upload from your PC, or enter a path manually.</span>
              </div>
            </div>
          </div>

          <div class="form-group admin-form-full">
            <label class="form-label">Description</label>
            <textarea class="form-textarea" name="description"><?php echo htmlspecialchars($col['description']);?></textarea>
          </div>

          <!-- ── Home & Featured Settings ── -->
          <div class="form-group">
            <label class="form-label">Homepage Display</label>
            <div style="display:flex;flex-direction:column;gap:0.6rem;margin-top:0.25rem;">
              <label style="display:flex;align-items:center;gap:0.6rem;font-family:Montserrat;font-size:0.85rem;font-weight:600;cursor:pointer;">
                <input type="checkbox" name="is_featured" value="1" <?php echo !empty($col['is_featured'])?'checked':'';?>
                       style="width:16px;height:16px;accent-color:var(--cta-bg);">
                Featured collection (shown in home carousel)
              </label>
              <label style="display:flex;align-items:center;gap:0.6rem;font-family:Montserrat;font-size:0.85rem;font-weight:600;cursor:pointer;">
                <input type="checkbox" name="show_on_home" value="1" <?php echo !empty($col['show_on_home'])?'checked':'';?>
                       style="width:16px;height:16px;accent-color:var(--cta-bg);">
                Show in "Popular Collections" on home page
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Home Display Order</label>
            <input class="form-input" type="number" name="home_order" min="0" value="<?php echo intval($col['home_order']??0);?>">
            <span class="form-hint">Lower = appears first (0 = default)</span>
          </div>

          <div class="form-group">
            <label class="form-label">Collection Price (DZD) *</label>
            <input class="form-input" type="number" name="sale_price" min="0" step="0.01" required
                   placeholder="e.g. 18000"
                   value="<?php echo htmlspecialchars($col['sale_price']??'');?>">
            <span class="form-hint">You set this price directly &mdash; it is not the sum of the included fragrances.</span>
          </div>

          <div class="form-group admin-form-full">
            <label class="form-label">Fragrances in this collection</label>
            <div class="form-hint" style="margin-bottom:0.5rem;">Tick every fragrance that belongs to this collection. The collection price above stays whatever you typed.</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0.4rem 1rem;max-height:320px;overflow-y:auto;border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:0.75rem;background:var(--bg-secondary);">
              <?php if (empty($all_frags)): ?>
                <div class="td-muted">No fragrances yet. Add some from the Fragrances page.</div>
              <?php else: foreach ($all_frags as $f):
                $checked = in_array((int)$f['id'], $current_ids, true) ? 'checked' : '';
              ?>
                <label style="display:flex;align-items:center;gap:0.5rem;font-family:Montserrat;font-size:0.82rem;cursor:pointer;">
                  <input type="checkbox" name="fragrance_ids[]" value="<?php echo (int)$f['id'];?>" <?php echo $checked;?>
                         style="width:15px;height:15px;accent-color:var(--cta-bg);">
                  <span><?php echo htmlspecialchars(($f['brand'] ? $f['brand'].' — ' : '').$f['name']);?>
                    <span class="td-muted">(<?php echo number_format((float)$f['price'], 0);?> DZD)</span>
                  </span>
                </label>
              <?php endforeach; endif; ?>
            </div>
          </div>


        </div>
        <div class="form-actions" style="margin-top:1.25rem;border-top:1px solid var(--border-light);padding-top:1.25rem;">
          <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> <?php echo $action;?></button>
          <a href="<?php echo BASE_URL;?>/admin/collections.php" class="btn-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </main>
</div>
<script>
(function(){
  const UPLOAD_URL='<?php echo BASE_URL;?>/admin/upload.php';
  function initWidget(dropId,fileId,previewId,statusId,urlId){
    const drop=document.getElementById(dropId),fileIn=document.getElementById(fileId),
          preview=document.getElementById(previewId),status=document.getElementById(statusId),
          urlIn=document.getElementById(urlId);
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
  }
  initWidget('col-drop','col-file','col-preview','col-status','col-url');
})();
</script>
<?php include __DIR__ . '/../footer.php'; ?>
