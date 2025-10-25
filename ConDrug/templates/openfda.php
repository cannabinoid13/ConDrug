<?php
/**
 * OpenFDA Search and Charts Template
 */
?>
<div class="condrug-openfda" data-condrug-openfda>
    <div class="condrug-openfda__header">
        <h1><?php echo esc_html__('İlaç Yan Etki Analizi', 'condrug'); ?></h1>
        <p><?php echo esc_html__('İlaç veya etken madde adını girerek OpenFDA verilerine göre istatistiksel grafikleri görüntüleyin.', 'condrug'); ?></p>
    </div>

    <div class="condrug-openfda__search">
        <label for="condrug-openfda-query" class="screen-reader-text"><?php echo esc_html__('Arama', 'condrug'); ?></label>
        <input type="text" id="condrug-openfda-query" class="condrug-input" placeholder="<?php echo esc_attr__('İlaç veya etken madde adı yazın ve Enter\'a basın', 'condrug'); ?>" />
        <button class="condrug-button" data-condrug-openfda-search>
            <?php echo esc_html__('Ara', 'condrug'); ?>
        </button>
    </div>

    <div class="condrug-openfda__status" aria-live="polite"></div>

    <div class="condrug-openfda__content" hidden>
        <div class="condrug-openfda__summary"></div>

        <div class="condrug-openfda__charts">
            <div class="condrug-card">
                <h2><?php echo esc_html__('En Sık Görülen Yan Etkiler', 'condrug'); ?></h2>
                <canvas id="condrug-chart-reactions" height="200"></canvas>
            </div>
            <div class="condrug-card">
                <h2><?php echo esc_html__('Yaş Dağılımı', 'condrug'); ?></h2>
                <canvas id="condrug-chart-ages" height="200"></canvas>
            </div>
            <div class="condrug-card">
                <h2><?php echo esc_html__('Cinsiyet Dağılımı', 'condrug'); ?></h2>
                <canvas id="condrug-chart-gender" height="200"></canvas>
            </div>
            <div class="condrug-card">
                <h2><?php echo esc_html__('Yıllara Göre Bildirimler', 'condrug'); ?></h2>
                <canvas id="condrug-chart-year" height="200"></canvas>
            </div>
        </div>

        <div class="condrug-openfda__raw" hidden>
            <details>
                <summary><?php echo esc_html__('Ham Yanıtı Göster', 'condrug'); ?></summary>
                <pre class="condrug-openfda__pre"></pre>
            </details>
        </div>
    </div>

    <div class="condrug-openfda__footer">
        <small><?php echo esc_html__('Veriler OpenFDA tarafından sağlanmaktadır.', 'condrug'); ?></small>
    </div>
</div>
