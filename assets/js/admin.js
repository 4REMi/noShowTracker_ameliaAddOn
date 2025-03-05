jQuery(document).ready(function($) {
    const modal = $('#ans-customer-modal');
    const modalClose = $('.ans-modal-close');
    const loading = $('.ans-loading');
    let currentCustomerId = null;

    // Load initial data
    console.log('Starting to load no-shows...');
    loadNoShows();

    // Close modal when clicking the X or outside the modal
    modalClose.on('click', closeModal);
    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            closeModal();
        }
    });

    function loadNoShows() {
        loading.show();
        console.log('Making AJAX request to load no-shows...');
        $.ajax({
            url: ansAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'ans_get_no_shows',
                nonce: ansAjax.nonce
            },
            success: function(response) {
                console.log('Received response:', response);
                if (response.success) {
                    console.log('Data received:', response.data);
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        renderNoShows(response.data);
                    } else {
                        $('.ans-table tbody').html('<tr><td colspan="5">No se encontraron inasistencias.</td></tr>');
                    }
                } else {
                    console.error('Error in response:', response);
                    alert('Error al cargar inasistencias');
                }
                loading.hide();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Error al cargar inasistencias');
                loading.hide();
            }
        });
    }

    function renderNoShows(data) {
        console.log('Rendering no-shows data:', data);
        const tbody = $('.ans-table tbody');
        tbody.empty();

        data.forEach(function(customer) {
            console.log('Rendering customer:', customer);
            const actionsCell = $('<td class="actions-column"></td>');
            
            // Create both buttons first
            const buttons = $('<div class="button-container"></div>');
            
            buttons.append(
                $('<button></button>')
                    .addClass('button button-primary view-details')
                    .text('Ver Detalles')
                    .data('customer-id', customer.customerId)
            );
            
            buttons.append(' '); // Space between buttons
            
            buttons.append(
                $('<button></button>')
                    .addClass('button button-primary pay-all')
                    .text('Pagar Todo')
                    .data('customer-id', customer.customerId)
            );
            
            // Add buttons container to cell
            actionsCell.append(buttons);

            const row = $('<tr></tr>').append(
                $('<td></td>').text(`${customer.firstName} ${customer.lastName}`),
                $('<td></td>').text(customer.email),
                $('<td></td>').text(customer.no_show_count),
                $('<td></td>').text(`$${parseFloat(customer.penalty_amount).toFixed(2)}`),
                actionsCell
            );

            tbody.append(row);
        });
        console.log('Finished rendering table');
        console.log('Final table HTML:', tbody.html());
    }

    function openCustomerDetails(customerId) {
        currentCustomerId = customerId;
        loading.show();
        
        $.ajax({
            url: ansAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'ans_get_customer_details',
                nonce: ansAjax.nonce,
                customerId: customerId
            },
            success: function(response) {
                if (response.success) {
                    renderCustomerDetails(response.data);
                    modal.show();
                } else {
                    alert('Error al cargar detalles del cliente');
                }
                loading.hide();
            },
            error: function() {
                alert('Error al cargar detalles del cliente');
                loading.hide();
            }
        });
    }

    function renderCustomerDetails(appointments) {
        const tbody = $('#ans-customer-modal table tbody');
        tbody.empty();

        appointments.forEach(function(appointment) {
            // Use WordPress site timezone for date display
            const date = new Date(appointment.bookingStart + 'Z'); // Add Z to treat as UTC
            const formattedDate = date.toLocaleString('es-ES', {
                timeZone: ansAjax.timezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const row = $('<tr>').append(
                $('<td>').text(formattedDate),
                $('<td>').text(appointment.service_name),
                $('<td>').text(appointment.provider_name),
                $('<td>').append(
                    $('<button>')
                        .addClass('button button-secondary pay-button')
                        .text('Marcar como Pagado')
                        .data('booking-id', appointment.booking_id)
                )
            );
            tbody.append(row);
        });
    }

    function markAsPaid(bookingId) {
        if (!confirm('¿Está seguro que desea marcar esta inasistencia como pagada?')) {
            return;
        }

        loading.show();
        $.ajax({
            url: ansAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'ans_mark_as_paid',
                nonce: ansAjax.nonce,
                bookingId: bookingId
            },
            success: function(response) {
                if (response.success) {
                    // Refresh both the main table and modal data
                    loadNoShows();
                    if (currentCustomerId) {
                        openCustomerDetails(currentCustomerId);
                    }
                } else {
                    alert('Error al marcar como pagado');
                }
                loading.hide();
            },
            error: function() {
                alert('Error al marcar como pagado');
                loading.hide();
            }
        });
    }

    function markAllAsPaid(customerId) {
        if (!confirm('¿Está seguro de que desea marcar todos los no-shows como pagados?')) {
            return;
        }

        loading.show();
        $.ajax({
            url: ansAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'ans_mark_all_as_paid',
                nonce: ansAjax.nonce,
                customerId: customerId
            },
            success: function(response) {
                if (response.success) {
                    alert('Todos los pagos registrados exitosamente.');
                    modal.hide();
                    currentCustomerId = null;
                    loadNoShows();
                } else {
                    alert('Error al marcar todos los no-shows como pagados');
                }
                loading.hide();
            },
            error: function() {
                alert('Error al marcar todos los no-shows como pagados');
                loading.hide();
            }
        });
    }

    function closeModal() {
        modal.hide();
        currentCustomerId = null;
    }

    // Event handlers
    $(document).on('click', '.view-details', function() {
        const customerId = $(this).data('customer-id');
        openCustomerDetails(customerId);
    });

    $(document).on('click', '.pay-button', function() {
        const bookingId = $(this).data('booking-id');
        markAsPaid(bookingId);
    });

    $(document).on('click', '.pay-all', function() {
        const customerId = $(this).data('customer-id');
        markAllAsPaid(customerId);
    });
}); 