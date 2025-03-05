<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Configuración de Monto de Penalización', 'amelia-no-shows'); ?></h1>
    
    <div class="ans-container">
        <form method="post" action="">
            <?php wp_nonce_field('update_penalty_amount'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="penalty_amount"><?php esc_html_e('Monto de Penalización ($)', 'amelia-no-shows'); ?></label>
                    </th>
                    <td>
                        <input name="penalty_amount" type="number" step="0.01" min="0" id="penalty_amount" 
                               value="<?php echo esc_attr($current_amount); ?>" class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Este monto será aplicado a cada inasistencia.', 'amelia-no-shows'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Guardar Cambios', 'amelia-no-shows'), 'primary', 'submit_penalty_amount'); ?>
        </form>
    </div>
</div> 