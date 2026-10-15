<?php
/**
 * Konfigurasi & helper Cloudinary.
 *
 * Upload gambar dari panel admin dilakukan langsung dari browser (unsigned
 * upload preset) ke Cloudinary, lalu URL "secure_url" yang dikembalikan
 * disimpan sebagai kolom `gambar` / `galeri` pada data produk.
 *
 * Isi environment variables di Vercel (atau .env lokal):
 *   CLOUDINARY_CLOUD_NAME   contoh: "aldo3d"
 *   CLOUDINARY_UPLOAD_PRESET contoh: "aldebaran_unsigned"
 */

function cloudinary_cloud_name(): string {
    return getenv('CLOUDINARY_CLOUD_NAME') ?: '';
}

function cloudinary_upload_preset(): string {
    return getenv('CLOUDINARY_UPLOAD_PRESET') ?: '';
}

/** true jika Cloudinary sudah dikonfigurasi (cloud name & preset terisi). */
function cloudinary_siap(): bool {
    return cloudinary_cloud_name() !== '' && cloudinary_upload_preset() !== '';
}

/** Endpoint upload unsigned dari sisi klien. */
function cloudinary_upload_url(): string {
    $cloud = rawurlencode(cloudinary_cloud_name());
    return "https://api.cloudinary.com/v1_1/{$cloud}/image/upload";
}

/**
 * true jika sebuah nilai gambar adalah URL absolut (Cloudinary / http).
 * Dipakai gambar_url() di functions.php.
 */
function gambar_adalah_url(string $file): bool {
    return preg_match('#^https?://#i', $file) === 1;
}
