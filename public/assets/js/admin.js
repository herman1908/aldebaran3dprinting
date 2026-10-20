/* =========================================================
   Admin — upload foto produk ke Cloudinary (unsigned preset)
   Mendukung banyak foto sekaligus (maks. 5).
   URL hasil upload ditambahkan ke #galeriUrls (satu per baris);
   foto pertama otomatis jadi foto utama.
   ========================================================= */
(function () {
  'use strict';

  var MAX_FOTO = 5;

  var cfg       = window.CLOUDINARY || {};
  var fileInput = document.getElementById('fileGambar');
  var area      = document.getElementById('uploadArea');
  var progress  = document.getElementById('uploadProgress');
  var bar       = progress ? progress.querySelector('div') : null;
  var urlArea   = document.getElementById('galeriUrls');
  var grid      = document.getElementById('fotoGrid');
  var hint      = document.getElementById('fotoHint');

  if (!fileInput || !urlArea) return;

  function daftar() {
    return urlArea.value.split('\n')
      .map(function (s) { return s.trim(); })
      .filter(Boolean);
  }

  function simpan(list) {
    urlArea.value = list.join('\n');
    render();
  }

  function render() {
    var list = daftar();

    if (grid) {
      grid.innerHTML = '';
      list.forEach(function (url, i) {
        var kartu = document.createElement('div');
        kartu.className = 'admin-foto-item' + (i === 0 ? ' utama' : '');

        var img = document.createElement('img');
        img.src = url;
        img.alt = 'Foto ' + (i + 1);
        img.loading = 'lazy';

        var tag = document.createElement('span');
        tag.className = 'admin-foto-tag';
        tag.textContent = i === 0 ? 'Utama' : String(i + 1);

        var hapus = document.createElement('button');
        hapus.type = 'button';
        hapus.className = 'admin-foto-hapus';
        hapus.textContent = '✕';
        hapus.title = 'Hapus foto';
        hapus.setAttribute('aria-label', 'Hapus foto ' + (i + 1));
        hapus.addEventListener('click', function () {
          var l = daftar();
          l.splice(i, 1);
          simpan(l);
        });

        kartu.appendChild(img);
        kartu.appendChild(tag);
        kartu.appendChild(hapus);
        grid.appendChild(kartu);
      });
    }

    if (hint) {
      if (!list.length) {
        hint.textContent = 'Belum ada foto. Upload atau tempel URL di atas.';
      } else if (list.length === 1) {
        hint.textContent = '1 / ' + MAX_FOTO + ' foto · ini jadi foto utama.';
      } else {
        hint.textContent = list.length + ' / ' + MAX_FOTO + ' foto · foto pertama jadi utama.';
      }
      hint.classList.toggle('penuh', list.length >= MAX_FOTO);
    }
  }

  function setProgress(pct) {
    if (progress) progress.hidden = pct === null;
    if (bar) bar.style.width = (pct === null ? 0 : pct) + '%';
  }

  function uploadSatu(file) {
    return new Promise(function (resolve, reject) {
      var fd = new FormData();
      fd.append('file', file);
      fd.append('upload_preset', cfg.preset);
      if (cfg.folder) {
        fd.append('folder', cfg.folder);
        fd.append('asset_folder', cfg.folder);
      }

      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.uploadUrl, true);

      xhr.addEventListener('load', function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res.secure_url) return resolve(res.secure_url);
            reject('respons tidak berisi secure_url');
          } catch (err) {
            reject('respons tidak valid');
          }
        } else {
          var pesan = 'HTTP ' + xhr.status;
          try {
            var err = JSON.parse(xhr.responseText);
            if (err && err.error && err.error.message) pesan += ' — ' + err.error.message;
          } catch (e) { /* abaikan */ }
          reject(pesan);
        }
      });
      xhr.addEventListener('error', function () {
        reject('koneksi ke Cloudinary bermasalah');
      });

      xhr.send(fd);
    });
  }

  var sibuk = false;

  function proses(fileList) {
    if (sibuk) return;

    var files = Array.prototype.slice.call(fileList || []).filter(function (f) {
      return f && f.type && f.type.match(/^image\//);
    });
    if (!files.length) return;

    var list = daftar();
    var sisa = MAX_FOTO - list.length;

    if (sisa <= 0) {
      alert('Maksimal ' + MAX_FOTO + ' foto per produk. Hapus salah satu foto dulu untuk menambah.');
      if (fileInput) fileInput.value = '';
      return;
    }
    if (files.length > sisa) {
      alert('Maksimal ' + MAX_FOTO + ' foto per produk. Kamu memilih ' + files.length +
            ' foto, tapi sisa slot hanya ' + sisa + '. Hanya ' + sisa + ' foto pertama yang diupload.');
      files = files.slice(0, sisa);
    }

    sibuk = true;
    setProgress(0);
    if (area) area.classList.add('mengunggah');

    var selesai = 0;
    var gagal = 0;

    function lanjut() {
      if (selesai >= files.length) {
        setProgress(null);
        sibuk = false;
        if (area) area.classList.remove('mengunggah');
        if (fileInput) fileInput.value = '';
        if (gagal > 0) alert(gagal + ' foto gagal diupload. Coba ulangi atau tempel URL manual.');
        return;
      }
      uploadSatu(files[selesai]).then(function (url) {
        var l = daftar();
        if (l.length < MAX_FOTO && l.indexOf(url) === -1) { l.push(url); simpan(l); }
      }).catch(function (err) {
        gagal++;
        alert('Upload gagal: ' + err);
      }).then(function () {
        selesai++;
        setProgress(Math.round((selesai / files.length) * 100));
        lanjut();
      });
    }
    lanjut();
  }

  fileInput.addEventListener('change', function () { proses(this.files); });

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
      proses(e.dataTransfer.files);
    });
  }

  // Tempel/ubah URL manual: render ulang + peringatan bila melebihi batas.
  urlArea.addEventListener('input', render);
  urlArea.addEventListener('change', function () {
    if (daftar().length > MAX_FOTO) {
      alert('Maksimal ' + MAX_FOTO + ' foto per produk. Kelebihan foto tidak akan disimpan.');
    }
  });

  render();
})();

