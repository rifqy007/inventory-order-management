<section class="error-page">
    <h1>403</h1>

    <h2>Akses Ditolak</h2>

    <p>
        Akun yang sedang digunakan tidak mempunyai kewenangan
        untuk membuka halaman ini.
    </p>

    <?php $homePath = \App\Support\Http::user() !== null ? '/dashboard' : '/login'; ?>
    <a class="button button-secondary" href="<?= $homePath ?>">
        Kembali ke halaman utama
    </a>
</section>
