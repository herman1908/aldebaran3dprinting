(function () {
    var tombolMenu = document.getElementById('tombolMenu');
    var sidebar    = document.getElementById('sidebar');
    var lapisan    = document.getElementById('lapisan');
    var keAtas     = document.getElementById('keAtas');

    function toggleMenu(buka) {
        if (!sidebar) return;
        sidebar.classList.toggle('buka', buka);
        if (lapisan) lapisan.classList.toggle('tampil', buka);
    }

    if (tombolMenu) {
        tombolMenu.addEventListener('click', function () {
            toggleMenu(!sidebar.classList.contains('buka'));
        });
    }
    if (lapisan) {
        lapisan.addEventListener('click', function () { toggleMenu(false); });
    }

    window.addEventListener('scroll', function () {
        if (keAtas) keAtas.classList.toggle('tampil', window.scrollY > 400);
    }, { passive: true });

    if (keAtas) {
        keAtas.addEventListener('click', function (ev) {
            ev.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Qty stepper di halaman produk
    document.querySelectorAll('.qty-stepper button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input[type=number]');
            if (!input) return;
            var step = parseInt(btn.getAttribute('data-step'), 10) || 1;
            var val = (parseInt(input.value, 10) || 1) + step;
            val = Math.max(1, Math.min(99, val));
            input.value = val;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            btn.animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(1.25)' }, { transform: 'scale(1)' }],
                { duration: 220 }
            );
        });
    });

    // Warna picker (visual, aesthetic)
    var dots = document.querySelectorAll('.warna-dot');
    dots.forEach(function (d) {
        d.addEventListener('click', function () {
            dots.forEach(function (x) {
                x.classList.remove('aktif');
                x.setAttribute('aria-pressed', 'false');
            });
            d.classList.add('aktif');
            d.setAttribute('aria-pressed', 'true');

            var label = document.getElementById('warnaTerpilih');
            var input = document.getElementById('warnaInput');
            var nama = d.getAttribute('data-nama');
            if (label && nama) label.textContent = nama;
            if (input && nama) input.value = nama;

            // Gambar utama ikut berganti sesuai warna yang dipilih.
            var gambar = d.getAttribute('data-gambar');
            var utama = document.getElementById('gambarUtama');
            if (gambar && utama) utama.src = gambar;

            var idxGambar = d.getAttribute('data-gambar-index');
            document.querySelectorAll('.thumb-btn').forEach(function (t) {
                t.classList.toggle('aktif', t.getAttribute('data-index') === idxGambar);
            });
        });
    });

    // Reveal on scroll — bikin kartu muncul satu-satu, aesthetic
    var revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en, idx) {
                if (en.isIntersecting) {
                    setTimeout(function () {
                        en.target.classList.add('muncul');
                    }, (idx % 6) * 70);
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.08 });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('muncul'); });
    }

    // Hero carousel: 4 produk unggulan
    (function () {
        var carousel = document.getElementById('heroCarousel');
        var track = document.getElementById('heroTrack');
        if (!carousel || !track) return;
        var slides = track.querySelectorAll('.hero-slide');
        var dots = carousel.querySelectorAll('.hero-dot');
        var prev = document.getElementById('heroPrev');
        var next = document.getElementById('heroNext');
        var counter = document.getElementById('heroCounter');
        var progress = document.getElementById('heroProgress');
        var pauseBtn = document.getElementById('heroPause');
        var total = slides.length;
        var idx = 0;
        var timer = null;
        var paused = false;
        if (!total) return;
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function jalanProgress() {
            if (!progress) return;
            progress.classList.remove('jalan');
            void progress.offsetWidth;
            progress.classList.add('jalan');
        }
        function go(i) {
            idx = (i + total) % total;
            track.style.transform = 'translateX(-' + (idx * 100) + '%)';
            slides.forEach(function (s, k) {
                s.classList.toggle('aktif', k === idx);
                s.setAttribute('aria-hidden', k === idx ? 'false' : 'true');
                var link = s.querySelector('.hero-slide-img');
                if (link) link.setAttribute('tabindex', k === idx ? '0' : '-1');
            });
            dots.forEach(function (d, k) { d.classList.toggle('aktif', k === idx); });
            if (counter) counter.textContent = pad(idx + 1) + ' / ' + pad(total);
            jalanProgress();
        }
        function auto() {
            stop();
            if (paused) return;
            timer = setInterval(function () { go(idx + 1); }, 5000);
        }
        function stop() { if (timer) { clearInterval(timer); timer = null; } }
        function setPaused(p) {
            paused = p;
            carousel.classList.toggle('dijeda', p);
            if (pauseBtn) {
                pauseBtn.textContent = p ? '▶' : '❚❚';
                pauseBtn.setAttribute('aria-pressed', p ? 'true' : 'false');
                pauseBtn.setAttribute('aria-label', p ? 'Putar slide otomatis' : 'Jeda slide otomatis');
            }
            if (p) stop(); else auto();
        }
        if (prev) prev.addEventListener('click', function () { go(idx - 1); auto(); });
        if (next) next.addEventListener('click', function () { go(idx + 1); auto(); });
        dots.forEach(function (d) {
            d.addEventListener('click', function () {
                go(parseInt(d.getAttribute('data-go'), 10) || 0);
                auto();
            });
        });
        if (pauseBtn) pauseBtn.addEventListener('click', function () { setPaused(!paused); });
        function hoverStop() { stop(); carousel.classList.add('dijeda'); }
        function hoverStart() { if (!paused) carousel.classList.remove('dijeda'); auto(); }
        carousel.addEventListener('mouseenter', hoverStop);
        carousel.addEventListener('mouseleave', hoverStart);
        carousel.addEventListener('focusin', hoverStop);
        carousel.addEventListener('focusout', hoverStart);
        var tx = null;
        track.addEventListener('touchstart', function (ev) {
            tx = ev.touches[0].clientX;
        }, { passive: true });
        track.addEventListener('touchend', function (ev) {
            if (tx === null) return;
            var dx = ev.changedTouches[0].clientX - tx;
            if (Math.abs(dx) > 40) go(idx + (dx < 0 ? 1 : -1));
            tx = null;
            auto();
        }, { passive: true });
        go(0);
        auto();
    })();

    // Galeri thumbs: klik B/C/D untuk ganti gambar utama
    var thumbs = document.querySelectorAll('.thumb-btn');
    var gambarUtamaPre = document.getElementById('gambarUtama');
    if (thumbs.length && gambarUtamaPre) {
        thumbs.forEach(function (t) {
            t.addEventListener('click', function () {
                thumbs.forEach(function (x) { x.classList.remove('aktif'); });
                t.classList.add('aktif');
                gambarUtamaPre.src = t.getAttribute('data-src');
            });
        });
    }

    // Lightbox: klik gambar produk untuk enlarge ke ukuran asli
    var gambarUtama = document.getElementById('gambarUtama');
    if (gambarUtama) {
        var box = document.createElement('div');
        box.className = 'lightbox';
        box.innerHTML = '<button class="lightbox-tutup" aria-label="Tutup">✕</button><img alt="">';
        document.body.appendChild(box);
        var boxImg = box.querySelector('img');
        function bukaBox() {
            boxImg.src = gambarUtama.src;
            boxImg.alt = gambarUtama.alt;
            box.classList.add('tampil');
            document.body.style.overflow = 'hidden';
        }
        function tutupBox() {
            box.classList.remove('tampil');
            document.body.style.overflow = '';
        }
        gambarUtama.addEventListener('click', bukaBox);
        box.querySelector('.lightbox-tutup').addEventListener('click', tutupBox);
        box.addEventListener('click', function (ev) { if (ev.target === box) tutupBox(); });
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') tutupBox(); });
    }

    // Micro-feedback: tombol beli di halaman detail berdenyut halus saat diklik
    document.querySelectorAll('.form-beli button[type=submit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(.96)' }, { transform: 'scale(1)' }],
                { duration: 160 }
            );
        });
    });

    function perbaruiLencana(jumlah) {
        var el = document.getElementById('lencanaKeranjang');
        if (!el) return;
        jumlah = parseInt(jumlah, 10) || 0;
        el.textContent = jumlah;
        if (jumlah > 0) el.removeAttribute('hidden');
        else el.setAttribute('hidden', '');
        el.classList.remove('lonjak');
        void el.offsetWidth;
        el.classList.add('lonjak');
    }

    // Keranjang: subtotal terakumulasi langsung saat jumlah diubah (tanpa Enter)
    var tabelKeranjang = document.querySelector('.tabel-keranjang');
    if (tabelKeranjang) {
        var ringkasan = document.querySelector('.ringkasan');
        var ongkirAktif = ringkasan ? (parseInt(ringkasan.getAttribute('data-ongkir'), 10) || 0) : 0;
        var antreanSimpan = Promise.resolve();
        var sibukSimpan = 0;
        var timerQty = null;
        var kotor = [];

        function rupiahJs(n) {
            return 'Rp ' + (Math.round(n) || 0).toLocaleString('id-ID');
        }

        function hitungUlang() {
            var subtotal = 0;
            tabelKeranjang.querySelectorAll('tr[data-harga]').forEach(function (tr) {
                var input = tr.querySelector('input[name="qty"]');
                if (!input) return;
                var harga = parseInt(tr.getAttribute('data-harga'), 10) || 0;
                var qty = parseInt(input.value, 10);
                if (!qty || qty < 1) qty = 1;
                if (qty > 99) qty = 99;
                var sub = harga * qty;
                subtotal += sub;
                var sel = tr.querySelector('[data-subtotal-baris]');
                if (sel) sel.textContent = rupiahJs(sub);
            });

            var elSub = document.getElementById('subtotalProduk');
            if (elSub) elSub.textContent = rupiahJs(subtotal);
            var elTotal = document.getElementById('totalBelanja');
            if (elTotal) elTotal.textContent = rupiahJs(subtotal + ongkirAktif);
        }

        function tandaiKotor(form) {
            if (kotor.indexOf(form) === -1) kotor.push(form);
        }

        function simpanQty(form) {
            if (!window.fetch || !window.FormData) return;
            var pos = kotor.indexOf(form);
            if (pos !== -1) kotor.splice(pos, 1);

            var data = new FormData(form);
            sibukSimpan++;
            antreanSimpan = antreanSimpan.then(function () {
                return fetch(form.getAttribute('action') || 'keranjang.php', {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (res) {
                    return res.json();
                }).then(function (hasil) {
                    if (hasil && hasil.ok) perbaruiLencana(hasil.jumlah);
                });
            }).catch(function () {
                tandaiKotor(form);
            }).then(function () {
                sibukSimpan--;
            });
        }

        function simpanSemuaQty() {
            if (timerQty) { clearTimeout(timerQty); timerQty = null; }
            kotor.slice().forEach(simpanQty);
        }

        tabelKeranjang.querySelectorAll('.form-qty input[name="qty"]').forEach(function (input) {
            input.addEventListener('input', function () {
                hitungUlang();
                tandaiKotor(input.form);
                if (timerQty) clearTimeout(timerQty);
                timerQty = setTimeout(simpanSemuaQty, 450);
            });
            input.addEventListener('change', function () {
                var qty = parseInt(input.value, 10);
                if (!qty || qty < 1) qty = 1;
                if (qty > 99) qty = 99;
                input.value = qty;
                hitungUlang();
                tandaiKotor(input.form);
                simpanSemuaQty();
            });
        });

        // Tunggu jumlah tersimpan sebelum lanjut checkout.
    var accStatistik = document.getElementById('statistik');
    if (accStatistik && accStatistik.tagName === 'DETAILS') {
        var bukaStatistik = function () {
            if (window.location.hash === '#statistik') accStatistik.open = true;
        };
        bukaStatistik();
        window.addEventListener('hashchange', bukaStatistik);
    }

    document.querySelectorAll('form').forEach(function (form) {
            var aksi = form.querySelector('input[name="aksi"]');
            if (!aksi || aksi.value !== 'checkout') return;
            form.addEventListener('submit', function (ev) {
                if (!kotor.length && !sibukSimpan && !timerQty) return;
                ev.preventDefault();
                simpanSemuaQty();
                antreanSimpan.then(function () { form.submit(); });
            });
        });
    }

    document.querySelectorAll('form').forEach(function (form) {
        var aksi = form.querySelector('input[name="aksi"]');
        if (!aksi || aksi.value !== 'tambah') return;

        form.addEventListener('submit', function (ev) {
            if (!window.fetch || !window.FormData) return;
            ev.preventDefault();

            var btn = form.querySelector('button[type=submit]');
            if (btn && btn.disabled) return;
            if (btn) btn.disabled = true;

            fetch(form.getAttribute('action') || 'keranjang.php', {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.ok) throw new Error('gagal');
                perbaruiLencana(data.jumlah);
                if (btn) {
                    btn.animate(
                        [{ transform: 'scale(1)' }, { transform: 'scale(.94)' }, { transform: 'scale(1)' }],
                        { duration: 220 }
                    );
                    setTimeout(function () { btn.disabled = false; }, 600);
                }
            }).catch(function () {
                if (btn) btn.disabled = false;
                form.submit();
            });
        });
    });
})();
