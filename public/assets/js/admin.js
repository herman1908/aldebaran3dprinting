/* =========================================================
   Admin — upload gambar ke Cloudinary (unsigned preset)
   Hasil secure_url ditulis ke input #gambarUrl lalu ikut
   disubmit bersama form produk.
   ========================================================= */
(function () {
  'use strict';

  var cfg = window.CLOUDINARY || {};
  var fileInput = document.getElementById('fileGambar');
  var preview   = document.getElementById('previewGambar');
  var progress  = document.getElementById('uploadProgress');
  var bar       = progress ? progress.querySelector('div') : null;
  var urlInput  = document.getElementById('gambarUrl');
  var area      = document.getElementById('uploadArea');

  if (!fileInput) return;

  function tampilkanPreview(file) {
    if (!preview) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.hidden = false;
    };
    reader.readAsDataURL(file);
  }

  function setProgress(pct) {
    if (progress) progress.hidden = pct === null;
    if (bar) bar.style.width = (pct === null ? 0 : pct) + '%';
  }

  function upload(file) {
    if (!file || !file.type.match(/^image\//)) return;

    tampilkanPreview(file);
    setProgress(0);

    var fd = new FormData();
    fd.append('file', file);
    fd.append('upload_preset', cfg.preset);
    if (cfg.folder) {
      // Folder Media Library tujuan (unsigned upload mengizinkan folder/asset_folder).
      fd.append('folder', cfg.folder);
      fd.append('asset_folder', cfg.folder);
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', cfg.uploadUrl, true);

    xhr.upload.addEventListener('progress', function (ev) {
      if (ev.lengthComputable) setProgress(Math.round((ev.loaded / ev.total) * 100));
    });

    xhr.addEventListener('load', function () {
      setProgress(null);
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.secure_url) {
            if (urlInput) urlInput.value = res.secure_url;
            if (area) area.classList.add('sukses');
          } else {
            alert('Upload gagal: respons tidak berisi secure_url.');
          }
        } catch (err) {
          alert('Upload gagal: respons tidak valid.');
        }
      } else {
        // Ambil pesan error asli dari Cloudinary agar mudah di-debug.
        var pesan = 'HTTP ' + xhr.status;
        try {
          var err = JSON.parse(xhr.responseText);
          if (err && err.error && err.error.message) {
            pesan += ' — ' + err.error.message;
          }
        } catch (e) { /* abaikan jika bukan JSON */ }
        pesan += '\n\nCloud name : ' + (cfg.cloudName || '(kosong)');
        pesan += '\nPreset     : ' + (cfg.preset || '(kosong)');
        pesan += '\nFolder     : ' + (cfg.folder || '(root)');
        pesan += '\n\nCek: cloud name & preset harus benar, dan preset bertipe UNSIGNED.';
        alert('Upload gagal. ' + pesan);
      }
    });

    xhr.addEventListener('error', function () {
      setProgress(null);
      alert('Upload gagal: koneksi ke Cloudinary bermasalah.');
    });

    xhr.send(fd);
  }

  fileInput.addEventListener('change', function () {
    if (this.files && this.files[0]) upload(this.files[0]);
  });

  // Drag & drop
  if (area) {
    area.addEventListener('dragover', function (e) {
      e.preventDefault();
      area.classList.add('hover');
    });
    area.addEventListener('dragleave', function () {
      area.classList.remove('hover');
    });
    area.addEventListener('drop', function (e) {
      e.preventDefault();
      area.classList.remove('hover');
      if (e.dataTransfer.files && e.dataTransfer.files[0]) upload(e.dataTransfer.files[0]);
    });
  }
})();
