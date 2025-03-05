<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('No-Show Tracker', 'amelia-no-shows'); ?></h1>
    
    <div class="ans-container">
        <table class="wp-list-table widefat fixed striped ans-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Cliente', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Correo', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('No-Shows', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Monto Total', 'amelia-no-shows'); ?></th>
                    <th><?php esc_html_e('Acciones', 'amelia-no-shows'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5"><?php esc_html_e('Cargando...', 'amelia-no-shows'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Customer Details Modal -->
    <div id="ans-customer-modal" class="ans-modal">
        <div class="ans-modal-content">
            <span class="ans-modal-close">&times;</span>
            <h2><?php esc_html_e('Detalles de Inasistencias', 'amelia-no-shows'); ?></h2>
            <div class="ans-modal-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Fecha', 'amelia-no-shows'); ?></th>
                            <th><?php esc_html_e('Servicio', 'amelia-no-shows'); ?></th>
                            <th><?php esc_html_e('Instructor', 'amelia-no-shows'); ?></th>
                            <th><?php esc_html_e('Acciones', 'amelia-no-shows'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4"><?php esc_html_e('Cargando...', 'amelia-no-shows'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ans-loading">
        <div class="ans-loading-content">
            <?php esc_html_e('Cargando...', 'amelia-no-shows'); ?>
        </div>
    </div>
</div>

<style>
.ans-table .actions-column {
    width: 250px;
    text-align: right;
    white-space: nowrap;
    padding-right: 10px;
}

.ans-table .button-container {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.ans-table .button {
    margin: 0;
    min-width: 100px;
}

.ans-table .button.button-primary {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}

.ans-table .button.button-primary:hover {
    background: #135e96;
    border-color: #135e96;
    color: #fff;
}

.ans-loading {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.8);
}

.ans-loading-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.ans-modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.ans-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #ddd;
    width: 80%;
    max-width: 900px;
    border-radius: 4px;
    position: relative;
}

@media screen and (max-width: 782px) {
    .ans-table .actions-column {
        width: auto;
        text-align: left;
    }
    
    .ans-table .button {
        display: inline-block;
        margin: 3px;
    }
}
</style> 