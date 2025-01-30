$(document).ready(function () {
    $('.tarj').hide();
    // $('.dest').hide();
    // $('.dest').fadeIn(800);
    
    $('.conte, .dest').css({
        'opacity': '0',
        'transform': 'translateY(5px)'
    });

    $('.conte, .dest').each(function (index) {
        $(this).delay(200 * index).animate({
            opacity: 1,
            marginTop: 0
        }, 500);
    });

    function vibrar(elemento, repeticiones, intensidad, velocidad) {
        let i = 0;
        let interval = setInterval(function () {
            let movimiento = (i % 2 === 0 ? intensidad : -intensidad);
            $(elemento).css("transform", `translateX(${movimiento}px)`);
            i++;
            if (i > repeticiones * 2) {
                clearInterval(interval);
                $(elemento).css("transform", "translateX(0)"); // Restablece la posición
            }
        }, velocidad);
    }

    $(".tarj").each(function (index) {
        let delay = 0 * index; // Retraso entre elementos
        $('.tarj').fadeIn(800);
        setTimeout(() => vibrar(this, 4, 1, 50), delay);
    });
});
