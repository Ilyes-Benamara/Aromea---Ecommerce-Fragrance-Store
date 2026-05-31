<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['delete_id'])) {
        $did = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM notes WHERE id=?");
        $stmt->bind_param('i', $did);
        $msg = $stmt->execute() ? 'Note deleted.' : 'Failed.';
    } elseif (isset($_POST['name'])) {
        $id        = intval($_POST['id'] ?? 0);
        $name      = sanitize($_POST['name']);
        $image_url = sanitize($_POST['image_url'] ?? '');
        if (!$name) { $msg = 'Name required.'; $msg_type='error'; }
        elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE notes SET name=?,image_url=? WHERE id=?");
            $stmt->bind_param('ssi',$name,$image_url,$id);
            $msg = $stmt->execute() ? 'Updated.' : 'Failed.';
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO notes (name,image_url) VALUES (?,?)");
            $stmt->bind_param('ss',$name,$image_url);
            $msg = $stmt->execute() ? 'Note added.' : 'Failed (duplicate?).';
        }
    }
}

$notes = $conn->query("SELECT n.*, (SELECT COUNT(DISTINCT fragrance_id) FROM fragrance_notes WHERE note_id=n.id) AS used FROM notes n ORDER BY n.name")->fetch_all(MYSQLI_ASSOC);

$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    foreach ($notes as $n) if ($n['id']==$eid) { $edit=$n; break; }
}

$page_title = 'Notes';
$admin_page  = 'notes';
include __DIR__ . '/../header.php';
?>
<style>
.img-upload-widget{display:flex;gap:.75rem;align-items:flex-start;flex-wrap:wrap;}
.img-upload-drop{width:80px;height:80px;border:2px dashed var(--border-light);border-radius:50%;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;background:var(--bg-secondary);transition:border-color .2s;flex-shrink:0;}
.img-upload-drop:hover{border-color:var(--cta-bg);}
.img-upload-drop img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;}
.img-upload-drop .drop-ph{text-align:center;padding:.2rem;pointer-events:none;z-index:1;}
.img-upload-drop .drop-ph i{font-size:1.1rem;color:var(--text-muted);display:block;}
.img-upload-drop .drop-ph span{font-size:.6rem;font-family:Montserrat;color:var(--text-muted);}
.img-upload-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.img-upload-right{flex:1;display:flex;flex-direction:column;gap:.35rem;}
.upload-status{font-size:.72rem;font-family:Montserrat;color:var(--text-muted);}
.upload-status.uploading{color:var(--cta-bg);}
.upload-status.done{color:#4caf7d;}
.upload-status.fail{color:#e05c5c;}
</style>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header"><h1 class="admin-page-title">Notes</h1></div>
    <?php if($msg):?><div class="admin-alert <?php echo $msg_type;?>"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg);?></div><?php endif;?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
      <div class="admin-form-card">
        <p class="form-label" style="margin-bottom:.85rem;"><?php echo $edit?'Edit Note':'Add New Note';?></p>
        <form method="POST">
          <input type="hidden" name="id" value="<?php echo $edit?intval($edit['id']):0;?>">
          <div class="form-group" style="margin-bottom:.75rem;">
            <label class="form-label">Name *</label>
            <input class="form-input" name="name" required value="<?php echo $edit?htmlspecialchars($edit['name']):'';?>" placeholder="e.g. Bergamot, Oud…">
          </div>
          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">Note Image <span class="form-hint" style="margin:0 0 0 .3em;">(shown in pyramid)</span></label>
            <div class="img-upload-widget">
              <div class="img-upload-drop" id="note-drop">
                <?php $ni = $edit?$edit['image_url']:'';?>
                <?php if($ni):?>
                  <img src="<?php echo BASE_URL.'/'.htmlspecialchars($ni);?>" id="note-preview" alt="">
                <?php else:?>
                  <img src="" id="note-preview" alt="" style="display:none;">
                  <div class="drop-ph"><i class="fa-solid fa-leaf"></i><span>Upload</span></div>
                <?php endif;?>
                <input type="file" accept="image/*" id="note-file" aria-label="Upload note image">
              </div>
              <div class="img-upload-right">
                <span class="upload-status" id="note-status"><?php echo $ni?'✓ Image set':'No image';?></span>
                <input class="form-input" name="image_url" id="note-url"
                  placeholder="images/notes/bergamot.jpg" style="font-size:.78rem;"
                  value="<?php echo $ni?htmlspecialchars($ni):'';?>">
                <span class="form-hint" style="font-size:.67rem;">Upload or paste path</span>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> <?php echo $edit?'Update':'Add';?></button>
            <?php if($edit):?><a href="<?php echo BASE_URL;?>/admin/notes.php" class="btn-secondary">Cancel</a><?php endif;?>
          </div>
        </form>
      </div>

      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Image</th><th>Used in</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if(empty($notes)):?>
            <tr><td colspan="4"><div class="admin-empty"><i class="fa-solid fa-leaf"></i>None yet.</div></td></tr>
          <?php else: foreach($notes as $n):?>
            <tr>
              <td><?php echo htmlspecialchars($n['name']);?></td>
              <td><?php if($n['image_url']):?><img src="<?php echo BASE_URL.'/'.htmlspecialchars($n['image_url']);?>" style="width:28px;height:28px;object-fit:cover;border-radius:50%;"><?php else:?>—<?php endif;?></td>
              <td class="td-muted"><?php echo $n['used'];?> fragrances</td>
              <td>
                <div class="admin-table-actions">
                  <a href="?edit=<?php echo $n['id'];?>" class="btn-edit">Edit</a>
                  <form method="POST" onsubmit="return confirm('Delete?');" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $n['id'];?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif;?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script>
(function(){
  const UPLOAD_URL='<?php echo BASE_URL;?>/admin/upload.php';
  const drop=document.getElementById('note-drop'),fileIn=document.getElementById('note-file'),
        preview=document.getElementById('note-preview'),status=document.getElementById('note-status'),
        urlIn=document.getElementById('note-url');
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
      if(d.success){urlIn.value=d.url;status.textContent='✓ '+d.url;status.className='upload-status done';}
      else{status.textContent='✗ '+(d.error||'Upload failed');status.className='upload-status fail';}
    }).catch(()=>{status.textContent='✗ Network error';status.className='upload-status fail';});
  }
})();
</script>
<?php include __DIR__ . '/../footer.php'; ?>
