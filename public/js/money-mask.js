// Adiciona máscara de moeda brasileira (R$ 0,00) nos inputs com a classe .money-mask
// Requer IMask.js

document.addEventListener('DOMContentLoaded', function () {
    if (typeof IMask === 'undefined') return;
    document.querySelectorAll('.money-mask').forEach(function (input) {
        IMask(input, {
            mask: Number,
            scale: 2,
            signed: false,
            thousandsSeparator: '.',
            padFractionalZeros: true,
            normalizeZeros: true,
            radix: ',',
            mapToRadix: ['.'],
            min: 0
        });
    });
});
