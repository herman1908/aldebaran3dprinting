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
            dots.forEach(function (x) { x.style.outline = 'none'; x.style.transform = ''; });
            d.style.outline = '3px solid #8B5CF6';
            d.style.outlineOffset = '2px';
            d.style.transform = 'scale(1.2)';
        });
    });
    if (dots.length) dots[0].click();

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

    // Hati melayang saat klik tombol + keranjang
    document.querySelectorAll('.kartu form button, .form-beli button[type=submit]').forEach(function (btn) {
        btn.addEventListener('click', function (ev) {
            var hati = document.createElement('span');
            hati.textContent = ['💖', '✨', '🌟', '🎈'][Math.floor(Math.random() * 4)];
            hati.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;font-size:22px;left:' +
                ev.clientX + 'px;top:' + ev.clientY + 'px;transition:all .8s ease-out;';
            document.body.appendChild(hati);
            requestAnimationFrame(function () {
                hati.style.transform = 'translateY(-70px) rotate(' + (Math.random() * 60 - 30) + 'deg) scale(1.4)';
                hati.style.opacity = '0';
            });
            setTimeout(function () { hati.remove(); }, 850);
        });
    });
})();
