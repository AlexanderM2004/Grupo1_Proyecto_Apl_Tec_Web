$(document).ready(function() {
    // Manejar animaciones de los tabs
    let animating = false;

    $('#authTabs .nav-link').on('click', function(e) {
        if (animating) {
            e.preventDefault();
            return;
        }

        const targetTab = $($(this).data('bs-target'));
        const currentTab = $('.tab-pane.active');

        if (targetTab.is(currentTab)) return;

        animating = true;

        // Animación de salida
        currentTab.css({
            'transform': 'translateX(0)',
            'opacity': '1'
        }).animate({
            'transform': 'translateX(-200px)',
            'opacity': '0'
        }, {
            duration: 300,
            step: function(now, fx) {
                if (fx.prop === 'transform') {
                    $(this).css('transform', `translateX(${now}px)`);
                }
            },
            complete: function() {
                // Preparar el nuevo tab
                targetTab.css({
                    'transform': 'translateX(200px)',
                    'opacity': '0'
                });

                // Actualizar clases de Bootstrap
                currentTab.removeClass('active');
                targetTab.addClass('active');

                // Animación de entrada
                targetTab.animate({
                    'transform': 'translateX(0)',
                    'opacity': '1'
                }, {
                    duration: 300,
                    step: function(now, fx) {
                        if (fx.prop === 'transform') {
                            $(this).css('transform', `translateX(${now}px)`);
                        }
                    },
                    complete: function() {
                        animating = false;
                    }
                });
            }
        });
    });

    // Aplicar efecto hover a los tabs
    $('.nav-link').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );

    // Manejar transiciones suaves para los botones de género
    $('.gender-btn').on('click', function() {
        $('.gender-btn').removeClass('selected').css('transform', 'scale(1)');
        $(this).addClass('selected').css('transform', 'scale(1.05)');
    });
});