/* =========================================================
   Admin — editor pilihan warna produk (tambah/hapus baris)
   ========================================================= */
(function () {
  'use strict';

  var MAX_WARNA = 12;
  var editor = document.getElementById('warnaEditor');
  var tambah = document.getElementById('tambahWarna');
  if (!editor || !tambah) return;

  function barisBaru(nama, hex) {
    var row = document.createElement('div');
    row.className = 'warna-baris';

    var warna = document.createElement('input');
    warna.type = 'color';
    warna.className = 'warna-hex';
    warna.name = 'warna_hex[]';
    warna.value = hex || '#FFB3C7';
    warna.setAttribute('aria-label', 'Kode warna');

    var teks = document.createElement('input');
    teks.type = 'text';
    teks.className = 'admin-input warna-nama';
    teks.name = 'warna_nama[]';
    teks.maxLength = 30;
    teks.placeholder = 'Nama warna';
    teks.value = nama || '';

    var hapus = document.createElement('button');
    hapus.type = 'button';
    hapus.className = 'warna-hapus';
    hapus.textContent = '✕';
    hapus.setAttribute('aria-label', 'Hapus warna');

    row.appendChild(warna);
    row.appendChild(teks);
    row.appendChild(hapus);
    return row;
  }

  tambah.addEventListener('click', function () {
    if (editor.querySelectorAll('.warna-baris').length >= MAX_WARNA) {
      alert('Maksimal ' + MAX_WARNA + ' pilihan warna.');
      return;
    }
    var row = barisBaru('', '#FFB3C7');
    editor.appendChild(row);
    var input = row.querySelector('.warna-nama');
    if (input) input.focus();
  });

  editor.addEventListener('click', function (ev) {
    var tombol = ev.target.closest('.warna-hapus');
    if (!tombol) return;
    var row = tombol.closest('.warna-baris');
    if (row) row.remove();
  });
})();
