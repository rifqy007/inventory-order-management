<section class="error-page">
    <h1>500</h1>

    <h2>Terjadi Kesalahan Internal</h2>

    <p>
        Aplikasi tidak dapat menyelesaikan permintaan saat ini.
        Detail teknis telah dicatat pada log aplikasi.
    </p>

    <?php $homePath = \App\Support\Http::user() !== null ? '/dashboard' : '/login'; ?>
    <a class="button button-secondary" href="<?= $homePath ?>">
        Kembali ke halaman utama
    </a>
</section>
