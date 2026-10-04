<section class="error-page">
    <h1>404</h1>

    <h2>Halaman Tidak Ditemukan</h2>

    <p>
        Halaman yang diminta tidak tersedia atau alamatnya telah berubah.
    </p>

    <?php $homePath = \App\Support\Http::user() !== null ? '/dashboard' : '/login'; ?>
    <a class="button button-secondary" href="<?= $homePath ?>">
        Kembali ke halaman utama
    </a>
</section>
