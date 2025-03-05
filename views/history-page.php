<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Historial de Pagos de Inasistencias', 'amelia-no-shows'); ?></h1>
    
    <div class="ans-container">
        <!-- Date Filter Form -->
        <form method="get" class="ans-filter-form">
            <input type="hidden" name="page" value="amelia-no-shows-history">
            <div class="ans-date-filters">
                <label for="date_from"><?php esc_html_e('Desde:', 'amelia-no-shows'); ?></label>
                <input type="date" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>">
                
                <label for="date_to"><?php esc_html_e('Hasta:', 'amelia-no-shows'); ?></label>
                <input type="date" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>">
                
                <input type="submit" class="button" value="<?php esc_attr_e('Filtrar', 'amelia-no-shows'); ?>">
                <?php if (isset($_GET['date_from']) || isset($_GET['date_to'])): ?>
                    <a href="?page=amelia-no-shows-history" class="button"><?php esc_html_e('Limpiar', 'amelia-no-shows'); ?></a>
                <?php endif; ?>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Cliente', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Correo', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Servicio', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Instructor', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Fecha de Cita', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Monto Pagado', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Fecha de Pago', 'amelia-no-shows'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No hay registros de pagos.', 'amelia-no-shows'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $record): ?>
                        <tr>
                            <td><?php echo esc_html($record->customerName); ?></td>
                            <td><?php echo esc_html($record->customerEmail); ?></td>
                            <td><?php echo esc_html($record->serviceName); ?></td>
                            <td><?php echo esc_html($record->providerName); ?></td>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($record->bookingStart))); ?></td>
                            <td><?php echo esc_html('$' . number_format($record->penaltyAmount, 2)); ?></td>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($record->paidDate))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        esc_html__('%s elementos', 'amelia-no-shows'),
                        number_format_i18n($total_items)
                    ); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.ans-date-filters {
    margin: 1em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.ans-date-filters label {
    margin-right: 10px;
}

.ans-date-filters input[type="date"] {
    margin-right: 20px;
}

.ans-filter-form .button {
    margin-right: 10px;
}
</style> 