<?php
/**
 * Konfigurasi & helper Cloudinary.
 *
 * Upload gambar dari panel admin dilakukan langsung dari browser (unsigned
 * upload preset) ke Cloudinary, lalu URL "secure_url" yang dikembalikan
 * disimpan sebagai kolom `gambar` / `galeri` pada data produk.
 *
 * Isi environment variables di Vercel (atau .env lokal):
 *   CLOUDINARY_CLOUD_NAME    contoh: "cu9n478a"
 *   CLOUDINARY_UPLOAD_PRESET contoh: "kliovrbz"
 *   CLOUDINARY_FOLDER        contoh: "ruang-3d"
 *
 * Catatan: nilai di bawah ini dipakai bila env tidak diisi, dan ikut tersimpan
 * di git (tidak hilang saat ganti laptop). Cloud name & preset UNSIGNED memang
 * tampil di sisi browser (window.CLOUDINARY), jadi bukan rahasia.
 */

const CLOUDINARY_CLOUD_NAME_DEFAULT    = 'cu9n478a';
const CLOUDINARY_UPLOAD_PRESET_DEFAULT = 'kliovrbz';

/** Folder Media Library tujuan upload (auto dibuat saat upload pertama). */
const CLOUDINARY_FOLDER_DEFAULT = 'ruang-3d';

function cloudinary_cloud_name(): string {
    return (string)(getenv('CLOUDINARY_CLOUD_NAME') ?: CLOUDINARY_CLOUD_NAME_DEFAULT);
}

function cloudinary_upload_preset(): string {
    return (string)(getenv('CLOUDINARY_UPLOAD_PRESET') ?: CLOUDINARY_UPLOAD_PRESET_DEFAULT);
}

function cloudinary_folder(): string {
    return (string)(getenv('CLOUDINARY_FOLDER') ?: CLOUDINARY_FOLDER_DEFAULT);
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
